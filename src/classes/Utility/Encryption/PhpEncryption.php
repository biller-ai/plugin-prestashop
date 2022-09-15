<?php

namespace Biller\PrestaShop\Utility\Encryption;

use Biller\PrestaShop\Utility\Encryption\Contracts\EncryptionInterface;

class PhpEncryption implements EncryptionInterface
{
    /** @var \PhpEncryptionCore $cipherTool */
    private $cipherTool;

    public function __construct()
    {
        $this->cipherTool = new \PhpEncryptionCore(_NEW_COOKIE_KEY_);
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
     * @throws \Exception
     */
    public function decrypt($key)
    {
        return $this->cipherTool->decrypt($key);
    }
}
