<?php

namespace Biller\PrestaShop\Utility\Version\OrderRefundMapper;

use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Refunds\RefundLine;
use Context;
use OrderDetail;
use Tools;

/**
 * Class OrderRefundMapperVersion17.
 * Used for versions from 1.7.0.0 to 1.7.7.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Redirection
 */
class OrderRefundMapperVersion17 extends OrderRefundMapperVersion16
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
    public function getRefundLines($lines, $quantity = array(), $isFullRefund = null)
    {
        return Tools::getIsset('cancelProduct') ? $this->getFullRefundLines(
            $lines,
            $quantity
        ) : $this->getPartialRefundLines($lines, $quantity);
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
                            $amount['amount'],
                            $orderDetail->getTaxCalculator()->getTotalRate()
                        ),
                        $this->getCurrency()
                    ),
                    Amount::fromFloat($amount['amount'], $this->getCurrency())
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
}
