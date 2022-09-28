<?php

namespace Biller\PrestaShop\Utility;

use PrestaShop\PrestaShop\Core\Crypto\Hashing;

/**
 * Class Hash
 *
 * @package Biller\PrestaShop\Utility
 */
class Hash
{
    /**
     * @var Hash
     */
    private static $instance = null;

    const MODULE_NAME = 'biller';

    const HASH_KEY = _COOKIE_KEY_;

    /**
     * Get current instance of Hash
     *
     * @return Hash Current instance of class
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Hash();
        }

        return self::$instance;
    }

    /**
     * Hash key given as first parameter.
     *
     * @param string $key Key to be hashed
     *
     * @return string
     */
    public function hashKey($key)
    {
        return md5(self::MODULE_NAME . $key . self::HASH_KEY);
    }

    /**
     * Check if hash is valid
     *
     * @param string $key Key to be hashed and compared
     * @param string $hash Hash to compare if valid
     *
     * @return bool
     */
    public function checkKey($key, $hash)
    {
        $hashedKey = $this->hashKey($key);

        return $hashedKey === $hash;
    }
}
