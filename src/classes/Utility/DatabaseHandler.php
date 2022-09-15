<?php

namespace Biller\PrestaShop\Utility;

use Db;

/**
 * Class DatabaseHandler. Executes SQL queries over Biller databases.
 *
 * @package Biller\PrestaShop\Utility
 */
class DatabaseHandler
{
    /**
     * Deletes table from database.
     *
     * @param string $name Name of database
     *
     * @return bool Result of drop table query
     */
    public static function dropTable($name)
    {
        $script = 'DROP TABLE IF EXISTS ' . bqSQL(_DB_PREFIX_ . "${name}");

        return Db::getInstance()->execute($script);
    }

    /**
     * Creates table in database.
     *
     * @param string $name Name of database
     * @param int $indexNum Number of index columns
     *
     * @return bool Result of create table query
     */
    public static function createTable($name, $indexNum)
    {
        $indexColumns = '';
        for ($i = 1; $i <= $indexNum; $i++) {
            $indexColumns .= 'index_' . $i . '      VARCHAR(255),';
        }
        $sql = 'CREATE TABLE IF NOT EXISTS ' . bqSQL(_DB_PREFIX_ . "${name}")
            . '(
  	        `id`           INT(64) unsigned NOT NULL AUTO_INCREMENT,
  	        `type`         VARCHAR(255),' .
            $indexColumns .
            '
	        `data`         MEDIUMTEXT,
	         PRIMARY KEY (`id`)
             ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Deletes rows from given table that satisfy given condition.
     *
     * @return bool Deletion status
     */
    public static function deleteRows($tableName, $where)
    {
        return Db::getInstance()->delete($tableName, $where);
    }

    /**
     * Updates rows from given table with given values that satisfy given condition.
     *
     * @param string $tableName Name of table
     * @param array $values Array of values to be applied in format {column => value}
     * @param string $where Condition for update
     *
     * @return bool Update status
     */
    public static function updateRows($tableName, $values, $where)
    {
        return Db::getInstance()->update($tableName, $values, $where);
    }
}
