<?php

namespace Biller\PrestaShop\Utility;

/**
 * Class Response. Utility class for sending formatted JSON responses to client.
 *
 * @package Biller\PrestaShop\Utility
 */
class Response
{
    /**
     * Dies with status 200 and supplied data as response.
     *
     * @param array $data Array to be encoded to json response
     */
    public static function die200(array $data = array())
    {
        header('HTTP/1.1 200 OK');

        self::dieJson($data);
    }

    /**
     * Dies with status 400 and supplied data as response.
     *
     * @param array $data Array to be encoded to json response
     */
    public static function die400(array $data = array())
    {
        header('HTTP/1.1 400 Bad Request');

        self::dieJson($data);
    }

    /**
     * Sets response header content type to json, echoes supplied $data as json and terminates the process.
     *
     * @param array $data Array to be encoded to json response
     */
    private static function dieJson(array $data = array())
    {
        header('Content-Type: application/json');

        die(json_encode($data));
    }
}
