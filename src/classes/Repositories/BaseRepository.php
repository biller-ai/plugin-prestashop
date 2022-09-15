<?php

namespace Biller\PrestaShop\Repositories;

use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ORM\Entity;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Biller\Infrastructure\ORM\QueryFilter\Operators;
use Biller\Infrastructure\ORM\QueryFilter\QueryCondition;
use Biller\Infrastructure\ORM\QueryFilter\QueryFilter;
use Biller\Infrastructure\ORM\Utility\IndexHelper;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class BaseRepository. Implements basic CRUD operations using core ORM entities.
 *
 * @package Biller\PrestaShop\Repositories
 */
class BaseRepository implements RepositoryInterface
{
    /**
     * Name of the base entity table in database. Empty string since the plugin doesn't have its own base table but
     * instead uses Presta's configuration table.
     */
    const TABLE_NAME = '';
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    protected $entityClass;
    /**
     * @var array
     */
    private $indexMapping;

    /**
     * Returns full class name.
     *
     * @return string Full class name
     */
    public static function getClassName()
    {
        return static::THIS_CLASS_NAME;
    }

    /**
     * Sets repository entity.
     *
     * @param string $entityClass Repository entity class
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Executes select query and returns first result.
     *
     * @param QueryFilter|null $filter Filter for query
     *
     * @return Entity|null First found entity or NULL
     *
     * @throws QueryFilterInvalidParamException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function selectOne(QueryFilter $filter = null)
    {
        if ($filter === null) {
            $filter = new QueryFilter();
        }

        $filter->setLimit(1);
        $results = $this->select($filter);

        return empty($results) ? null : $results[0];
    }

    /**
     * Executes select query.
     *
     * @param QueryFilter $filter Filter for query
     *
     * @return Entity[] A list of found entities ot empty array
     *
     * @throws QueryFilterInvalidParamException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function select(QueryFilter $filter = null)
    {
        $entity = new $this->entityClass;

        $fieldsIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $filter ? $this->buildConditionGroups($filter, $fieldsIndexMap) : array();
        $type = $entity->getConfig()->getType();

        $typeCondition = "type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldsIndexMap);
        $result = $this->getRecordsByCondition(
            $typeCondition . (!empty($whereCondition) ? ' AND ' . $whereCondition : ''),
            $filter
        );

        return $this->unserializeEntities($result);
    }

    /**
     * Executes insert query and returns ID of created entity. Entity will be updated with new ID.
     *
     * @param Entity $entity Entity to be saved
     *
     * @return int Identifier of saved entity
     *
     * @throws PrestaShopDatabaseException
     */
    public function save(Entity $entity)
    {
        $indexes = IndexHelper::transformFieldsToIndexes($entity);
        $record = $this->prepareDataForInsertOrUpdate($entity, $indexes);
        $record['type'] = pSQL($entity->getConfig()->getType());

        $result = \Db::getInstance()->insert(static::TABLE_NAME, $record);

        if (!$result) {
            $type = $entity->getConfig()->getType();
            $dbErrorMessage = \Db::getInstance()->getMsgError();
            $message = "Entity $type cannot be inserted. Error: $dbErrorMessage";

            Logger::logError($message);

            throw new \RuntimeException($message);
        }

        $entity->setId((int)\Db::getInstance()->Insert_ID());

        return $entity->getId();
    }

    /**
     * Executes update query and returns success flag.
     *
     * @param Entity $entity Entity to be updated
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE
     */
    public function update(Entity $entity)
    {
        $indexes = IndexHelper::transformFieldsToIndexes($entity);
        $record = $this->prepareDataForInsertOrUpdate($entity, $indexes);

        $id = $entity->getId();
        $result = \Db::getInstance()->update(static::TABLE_NAME, $record, "id = $id");
        if (!$result) {
            $type = $entity->getConfig()->getType();

            Logger::logError("Entity $type with ID $id cannot be updated.");
        }

        return $result;
    }

    /**
     * Deletes entities identified by filter.
     *
     * @param QueryFilter $filter Filter used for ident
     *
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function deleteWhere(QueryFilter $filter)
    {
        $entity = new $this->entityClass;

        $fieldIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $this->buildConditionGroups($filter, $fieldIndexMap);
        $type = $entity->getConfig()->getType();

        $typeCondition = "type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldIndexMap);
        $result = $this->deleteRecordsByCondition(
            $typeCondition . (!empty($whereCondition) ? ' AND ' . $whereCondition : ''),
            $filter
        );

        if (!$result) {
            $id = $entity->getId();

            Logger::logError(
                "Could not delete entity $type with ID $id."
            );
        }
    }

    /**
     * Executes delete query and returns success flag.
     *
     * @param Entity $entity Entity to be deleted
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE
     */
    public function delete(Entity $entity)
    {
        $id = $entity->getId();
        $result = \Db::getInstance()->delete(static::TABLE_NAME, "id = $id");

        if (!$result) {
            $type = $entity->getConfig()->getType();

            Logger::logError(
                "Could not delete entity $type with ID $id."
            );
        }

        return $result;
    }

    /**
     * Counts records that match filter criteria.
     *
     * @param QueryFilter $filter Filter for query
     *
     * @return int Number of records that match filter criteria
     *
     * @throws QueryFilterInvalidParamException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function count(QueryFilter $filter = null)
    {
        return count($this->select($filter));
    }

    /**
     * Returns columns that should be in the result of a select query on Biller entity table.
     *
     * @return array Select columns
     */
    protected function getSelectColumns()
    {
        return array('id', 'data');
    }

    /**
     * Returns index mapped to given property.
     *
     * @param string $property Property name
     *
     * @return string Index column in Biller entity table
     */
    protected function getIndexMapping($property)
    {
        if ($this->indexMapping === null) {
            $this->indexMapping = IndexHelper::mapFieldsToIndexes(new $this->entityClass);
        }

        if (array_key_exists($property, $this->indexMapping)) {
            return 'index_' . $this->indexMapping[$property];
        }

        return null;
    }

    /**
     * Translates database records to Biller entities.
     *
     * @param array $records Array of database records
     *
     * @return Entity[] Array of unserialized entities
     */
    protected function unserializeEntities($records)
    {
        $entities = array();
        foreach ($records as $record) {
            $entity = $this->unseralizeEntity($record['data']);
            if ($entity !== null) {
                $entity->setId((int)$record['id']);
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Prepares data for inserting a new record or updating an existing one.
     *
     * @param Entity $entity Biller entity object
     * @param array $indexes Entities' indexes
     *
     * @return array Prepared record for inserting or updating
     */
    protected function prepareDataForInsertOrUpdate(Entity $entity, array $indexes)
    {
        $record = array('data' => pSQL($this->serializeEntity($entity), true));

        foreach ($indexes as $index => $value) {
            $record['index_' . $index] = $value !== null ? pSQL($value, true) : null;
        }

        return $record;
    }

    /**
     * Serializes Entity to string.
     *
     * @param Entity $entity Entity to be serialized
     *
     * @return string Serialized entity
     */
    protected function serializeEntity(Entity $entity)
    {
        return json_encode($entity->toArray());
    }

    /**
     * Builds condition groups (each group is chained with OR internally, and with AND externally) based on query
     * filter.
     *
     * @param QueryFilter $filter Query filter object
     * @param array $fieldIndexMap Map of property indexes
     *
     * @return array Array of condition groups
     *
     * @throws QueryFilterInvalidParamException
     */
    private function buildConditionGroups(QueryFilter $filter, array $fieldIndexMap)
    {
        $groups = array();
        $counter = 0;
        $fieldIndexMap['id'] = 0;
        foreach ($filter->getConditions() as $condition) {
            if (!empty($groups[$counter]) && $condition->getChainOperator() === 'OR') {
                $counter++;
            }

            if (!array_key_exists($condition->getColumn(), $fieldIndexMap)) {
                $column = $condition->getColumn();

                throw new QueryFilterInvalidParamException(
                    "Field $column is not indexed!"
                );
            }

            $groups[$counter][] = $condition;
        }

        return $groups;
    }

    /**
     * Builds WHERE statement of SELECT query by separating AND and OR conditions.
     * Output format: (C1 AND C2) OR (C3 AND C4) OR (C5 AND C6 AND C7)
     *
     * @param array $groups Array of condition groups
     * @param array $fieldIndexMap Map of property indexes
     *
     * @return string Fully formed WHERE statement
     */
    private function buildWhereCondition(array $groups, array $fieldIndexMap)
    {
        $whereStatement = '';
        foreach ($groups as $groupIndex => $group) {
            $conditions = array();
            foreach ($group as $condition) {
                $conditions[] = $this->addCondition($condition, $fieldIndexMap);
            }

            $whereStatement .= '(' . implode(' AND', $conditions) . ')';

            if (count($groups) !== 1 && $groupIndex < count($groups) - 1) {
                $whereStatement .= ' OR ';
            }
        }

        return $whereStatement;
    }

    /**
     * Filters records by given condition.
     *
     * @param QueryCondition $condition Query condition object
     * @param array $indexMap Map of property indexes
     *
     * @return string A single WHERE condition
     */
    private function addCondition(QueryCondition $condition, array $indexMap)
    {
        $column = $condition->getColumn();
        $columnName = $column === 'id' ? 'id' : 'index_' . $indexMap[$column];
        if ($column === 'id') {
            $conditionValue = (int)$condition->getValue();
        } else {
            $conditionValue = IndexHelper::castFieldValue($condition->getValue(), $condition->getValueType());
        }

        if (in_array($condition->getOperator(), array(Operators::NOT_IN, Operators::IN), true)) {
            $values = array_map(function ($item) {
                if (is_string($item)) {
                    return "'$item'";
                }

                if (is_int($item)) {
                    $val = IndexHelper::castFieldValue($item, 'integer');
                    return "'{$val}'";
                }

                $val = IndexHelper::castFieldValue($item, 'double');

                return "'{$val}'";
            }, $condition->getValue());
            $conditionValue = '(' . implode(',', $values) . ')';
        } else {
            $conditionValue = "'" . pSQL($conditionValue, true) . "'";
        }

        return $columnName . ' ' . $condition->getOperator()
            . (!in_array($condition->getOperator(), array(Operators::NULL, Operators::NOT_NULL), true)
                ? $conditionValue : ''
            );
    }

    /**
     * Returns Biller entity records that satisfy provided condition.
     *
     * @param string $condition Condition in format: KEY OPERATOR VALUE
     * @param QueryFilter|null $filter Query filter object
     *
     * @return array Array of Biller entity records
     *
     * @throws QueryFilterInvalidParamException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getRecordsByCondition($condition, QueryFilter $filter = null)
    {
        $query = new \DbQuery();
        $query->select(implode(',', $this->getSelectColumns()))
            ->from(bqSQL(static::TABLE_NAME))
            ->where($condition);
        $this->applyLimitAndOrderBy($query, $filter);

        $result = \Db::getInstance()->executeS($query);

        return !empty($result) ? $result : array();
    }

    /**
     * Applies limit and order by statements to provided SELECT query.
     *
     * @param \DbQuery $query SELECT query
     * @param QueryFilter|null $filter Query filter object
     *
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    private function applyLimitAndOrderBy(\DbQuery $query, QueryFilter $filter = null)
    {
        if ($filter) {
            $limit = $filter->getLimit();

            if ($limit) {
                $query->limit($limit, $filter->getOffset());
            }

            $orderByColumn = $filter->getOrderByColumn();
            if ($orderByColumn) {
                $indexedColumn = $orderByColumn === 'id' ? 'id' : $this->getIndexMapping($orderByColumn);
                if (empty($indexedColumn)) {
                    throw new QueryFilterInvalidParamException(
                        "Unknown or not indexed OrderBy column $orderByColumn"
                    );
                }

                $query->orderBy($indexedColumn . ' ' . $filter->getOrderDirection());
            }
        }
    }

    /**
     * Unserialize entity from given string.
     *
     * @param string $data Serialized entity as string
     *
     * @return Entity Created entity object
     */
    private function unseralizeEntity($data)
    {
        $jsonEntity = json_decode($data, true);
        if (array_key_exists('class_name', $jsonEntity)) {
            $entity = new $jsonEntity['class_name'];
        } else {
            $entity = new $this->entityClass;
        }

        /** @var Entity $entity */
        $entity->inflate($jsonEntity);

        return $entity;
    }

    /**
     * Deletes Biller entity records satisfying provided condition.
     *
     * @param string $condition Condition in format: KEY OPERATOR VALUE
     * @param QueryFilter|null $filter Query filter object
     *
     * @return bool Deletion status
     */
    private function deleteRecordsByCondition($condition, QueryFilter $filter = null)
    {
        $limit = $filter ? $filter->getLimit() : 0;
        return \Db::getInstance()->delete(static::TABLE_NAME, $condition, $limit);
    }
}
