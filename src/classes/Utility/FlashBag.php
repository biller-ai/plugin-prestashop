<?php

namespace Biller\PrestaShop\Utility;

/**
 * Class FlashBag. Used for storing and getting messages for PrestaShop 1.6.
 *
 * @package Biller\PrestaShop\Utility
 */
class FlashBag
{
    private static $instance = null;

    /**
     * Get current instance of FlashBag
     *
     * @return FlashBag Current instance of class
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new FlashBag();
        }

        return self::$instance;
    }

    /**
     * Sets message to session/cookie.
     *
     * @param string $type Type of message
     * @param string|array $message Message to be set in session or cookie
     * @return void
     */
    public function setMessage($type, $message)
    {
        setcookie($type, $message, time() + 3600, "/");
    }

    /**
     * Gets message from session/cookie if exists.
     *
     * @param string $type Type of message
     *
     * @return mixed|string
     */
    public function getMessage($type)
    {
        $message = '';
        if (isset($_COOKIE[$type])) {
            $message = $_COOKIE[$type];
            unset($_COOKIE[$type]);
            setcookie($type, '', time() - 3600, '/');
        }

        return $message;
    }
}
