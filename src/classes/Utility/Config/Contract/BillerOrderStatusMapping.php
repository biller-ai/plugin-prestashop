<?php

namespace Biller\PrestaShop\Utility\Config\Contract;

use Biller\Domain\Order\Status;

/**
 * Interface BillerOrderStatusMapping. For accessing order status mapping.
 *
 * @package Biller\PrestaShop\Utility\Config\Contract
 */
interface BillerOrderStatusMapping
{
    /** @var string[] Default Mapping of Biller order statuses to ids of PrestaShop order states. */

    const PRESTA_ORDER_STATUS_MAP = array(
        Status::BILLER_STATUS_PENDING,
        Status::BILLER_STATUS_ACCEPTED,
        Status::BILLER_STATUS_REFUNDED,
        Status::BILLER_STATUS_CAPTURED,
        Status::BILLER_STATUS_FAILED,
        Status::BILLER_STATUS_REJECTED,
        Status::BILLER_STATUS_CANCELLED,
        Status::BILLER_STATUS_PARTIALLY_REFUNDED,
        Status::BILLER_STATUS_PARTIALLY_CAPTURED,
    );

    /**
     * @return string
     */
    public static function getOrderStatusLabel($orderStatus);

    /**
     * Retrieves all available PrestaShop order statuses in the current shop language.
     *
     * @return array Array of available PrestaShop statuses
     */
    public function getAvailableStatuses();

    /**
     * Returns stored status map if exists, otherwise default mapping should.
     *
     * @return array in format {BillerStatusKey => PrestaShopStatusId}
     */
    public function getOrderStatusMap();

    /**
     * Save order status map in configuration.
     *
     * @param array $orderStatusMap in format {BillerStatusKey => PrestaShopStatusId}
     *
     * @return bool Save status
     */
    public function saveOrderStatusMap($orderStatusMap);
}
