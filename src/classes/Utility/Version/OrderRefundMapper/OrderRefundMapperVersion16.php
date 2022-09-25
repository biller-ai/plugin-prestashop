<?php

namespace Biller\PrestaShop\Utility\Version\OrderRefundMapper;

use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Refunds\RefundLine;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Context;
use OrderDetail;
use OrderSlip;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;

/**
 * Class OrderRefundMapperVersion16. Used from version 1.6.0.14 to 1.7.0.0.
 *
 * @package Biller\PrestaShop\Utility\Version\OrderRefundMapper
 */
class OrderRefundMapperVersion16 extends OrderRefundMapperVersion
{
    /**
     * @inheritDoc
     */
    public function getRefundedStatusLabel()
    {
        return 'Refund';
    }

    /**
     * @inheritDoc
     */
    public function getRefundedAmount($order)
    {
        $amount = 0;

        foreach ($order->getCartProducts() as $product) {
            $resume = OrderSlip::getProductSlipResume($product['id_order_detail']);
            $amount += (!empty($resume['amount_tax_incl'])) ? $resume['amount_tax_incl'] : 0.0;
        }

        foreach (OrderSlip::getOrdersSlip($order->id_customer, $order->id) as $orderSlip) {
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
        return $isFullRefund ? $this->getFullRefundLines(
            $lines,
            $quantity
        ) : $this->getPartialRefundLines($lines, $quantity);
    }


    /**
     * Maps product properties from PrestaShop 1.6 product to RefundLine. Used for Product return action
     *
     * @param int[] $lines array of order detail IDs
     * @param int[] $quantity array of Quantity to be refunded
     *
     * @return RefundLine[]
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getFullRefundLines($lines, $quantity)
    {
        $refundLines = array();
        $cart = Context::getContext()->cart;

        foreach ($lines as $product) {
            $orderDetail = new OrderDetail($product);
            $refundLines[] = new RefundLine(
                $orderDetail->product_id,
                $this->getTaxableAmount(
                    $orderDetail->unit_price_tax_excl * $quantity[$product],
                    $orderDetail->unit_price_tax_incl * $quantity[$product]
                ),
                $quantity[$product],
                $cart->id_address_invoice
            );
        }

        if (Tools::getValue('shippingBack') === 'on') {
            $refundLines[] = $this->generateShippingLine($cart->getTotalShippingCost());
        }

        return $refundLines;
    }

    /**
     * Maps product properties from PrestaShop 1.6 product to RefundLine. Used for Partial refund action.
     *
     * @param int[] $lines array of order detail IDs
     * @param int[] $quantity array of Quantity to be refunded
     *
     * @return RefundLine[]
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getPartialRefundLines($lines, $quantity)
    {
        $refundLines = array();
        $cart = Context::getContext()->cart;

        foreach ($lines as $id => $amount) {
            $orderDetail = new OrderDetail($id);

            $refundLines[] = new RefundLine(
                $orderDetail->product_id,
                TaxableAmount::fromAmounts(
                    Amount::fromFloat(
                        $this->getTaxExclFromTaxIncl(
                            $amount,
                            $orderDetail->getTaxCalculator()->getTotalRate()
                        ),
                        $this->getCurrency()
                    ),
                    Amount::fromFloat($amount, $this->getCurrency())
                ),
                $quantity[$id],
                $cart->id_address_invoice
            );
        }

        if (Tools::getValue('partialRefundShippingCost') != 0) {
            $refundLines[] = $this->generateShippingLine(Tools::getValue('partialRefundShippingCost'));
        }

        return $refundLines;
    }

    /**
     * Returns tax excluded price for given tax included price and tax rate.
     *
     * @param float $taxIncl Price with tax included
     * @param float $taxRate Tax rate given as a whole number
     *
     * @return float
     */
    protected function getTaxExclFromTaxIncl($taxIncl, $taxRate)
    {
        return $taxRate ? $taxIncl / (1 + $taxRate / 100.0) : $taxIncl;
    }
}
