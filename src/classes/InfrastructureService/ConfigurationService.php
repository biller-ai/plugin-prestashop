<?php

namespace Biller\PrestaShop\InfrastructureService;

use Biller\Infrastructure\Configuration\Configuration;
use Biller\PrestaShop\Repositories\ConfigurationRepository;

/**
 * Class ConfigurationService
 * @package Biller\PrestaShop\InfrastructureService
 */
class ConfigurationService extends Configuration
{

    const INTEGRATION_NAME = 'biller-business-invoice';

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
        return \Context::getContext()->shop->id;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSystemName()
    {
        return \Context::getContext()->shop->name;
    }

    /**
     * @inheritDoc
     */
    protected function getConfigValue($name, $default = null)
    {
        $value = ConfigurationRepository::getValue($name);

        return $value ?: $default;
    }
}
