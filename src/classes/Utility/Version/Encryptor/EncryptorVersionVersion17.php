<?php

namespace Biller\PrestaShop\Utility\Version\Encryptor;

use Biller\PrestaShop\Utility\Version\Encryptor\Contract\EncryptorVersionInterface;
use PhpEncryptionCore;

/**
 * Class EncryptorVersionVersion17. Used for encryption and decryption for PrestaShop 1.7+.
 *
 * @package Biller\PrestaShop\Utility\Version\Encryptor
 */
class EncryptorVersionVersion17 implements EncryptorVersionInterface
{
    /** @var PhpEncryptionCore $cipherTool */
    private $cipherTool;

    public function __construct()
    {
        $this->cipherTool = new PhpEncryptionCore(_NEW_COOKIE_KEY_);
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
     *
     * @throws \Exception
     */
    public function decrypt($key)
    {
        return $this->cipherTool->decrypt($key);
    }
}
