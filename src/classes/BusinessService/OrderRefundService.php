<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Refunds\RefundCollection;
use Biller\BusinessLogic\Integration\Refund\OrderRefundService as OrderRefundServiceInterface;
use Biller\Domain\Refunds\RefundLine;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Exception;
use Order;
use OrderDetail;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class OrderRefundService. For refunding an order in PrestaShop.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class OrderRefundService implements OrderRefundServiceInterface
{
    /**
     * @inheritDoc
     * @throws \Biller\Domain\Exceptions\InvalidTaxPercentage
     */
    public function refund($externalExternalOrderUUID, RefundCollection $billerRefunds = null)
    {
        $billerRefunds ?
            $this->executePartialRefund(
                $externalExternalOrderUUID,
                $billerRefunds->getItems(),
                $billerRefunds->getTotalRefunded()->getAmountInclTax()
            ) :
            $this->executeFullRefund($externalExternalOrderUUID);
    }

    /**
     * Issues a full order refund for order given by it's cart's Id.
     *
     * @param int $cartId Id of the cart whose order is to be fully refunded
     *
     * @return void
     */
    private function executeFullRefund($cartId)
    {
        try {
            $order = new Order(Order::getOrderByCartId($cartId));
            $orderDetails = $order->getOrderDetailList();

            foreach ($orderDetails as $orderDetail) {
                $orderDetail = new OrderDetail($orderDetail['id_order_detail']);

                $orderDetail->product_quantity_refunded = $orderDetail->product_quantity;
                $orderDetail->total_refunded_tax_excl = $orderDetail->total_price_tax_excl;
                $orderDetail->total_refunded_tax_incl = $orderDetail->total_price_tax_incl;

                $orderDetail->update();
            }
        } catch (Exception $exception) {
            Logger::logError($exception->getMessage());
        }
    }

    /**
     * Issues a partial order refund specified by the $billerRefunds parameter for order given by its cart's Id if the.
     * given total refunded amount doesn't match the total refunded amount in the shop.
     *
     * @param int $cartId Id of the cart whose order is to be partially refunded
     * @param RefundLine[] $billerRefunds Refunds to be applied
     * @param Amount $totalAmountRefunded Total amount refunded on the Biller portal
     *
     * @return void
     */
    private function executePartialRefund($cartId, array $billerRefunds, $totalAmountRefunded)
    {
        try {
            $order = new Order(Order::getOrderByCartId($cartId));

            if (!$this->isRefundNeeded($order, $totalAmountRefunded)) {
                return;
            }

            $orderDetails = $order->getOrderDetailList();

            foreach ($billerRefunds as $billerRefund) {
                $orderDetail = $this->getOrderDetailByProductId($orderDetails, $billerRefund->getProductId());

                if (!$orderDetail) {
                    $orderSlip = new \OrderSlip();
                    $orderSlip->id_customer = $order->id_customer;
                    $orderSlip->id_order = $order->id;
                    $totalShippingTaxIncl = 0;
                    $totalShippingTaxExcl = 0;
                    foreach (\OrderSlip::getOrdersSlip($order->id_customer, $order->id) as $slip) {
                        if ($slip['shipping_cost']) {
                            $totalShippingTaxIncl += $slip['total_shipping_tax_incl'];
                            $totalShippingTaxExcl += $slip['total_shipping_tax_excl'];
                        }
                    }
                    $orderSlip->total_shipping_tax_incl = $billerRefund->getAmount()->getAmountInclTax(
                        )->getPriceInCurrencyUnits() - $totalShippingTaxIncl;
                    $orderSlip->total_shipping_tax_excl = $billerRefund->getAmount()->getAmountExclTax(
                        )->getPriceInCurrencyUnits() - $totalShippingTaxExcl;
                    $orderSlip->conversion_rate = 1.0;
                    $orderSlip->total_products_tax_excl = $order->getTotalProductsWithoutTaxes();
                    $orderSlip->total_products_tax_incl = $order->getTotalProductsWithTaxes();
                    $orderSlip->shipping_cost = 1;
                    $orderSlip->add();

                    return;
                }

                $orderDetail->product_quantity_refunded =
                    $orderDetail->product_quantity - $billerRefund->getRefundableQuantity();
                $orderDetail->total_refunded_tax_excl =
                    $billerRefund->getAmount()->getAmountExclTax()->getPriceInCurrencyUnits();
                $orderDetail->total_refunded_tax_incl =
                    $billerRefund->getAmount()->getAmountInclTax()->getPriceInCurrencyUnits();

                $orderDetail->update();
            }
        } catch (Exception $exception) {
            Logger::logError($exception->getMessage());
        }
    }

    /**
     * Finds and returns an OrderDetail object whose product's id matches the passed $productId.
     *
     * @param OrderDetail[] $orderDetails Array of OrderDetail objects
     * @param int $productId Product ID
     *
     * @return OrderDetail|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getOrderDetailByProductId(array $orderDetails, $productId)
    {
        foreach ($orderDetails as $orderDetail) {
            if ($orderDetail['product_id'] == $productId) {
                $orderDetailId = $orderDetail['id_order_detail'];

                return new OrderDetail($orderDetailId);
            }
        }

        return null;
    }

    /**
     * Checks if the refund should be executed by comparing the given current total refunded amount for the order on
     * the Biller portal and the total refunded amount for the order in the shop. If they are the same the refund should
     * not be done.
     * In case refund is done from shop, biller will send webhook for refund.
     * In that case refunded amounts from shop and biller portal are the same.
     *
     * @param Order $order Order to possibly be refunded
     * @param Amount $totalRefundedAmount Total refunded amount for the given order in the Biller portal
     *
     * @return bool True if the refund should be done, otherwise false
     *
     * @throws InvalidCurrencyCode
     * @throws CurrencyMismatchException
     */
    private function isRefundNeeded($order, $totalRefundedAmount)
    {
        $refundedOnPresta = $this->getOrderRefundMapperVersion()->getRefundedAmount($order);
        $orderCurrency = new \Currency($order->id_currency);
        $refundedPrestaAmount = Amount::fromFloat($refundedOnPresta, Currency::fromIsoCode($orderCurrency->iso_code));
        $diffAmount = $totalRefundedAmount->minus($refundedPrestaAmount);

        return !($diffAmount->getAmount() === 0);
    }

    /**
     * Returns OrderRefundMapper class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 OrderRefundMapperVersion16 is returned.
     * For versions from 1.7.0.0+ OrderRefundMapperVersion177 is returned.
     *
     * @return OrderRefundMapperVersion
     */
    private function getOrderRefundMapperVersion()
    {
        return ServiceRegister::getService(OrderRefundMapperVersion::class);
    }
}
