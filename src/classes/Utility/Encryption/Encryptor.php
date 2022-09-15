<?php

namespace Biller\PrestaShop\Utility\Encryption;

use Biller\PrestaShop\Utility\Encryption\Contracts\EncryptionInterface;

class Encryptor
{
    /** @var EncryptionInterface $cipherTool */
    private $cipherTool;

    public function __construct()
    {
        if(version_compare(_PS_VERSION_, '1.7.0.0' , '<')) {
            $this->cipherTool = new Rijndael();
        } else {
            $this->cipherTool = new PhpEncryption();
        }
    }

    /**
     * Returns encrypted key
     *
     * @param string $key
     * @return string
     */
    public function encrypt($key)
    {
        return $this->cipherTool->encrypt($key);
    }

    /**
     * Returns decrypted key
     *
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public function decrypt($key)
    {
        return $this->cipherTool->decrypt($key);
    }
}
