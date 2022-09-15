<?php

namespace Biller\PrestaShop\Utility\Version\Configuration;

use Biller\PrestaShop\Utility\Version\Configuration\Contract\ConfigurationVersionInterface;
use Configuration;

/**
 * Class ConfigurationVersion16. Used for configuration from PrestaShop 1.6.0.14 to 1.7.0.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Configuration
 */
class ConfigurationVersion16 implements ConfigurationVersionInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigurationValue($name, $default)
    {
        $value = Configuration::get($name, null, null, null);

        return ($value !== false) ? $value : $default;
    }
}
