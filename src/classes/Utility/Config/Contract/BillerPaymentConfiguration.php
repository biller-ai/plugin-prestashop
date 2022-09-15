<?php

namespace Biller\PrestaShop\Utility\Config\Contract;

/**
 * BillerPaymentConfiguration interface;
 *
 * @package Biller\PrestaShop\Utility\Config\Contract
 */
interface BillerPaymentConfiguration
{
    /**
     * Returns Biller checkout name
     *
     * @return string
     */
    public function getName();

    /**
     * Stores Biller checkout name
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * Returns Biller checkout description
     *
     * @return string
     */
    public function getDescription();

    /**
     * Stores Biller checkout description
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Returns Biller enabled status
     *
     * @return int
     */
    public function getMethodEnabledStatus();

    /**
     * Stores Biller enabled status
     *
     * @param int $enabled
     */
    public function setMethodEnabledStatus($enabled);
}
