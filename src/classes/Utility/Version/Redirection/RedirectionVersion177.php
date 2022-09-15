<?php

namespace Biller\PrestaShop\Utility\Version\Redirection;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

/**
 * Class RedirectionVersion177. Used for redirection and generating order page url.
 * Used for versions 1.7.7.0+.
 *
 * @package Biller\PrestaShop\Utility\Version\Redirection
 */
class RedirectionVersion177 extends RedirectionVersion17
{
    /**
     * @inheritDoc
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function generateOrderPageUrl($order)
    {
        return SymfonyContainer::getInstance()->get('router')
            ->generate('admin_orders_view', ['orderId' => $order->id]);
    }
}
