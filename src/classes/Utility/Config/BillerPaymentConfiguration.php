<?php

namespace Biller\PrestaShop\Utility\Config;

use Biller\PrestaShop\Repositories\ConfigurationRepository;

/**
 * BillerPaymentConfiguration class.
 *
 * @package Biller\PrestaShop\Utility\Config
 */
class BillerPaymentConfiguration implements Contract\BillerPaymentConfiguration
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return ConfigurationRepository::getValue(Config::BILLER_NAME_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        ConfigurationRepository::updateValue(Config::BILLER_NAME_KEY, $name);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {

        return ConfigurationRepository::getValue(Config::BILLER_DESCRIPTION_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        ConfigurationRepository::updateValue(Config::BILLER_DESCRIPTION_KEY, $description);
    }

    /**
     * @inheritDoc
     */
    public function getMethodEnabledStatus()
    {
        return ConfigurationRepository::getValue(Config::BILLER_ENABLE_BUSINESS_INVOICE_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setMethodEnabledStatus($enabled)
    {
        ConfigurationRepository::updateValue(Config::BILLER_ENABLE_BUSINESS_INVOICE_KEY, $enabled);
    }
}
