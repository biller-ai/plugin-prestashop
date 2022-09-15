<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\BusinessLogic\Integration\Shipment\ShipmentService as ShipmentServiceInterface;
use Biller\BusinessLogic\Integration\ShipmentRequest;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Order\OrderRequest\Discount;
use Biller\PrestaShop\Exception\BillerCaptureRejectedException;
use Exception;
use Order;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\ChangeOrderStatusException;
use PrestaShop\PrestaShop\Core\Domain\Order\ValueObject\OrderId;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;

/**
 * Class ShipmentService. Handles capture rejection for PrestaShop and creation of ShipmentRequest.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class ShipmentService implements ShipmentServiceInterface
{
    /** @var /Cart */
    private $cart;

    /**
     * @inheritDoc
     *
     * @throws ChangeOrderStatusException
     * @throws OrderException
     * @throws BillerCaptureRejectedException
     */
    public function reject($request, Exception $reason)
    {
        throw new BillerCaptureRejectedException($reason->getMessage());
    }

    /**
     * Creates shipment request for capturing for the given order.
     *
     * @param Order $order Order object
     *
     * @return ShipmentRequest Core shipment request object
     *
     * @throws InvalidCurrencyCode
     * @throws Exception
     */
    public function createShipmentRequest($order)
    {
        $this->cart = new \Cart($order->id_cart);

        return new ShipmentRequest(
            $order->id_cart,
            null,
            $this->getDiscount(),
            Amount::fromFloat($order->getTotalPaid(), $this->getCurrency())
        );
    }

    /**
     * Map discount amount.
     *
     * @return Discount Core discount object
     *
     * @throws Exception
     */
    private function getDiscount()
    {
        return new Discount('Discount', $this->getTaxableAmount(\Cart::ONLY_DISCOUNTS));
    }

    /**
     * Map taxable amount based on cart price details.
     *
     * @param int $type Cart taxation type
     *
     * @return TaxableAmount Core taxable amount object
     *
     * @throws Exception
     */
    private function getTaxableAmount($type)
    {
        $amountExclTax = Amount::fromFloat($this->cart->getOrderTotal(false, $type), $this->getCurrency());
        $amountInclTax = Amount::fromFloat($this->cart->getOrderTotal(true, $type), $this->getCurrency());

        return TaxableAmount::fromAmounts($amountExclTax, $amountInclTax);
    }

    /**
     * Get currency from cart.
     *
     * @return Currency Core currency object
     *
     * @throws InvalidCurrencyCode
     */
    private function getCurrency()
    {
        $prestaCurrency = new \Currency($this->cart->id_currency);

        return Currency::fromIsoCode($prestaCurrency->iso_code);
    }
}
