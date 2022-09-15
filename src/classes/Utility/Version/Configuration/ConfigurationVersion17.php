<?php

namespace Biller\PrestaShop\Utility\Version\Configuration;

use Biller\PrestaShop\Utility\Version\Configuration\Contract\ConfigurationVersionInterface;
use Configuration;

/**
 * Class ConfigurationVersion17. Used for configuration from PrestaShop 1.7.0.0+.
 *
 * @package Biller\PrestaShop\Utility\Version\Configuration
 */
class ConfigurationVersion17 implements ConfigurationVersionInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigurationValue($name, $default)
    {
        return Configuration::get($name, null, null, null, $default);
    }
}
