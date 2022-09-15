<?php

namespace Biller\PrestaShop\Repositories;

use Biller\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Biller\Infrastructure\Logger\Logger;
use Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference;
use Biller\Infrastructure\ORM\QueryFilter\QueryFilter;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\BusinessLogic\Authorization\AuthorizationService;
use Exception;

/**
 * Class OrderReferenceRepository. Repository for OrderReference table.
 *
 * @package Biller\PrestaShop\Repositories
 */
class OrderReferenceRepository extends BaseRepository
{
    /**
     * Name of the base entity table in database.
     */
    const TABLE_NAME = 'biller_order_reference';
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

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
     * Returns external order uid by cart ID.
     *
     * @param string $cartId
     *
     * @return string
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getExternalOrderUUIDByCartId($cartId)
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('externalUUID', '=', $cartId);

        try {
            /** @var OrderReference $orderReference */
            $orderReference = $this->selectOne($queryFilter);
        } catch (Exception $exception) {
            Logger::logError($exception->getMessage());
        }

        return $orderReference ? $orderReference->getExternalUUID() : null;
    }

    /**
     * Returns external webshop UID if user is authorized.
     *
     * @return string|void
     */
    public function getWebshopUID()
    {
        try {
            $userInfo = AuthorizationService::getInstance()->getUserInfo();

            return $userInfo->getWebShopUID();
        } catch (FailedToRetrieveAuthInfoException $exception) {
            Logger::logError($exception->getMessage());
        }
    }
}
