<?php

namespace Biller\PrestaShop\Utility\Config\Contract;

/**
 * Interface BillerPaymentConfiguration interface. For accessing module configuration.
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
     * Returns flag if Biller payment is enabled. Returns 1 if it is enabled, 0 otherwise.
     *
     * @return int
     */
    public function getEnabled();

    /**
     * Set flag if Biller payment is enabled.
     *
     * @param int $enable Flag to be set
     *
     * @return void
     */
    public function setEnabled($enable);
}
