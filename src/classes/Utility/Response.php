<?php

namespace Biller\PrestaShop\Utility;

/**
 * Response class.
 *
 * @package Biller\PrestaShop\Utility
 */
class Response
{
    /**
     * Sets response header content type to json, echoes supplied $data as json and terminates the process.
     *
     * @param array $data Array to be encoded to json response
     */
    public static function dieJson(array $data = array())
    {
        header('Content-Type: application/json');

        die(json_encode($data));
    }
}