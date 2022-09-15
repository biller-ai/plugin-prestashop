<?php

namespace Biller\PrestaShop\Utility\Config;

use Biller\Domain\Order\Status;
use Biller\PrestaShop\Repositories\ConfigurationRepository;
use OrderState;

/**
 * BillerOrderStatusMapping class.
 *
 * @package Biller\PrestaShop\Utility\Config
 */
class BillerOrderStatusMapping implements Contract\BillerOrderStatusMapping
{
    /** @var string[] Default Mapping of Biller order statuses to ids of PrestaShop order states. */
    const DEFAULT_ORDER_STATUS_MAP = array(
        Status::BILLER_STATUS_PENDING => 12,
        Status::BILLER_STATUS_ACCEPTED => 3,
        Status::BILLER_STATUS_REFUNDED => 7,
        Status::BILLER_STATUS_CAPTURED => 4,
        Status::BILLER_STATUS_FAILED => 8,
        Status::BILLER_STATUS_REJECTED => 8,
        Status::BILLER_STATUS_CANCELLED => 6,
    );

    /**
     * @inheritDoc
     */
    public function getAvailableStatuses()
    {
        return OrderState::getOrderStates(\Context::getContext()->language->getId());
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusMap()
    {
        return json_decode(ConfigurationRepository::getValue(Config::BILLER_ORDER_STATUS_MAP_KEY), true);
    }

    /**
     * @inheritDoc
     */
    public function saveOrderStatusMap($orderStatusMap)
    {
        ConfigurationRepository::updateValue(Config::BILLER_ORDER_STATUS_MAP_KEY, json_encode($orderStatusMap));
    }
}
