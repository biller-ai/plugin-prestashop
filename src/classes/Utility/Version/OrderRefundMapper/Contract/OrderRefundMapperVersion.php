<?php

namespace Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract;

use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Order\OrderRequest\Discount;
use Biller\Domain\Refunds\RefundLine;
use Context;

/**
 * Class OrderRefundMapperVersion. Contains methods for mapping order refund.
 *
 * @package Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract
 */
abstract class OrderRefundMapperVersion
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * Returns refund status label.
     *
     * @return string
     */
    abstract public function getRefundedStatusLabel();

    /**
     * Returns already refunded amount of order.
     *
     * @param \Order $order
     *
     * @return float
     */
    abstract public function getRefundedAmount($order);

    /**
     * Handles partial refund.
     *
     * @param array $lines
     * @param array $quantity
     * @param bool|null $isFullRefund
     *
     * @return RefundLine[]
     */
    abstract public function getRefundLines($lines, $quantity = array(), $isFullRefund = null);

    /**
     * Get order discount.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Exception
     */
    public function getDiscount()
    {
        return new Discount('Discount', $this->mapTaxableAmount(Cart::ONLY_DISCOUNTS));
    }

    /**
     * Map taxable amount based on cart price details.
     *
     * @param int $type Cart taxation type
     *
     * @return TaxableAmount Core taxable amount object
     *
     * @throws \Exception
     */
    private function mapTaxableAmount($type)
    {
        $cart = Context::getContext()->cart;

        $amountExclTax = Amount::fromFloat($cart->getOrderTotal(false, $type), $this->getCurrency());
        $amountInclTax = Amount::fromFloat($cart->getOrderTotal(true, $type), $this->getCurrency());

        return TaxableAmount::fromAmounts($amountExclTax, $amountInclTax);
    }

    /**
     * Map currency. If it is already set, return private currency field.
     *
     * @return Currency Core currency object
     *
     * @throws InvalidCurrencyCode
     */
    protected function getCurrency()
    {
        $cart = Context::getContext()->cart;

        if (!$this->currency) {
            $prestaCurrency = new \Currency($cart->id_currency);

            $this->currency = Currency::fromIsoCode($prestaCurrency->iso_code);
        }

        return $this->currency;
    }

    /**
     * Get TaxableAmount for product.
     *
     * @param float $taxExl Price tax excluded
     * @param float $taxIncl Price tax included
     *
     * @return TaxableAmount Core taxable amount object
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     */
    protected function getTaxableAmount($taxExl, $taxIncl)
    {
        $amountExclTax = Amount::fromFloat(
            $taxExl,
            $this->getCurrency()
        );
        $amountInclTax = Amount::fromFloat(
            $taxIncl,
            $this->getCurrency()
        );

        return TaxableAmount::fromAmounts($amountExclTax, $amountInclTax);
    }

    /**
     * Generates refund line for shipping refund.
     *
     * @param float $amount Shipping amount to be refunded
     *
     * @return RefundLine
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     */
    protected function generateShippingLine($amount)
    {
        $cart = Context::getContext()->cart;

        return new RefundLine(
            'Shipping_cost',
            TaxableAmount::fromAmountInclTax(
                Amount::fromFloat($amount, $this->getCurrency())
            ),
            1,
            $cart->id_address_invoice
        );
    }
}
