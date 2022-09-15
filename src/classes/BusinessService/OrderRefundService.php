<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\Domain\Refunds\RefundCollection;
use Biller\BusinessLogic\Integration\Refund\OrderRefundService as OrderRefundServiceInterface;
use Biller\Domain\Refunds\RefundLine;
use Biller\Infrastructure\Logger\Logger;
use Exception;
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
     */
    public function refund($externalExternalOrderUUID, RefundCollection $billerRefunds = null)
    {
        $billerRefunds ?
            $this->executePartialRefund($externalExternalOrderUUID, $billerRefunds->getItems()) :
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
            $order = new \Order(\Order::getOrderByCartId($cartId));
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
     * Issues a partial order refund specified by the $billerRefunds parameter for order given by it's cart's Id.
     *
     * @param int $cartId Id of the cart whose order is to be partially refunded
     * @param RefundLine[] $billerRefunds Refunds to be made
     *
     * @return void
     */
    private function executePartialRefund($cartId, array $billerRefunds)
    {
        try {
            $order = new \Order(\Order::getOrderByCartId($cartId));
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
     * @param int $productId Product Id
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
}
