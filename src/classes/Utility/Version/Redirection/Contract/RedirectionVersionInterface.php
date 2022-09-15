<?php

namespace Biller\PrestaShop\Utility\Version\Redirection\Contract;

/**
 * Contains methods for redirection that vary by PrestaShop versions.
 */
interface RedirectionVersionInterface
{
    /**
     * Returns URL of order page.
     *
     * @param \Order $order
     *
     * @return string
     */
    public function generateOrderPageUrl($order);

    /**
     * Redirects back to checkout page.
     *
     * @return mixed
     */
    public function cancelRedirect();

    /**
     * Redirects back to checkout page with error message.
     *
     * @param string $message Message to be displayed
     *
     * @return mixed
     */
    public function errorRedirect($message);

    /**
     * Redirects back to checkout page after payment fail.
     *
     * @param string $message Message to be displayed
     *
     * @return mixed
     */
    public function paymentErrorRedirect($message);
}
