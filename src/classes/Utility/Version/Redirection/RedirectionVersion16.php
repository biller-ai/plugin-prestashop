<?php

namespace Biller\PrestaShop\Utility\Version\Redirection;

use Biller\PrestaShop\Utility\FlashBag;
use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;
use Context;
use Tools;

/**
 * Class RedirectionVersion16. Used for redirection and generating order page url.
 * Used for versions from 1.6.0.14 to 1.7.0.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Redirection
 */
class RedirectionVersion16 implements RedirectionVersionInterface
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
        Tools::redirect($this->generateOrderURL());
    }

    /**
     * @inheritDoc
     */
    public function errorRedirect($message)
    {
        FlashBag::getInstance()->setMessage('error', $message);
        Tools::redirect($this->generateOrderURL());
    }

    /**
     * @inheritDoc
     */
    public function paymentErrorRedirect($message)
    {
        FlashBag::getInstance()->setMessage('error', implode(' ', $message));
        Tools::redirect($this->generateOrderURL());
    }

    /**
     * Create URL to order page on payment tab.
     *
     * @return string URL for order page
     */
    private function generateOrderURL()
    {
        return Context::getContext()->link->getPageLink(
            'order',
            true,
            null,
            array(
                'step' => 3
            )
        );
    }
}
