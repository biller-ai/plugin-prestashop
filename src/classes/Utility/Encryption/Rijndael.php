<?php

namespace Biller\PrestaShop\Utility\Encryption;

use Biller\PrestaShop\Utility\Encryption\Contracts\EncryptionInterface;

class Rijndael implements EncryptionInterface
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
