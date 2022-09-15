<?php

namespace Biller\PrestaShop\Utility\Version\OrderRefundMapper;

use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Refunds\RefundLine;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Context;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;

/**
 * Class OrderRefundMapperVersion17. Used from version 1.7.0.0+.
 *
 * @package Biller\PrestaShop\Utility\Version\OrderRefundMapper
 */
class OrderRefundMapperVersion17 extends OrderRefundMapperVersion
{
    /**
     * @inheritDoc
     */
    public function getRefundedStatusLabel()
    {
        return 'Refunded';
    }

    /**
     * @inheritDoc
     */
    public function getRefundedAmount($order)
    {
        $amount = 0;

        foreach ($order->getCartProducts() as $product) {
            $amount += (!empty($product['total_refunded_tax_incl'])) ? $product['total_refunded_tax_incl'] : 0.0;
        }
        foreach (\OrderSlip::getOrdersSlip($order->id_customer, $order->id) as $orderSlip) {
            if ($orderSlip['shipping_cost']) {
                $amount += $orderSlip['total_shipping_tax_incl'];
            }
        }

        return $amount;
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getRefundLines($lines, $quantity = array(), $isFullRefund = null)
    {
        $refundLines = array();
        $cart = Context::getContext()->cart;

        foreach ($lines as $product) {
            $orderDetail = new \OrderDetail((int)$product['id_order_detail']);

            $refundLines[] = new RefundLine(
                $orderDetail->product_id,
                $this->getTaxableAmount($product['total_refunded_tax_excl'], $product['total_refunded_tax_incl']),
                !empty($product['quantity']) ? $product['quantity'] : 0,
                $cart->id_address_invoice
            );
        }

        $shippingAmount = (float)Tools::getValue('cancel_product')['shipping_amount'];

        if ($shippingAmount > 0) {
            $refundLines[] = $this->generateShippingLine($shippingAmount);
        }

        if (Tools::getValue('cancel_product')['shipping']) {
            $refundLines[] = $this->generateShippingLine($cart->getTotalShippingCost());
        }

        return $refundLines;
    }
}
