<?php

namespace Biller\PrestaShop\Utility\Version\Encryptor;

use Biller\PrestaShop\Utility\Version\Encryptor\Contract\EncryptorVersionInterface;
use RijndaelCore;

/**
 * Class EncryptorVersionVersion16. Used for encryption and decryption from PrestaShop 1.6.0.14 to 1.7.0.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Encryptor
 */
class EncryptorVersionVersion16 implements EncryptorVersionInterface
{
    /** @var RijndaelCore $cipherTool */
    private $cipherTool;

    public function __construct()
    {
        $this->cipherTool = new RijndaelCore(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
    }

    /**
     * @inheritDoc
     */
    public function encrypt($key)
    {
        return $this->cipherTool->encrypt($key);
    }

    /**
     * @inheritDoc
     */
    public function decrypt($key)
    {
        return $this->cipherTool->decrypt($key);
    }
}
