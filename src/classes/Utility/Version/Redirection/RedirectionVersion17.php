<?php

namespace Biller\PrestaShop\Utility\Version\Redirection;

use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;
use Context;

/**
 * Class RedirectionVersion17. Used for redirection and generating order page url.
 * Used for versions from 1.7.0.0 to 1.7.7.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Redirection
 */
class RedirectionVersion17 implements RedirectionVersionInterface
{
    /**
     * @inheritDoc
     *
     * @throws \PrestaShopException
     */
    public function generateOrderPageUrl($order)
    {
        return Context::getContext()->link->
            getAdminLink('AdminOrders', false) . '&id_order=' . $order->id . '&token=' . Tools::getAdminTokenLite(
                'AdminOrders'
            ) . '&vieworder';
    }

    /**
     * @inheritDoc
     */
    public function cancelRedirect()
    {
        Context::getContext()->controller->redirectWithNotifications(
            Context::getContext()->link->getPageLink('order')
        );
    }

    /**
     * @inheritDoc
     */
    public function errorRedirect($message)
    {
        Context::getContext()->controller->errors[] = $message;
        Context::getContext()->controller->redirectWithNotifications(
            Context::getContext()->link->getPageLink('order')
        );
    }

    /**
     * @inheritDoc
     */
    public function paymentErrorRedirect($message)
    {
        Context::getContext()->controller->redirectWithNotifications(
            Context::getContext()->link->getPageLink('order')
        );
    }
}
