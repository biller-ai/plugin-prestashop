<?php

namespace Biller\PrestaShop\Utility\Config\Contract;

/**
 * BillerOrderStatusMapping interface.
 *
 * @package Biller\PrestaShop\Utility\Config\Contract
 */
interface BillerOrderStatusMapping
{
    /**
     * Retrieves all available PrestaShop order statuses in the current shop language
     *
     * @return array
     */
    public function getAvailableStatuses();

    /**
     * Returns stored status map if exists, otherwise default mapping should
     *
     * @return array in format {BillerStatusKey => PrestaShopStatusId}
     */
    public function getOrderStatusMap();

    /**
     * @param array $orderStatusMap in format {BillerStatusKey => PrestaShopStatusId}
     *
     * @return mixed
     */
    public function saveOrderStatusMap($orderStatusMap);
}
