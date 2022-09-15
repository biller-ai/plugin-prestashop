<?php

namespace Biller\PrestaShop\InfrastructureService;

use Biller\Infrastructure\Configuration\ConfigEntity;
use Biller\Infrastructure\Configuration\Configuration as CoreConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Exception\BoolConfigTypeNotSupportedException;
use Biller\PrestaShop\Utility\Version\Configuration\Contract\ConfigurationVersionInterface;
use Configuration;
use Context;
use OrderState;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Version\Contract\VersionHandlerInterface;
use Biller\PrestaShop\Utility\Version\Version16;
use Biller\PrestaShop\Utility\Version\Version17;
use Biller\PrestaShop\Utility\Version\Version177;

/**
 * Class ConfigurationService. Used for getting and setting configuration data.
 *
 * @package Biller\PrestaShop\InfrastructureService
 */
class ConfigurationService extends CoreConfiguration
{
    /** @var string Name of integration. */
    const INTEGRATION_NAME = 'biller-business-invoice';
    /** @var string Biller configuration database prefix. */
    const BILLER_DB_PREFIX = 'BILLER_';

    /**
     * @inheritDoc
     */
    public function getIntegrationName()
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSystemId()
    {
        return Context::getContext()->shop->id;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSystemName()
    {
        return Context::getContext()->shop->name;
    }

    /**
     * @inheritDoc
     */
    public function getConfigValue($name, $default = null)
    {
        return $this->getConfigurationVersion()->getConfigurationValue(self::BILLER_DB_PREFIX . $name, $default);
    }

    /**
     * @inheritDoc
     *
     * @throws BoolConfigTypeNotSupportedException
     */
    public function saveConfigValue($name, $value)
    {
        if (is_bool($value)) {
            throw new BoolConfigTypeNotSupportedException(
                "Passed argument [${$name}] is of type boolean."
                . " Saving boolean values is not supported."
            );
        }

        Configuration::updateValue(self::BILLER_DB_PREFIX . $name, $value);

        /** dummy ConfigEntity object */
        $configEntity = new ConfigEntity();

        $configEntity->setName($name);
        $configEntity->setValue($value);

        return $configEntity;
    }

    /**
     * Gets available order statuses.
     *
     * @return array Array of available order statuses
     */
    public function getAvailableOrderStatuses()
    {
        return OrderState::getOrderStates(1);
    }

    /**
     * Gets order status map from configuration.
     *
     * @return array|null Array in format {BillerStatusKey => PrestaShopStatusId} or null if order status map not saved
     */
    public function getOrderStatusMap()
    {
        $orderStatusMap = $this->getConfigValue(Config::ORDER_STATUS_MAP_KEY);

        return $orderStatusMap ? json_decode($orderStatusMap, true) : null;
    }

    /**
     * Saves order status map to configuration.
     *
     * @param array $orderStatusMap in format {BillerStatusKey => PrestaShopStatusId}
     *
     * @return bool
     * @throws BoolConfigTypeNotSupportedException
     */
    public function saveOrderStatusMap(array $orderStatusMap)
    {
        $this->saveConfigValue(Config::ORDER_STATUS_MAP_KEY, json_encode($orderStatusMap));

        return true;
    }

    /**
     * Returns Configuration Version handler depending on used PrestaShop version.
     *
     * @return ConfigurationVersionInterface
     */
    private function getConfigurationVersion()
    {
        return ServiceRegister::getService(ConfigurationVersionInterface::class);
    }
}
