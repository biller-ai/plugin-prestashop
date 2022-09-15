<?php

namespace Biller\PrestaShop\Utility\Encryption\Contracts;

interface EncryptionInterface
{

    /**
     * Returns encrypted key
     *
     * @param string $key
     * @return string
     */
    public function encrypt($key);

    /**
     * Returns decrypted key
     *
     * @param $key
     * @return string
     */
    public function decrypt($key);
}
