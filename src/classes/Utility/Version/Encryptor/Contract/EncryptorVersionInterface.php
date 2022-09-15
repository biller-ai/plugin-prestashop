<?php

namespace Biller\PrestaShop\Utility\Version\Encryptor\Contract;

/**
 * Contains methods for encryption that vary by PrestaShop versions.
 */
interface EncryptorVersionInterface
{
    /**
     * Returns encrypted key
     *
     * @param string $key Value to be encrypted
     * @return string
     */
    public function encrypt($key);

    /**
     * Returns decrypted key
     *
     * @param string $key Value to be decrypted
     * @return string
     */
    public function decrypt($key);
}
