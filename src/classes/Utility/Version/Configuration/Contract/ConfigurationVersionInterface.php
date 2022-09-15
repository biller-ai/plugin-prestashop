<?php

namespace Biller\PrestaShop\Utility\Version\Configuration\Contract;

/**
 * Contains methods for PrestaShop Configuration class that vary by PrestaShop versions.
 */
interface ConfigurationVersionInterface
{
    /**
     * Get configuration value from Configuration table. Returns default if it doesn't exist.
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function getConfigurationValue($name, $default);
}
