<?php

namespace Biller\PrestaShop\Repositories;

use Biller\BusinessLogic\Authorization\AuthorizationService;
use Biller\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Biller\Infrastructure\Logger\Logger;
use Configuration;

/**
 * Class ConfigurationRepository
 * @package Biller\PrestaShop\Repositories
 */
class ConfigurationRepository
{

    /**
     * Updates value in PrestaShop configuration table
     *
     * @param $name
     * @param $value
     * @return bool
     */
    public static function updateValue($name, $value)
    {
        return Configuration::updateValue($name, $value);
    }

    /**
     * Gets value from PrestaShop configuration table
     *
     * @param $name
     * @return false|string
     */
    public static function getValue($name)
    {
        return Configuration::get($name);
    }

    /**
     * @return string|void
     */
    public static function getWebshopUID() {
        try {
            $userInfo = AuthorizationService::getInstance()->getUserInfo();
            return $userInfo->getWebShopUID();
        } catch (FailedToRetrieveAuthInfoException $exception) {
            Logger::logError('User needs to be authorized in order to get webshop uid!');
        }
    }
}
