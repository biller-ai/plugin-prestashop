<?php

namespace Biller\PrestaShop\Data;

use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Order\OrderRequest;
use Biller\Domain\Order\OrderRequest\Locale;
use Biller\Domain\Exceptions\InvalidLocale;
use Biller\Domain\Order\OrderRequestFactory;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Order\OrderRequest\Address;
use Biller\Domain\Order\OrderRequest\Country;
use Biller\Domain\Exceptions\InvalidCountryCode;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Biller\Domain\Order\OrderRequest\Buyer;
use Biller\Domain\Order\OrderRequest\Company;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Order\OrderRequest\OrderLines;
use Biller\Domain\Order\OrderRequest\OrderLine;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Exceptions\InvalidTypeException;
use Biller\Domain\Order\OrderRequest\Discount;
use Biller\Domain\Order\OrderRequest\PaymentLinkDuration;
use Cart;
use Context;
use Exception;
use PrestaShopDatabaseException;
use PrestaShopException;
use Product;

/**
 * Class OrderMapper. Used for mapping PrestaShop order data to Biller order.
 *
 * @package Biller\PrestaShop\Data
 */
class OrderMapper
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var string
     */
    private $webshopUID;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var string[]
     */
    private $responseURLs;

    /**
     * @var string[]
     */
    private $companyData;

    /**
     * @param Cart $cart
     * @param string $webshopUID
     * @param string[] $responseURLs
     * @param string[] $companyData
     */
    public function __construct($cart, $webshopUID, $responseURLs, $companyData)
    {
        $this->cart = $cart;
        $this->webshopUID = $webshopUID;
        $this->responseURLs = $responseURLs;
        $this->companyData = $companyData;
    }

    /**
     * Map order details from PrestaShop to Biller core classes.
     *
     * @return OrderRequest Created core order request object
     *
     * @throws CurrencyMismatchException
     * @throws InvalidArgumentException
     * @throws InvalidCountryCode
     * @throws InvalidCurrencyCode
     * @throws InvalidLocale
     * @throws InvalidTaxPercentage
     * @throws InvalidTypeException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function mapOrder()
    {
        $locale = $this->mapLocale();
        $orderId = $this->cart->id;
        $amount = Amount::fromFloat($this->cart->getOrderTotal(), $this->getCurrency());
        $shippingAddress = $this->mapAddress($this->cart->id_address_delivery);
        $billingAddress = $this->mapAddress($this->cart->id_address_invoice);
        $buyer = $this->mapBuyer();
        $company = $this->mapCompany();
        $orderLines = $this->mapOrderLines();
        $discount = $this->mapDiscount();
        return $this->makeOrderRequest(
            $this->webshopUID,
            $orderId,
            $amount,
            $orderLines,
            $company,
            $buyer,
            $shippingAddress,
            $billingAddress,
            $locale,
            $discount,
            null,
            $this->responseURLs['success'],
            $this->responseURLs['error'],
            $this->responseURLs['cancel'],
            $this->responseURLs['webhooks']
        );
    }

    /**
     * Create order using factory class from core.
     *
     * @param string $webshopUID
     * @param string $orderId
     * @param Amount $amount
     * @param OrderLines $orderLines
     * @param Company $company
     * @param Buyer $buyer
     * @param Address $shippingAddress
     * @param Address $billingAddress
     * @param Locale $locale
     * @param Discount $discount
     * @param PaymentLinkDuration|null $paymentLinkDuration
     * @param string $successURL
     * @param string $errorURL
     * @param string $cancelURL
     * @param string $webhookURL
     *
     * @return OrderRequest|null
     *
     * @throws CurrencyMismatchException
     * @throws InvalidArgumentException
     * @throws InvalidTaxPercentage
     */
    private function makeOrderRequest(
        $webshopUID,
        $orderId,
        $amount,
        $orderLines,
        $company,
        $buyer,
        $shippingAddress,
        $billingAddress,
        $locale,
        $discount,
        $paymentLinkDuration,
        $successURL,
        $errorURL,
        $cancelURL,
        $webhookURL
    ) {
        $factory = new OrderRequestFactory();
        $factory->setExternalWebshopUID($webshopUID);
        $factory->setExternalOrderUID($orderId);
        $factory->setAmount($amount);
        foreach ($orderLines as $orderLine) {
            $factory->addOrderLine($orderLine);
        }
        $factory->setBuyerCompany($company);
        $factory->setBuyerRepresentative($buyer);
        $factory->setShippingAddress($shippingAddress);
        $factory->setBillingAddress($billingAddress);
        $factory->setLocale($locale);
        $factory->addDiscount($discount);
        $factory->setPaymentLinkDuration($paymentLinkDuration);
        $factory->setSuccessUrl($successURL);
        $factory->setErrorUrl($errorURL);
        $factory->setCancelUrl($cancelURL);
        $factory->setWebhookUrl($webhookURL);

        return $factory->create();
    }

    /**
     * Map locale based on context language locale.
     *
     * @return Locale Locale to be mapped
     *
     * @throws InvalidLocale
     */
    private function mapLocale()
    {
        try {
            $locale = Locale::fromCode(Context::getContext()->language->locale);
        } catch (InvalidLocale $exception) {
            $locale = Locale::getDefault();
        }

        return $locale;
    }

    /**
     * Map discount amount.
     *
     * @return Discount Discount to be mapped
     *
     * @throws Exception
     */
    private function mapDiscount()
    {
        return new Discount('Discount', $this->mapTaxableAmount(Cart::ONLY_DISCOUNTS));
    }

    /**
     * Map currency. If it is already set, return private currency field.
     *
     * @return Currency
     *
     * @throws InvalidCurrencyCode
     */
    private function getCurrency()
    {
        if (!$this->currency) {
            $prestaCurrency = new \Currency($this->cart->id_currency);

            $this->currency = Currency::fromIsoCode($prestaCurrency->iso_code);
        }

        return $this->currency;
    }

    /**
     * Map all products from cart and shipping order line.
     *
     * @return OrderLines Order lines to be mapped
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     * @throws InvalidTypeException
     * @throws Exception
     */
    private function mapOrderLines()
    {
        $orderLines = array();

        foreach ($this->cart->getProducts() as $orderLine) {
            $orderLines[] = $this->mapOrderLine($orderLine);
        }

        if ($this->cart->id_carrier) {
            $orderLines[] = $this->getShippingOrderLine();
        }

        return new OrderLines($orderLines);
    }

    /**
     * Returns order line for shipping details.
     *
     * @return OrderLine
     *
     * @throws Exception
     */
    private function getShippingOrderLine()
    {
        $carrier = new \Carrier($this->cart->id_carrier);
        $costWithTaxes = $this->cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $costWithoutTaxes = $this->cart->getOrderTotal(false, Cart::ONLY_SHIPPING);

        return new OrderLine(
            'Shipping_cost',
            !empty($carrier->name) ? $carrier->name : '',
            TaxableAmount::fromAmountInclTax(
                Amount::fromFloat($costWithTaxes, $this->getCurrency())
            ),
            ($costWithTaxes) ? (string)(($costWithTaxes - $costWithoutTaxes) / $costWithTaxes) : 0.0
        );
    }

    /**
     * Map taxable amount based on cart price details.
     *
     * @param int $type
     *
     * @return TaxableAmount Core taxable amount object
     *
     * @throws Exception
     */
    private function mapTaxableAmount($type)
    {
        $amountExclTax = Amount::fromFloat($this->cart->getOrderTotal(false, $type), $this->getCurrency());
        $amountInclTax = Amount::fromFloat($this->cart->getOrderTotal(true, $type), $this->getCurrency());

        return TaxableAmount::fromAmounts($amountExclTax, $amountInclTax);
    }

    /**
     * Map one order line.
     *
     * @param array $orderLine Array of all order lines
     *
     * @return OrderLine Core order line object
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     */
    private function mapOrderLine($orderLine)
    {
        $product = new Product($orderLine['id_product']);

        return new OrderLine(
            !empty($orderLine['id_product']) ? (string)$orderLine['id_product'] : '',
            !empty($product->name[Context::getContext()->language->id]) ? $product->name[Context::getContext(
            )->language->id] : '',
            $this->mapOrderLineTaxableAmount($orderLine),
            !empty($orderLine['rate']) ? (string)$orderLine['rate'] : '0',
            !empty($orderLine['quantity']) ? $orderLine['quantity'] : 1,
            !empty($product->description[Context::getContext()->language->id])
                ? $product->description[Context::getContext()->language->id] : null
        );
    }

    /**
     * Map taxable amount of one order line.
     *
     * @param array $orderLine Array of all order lines
     *
     * @return TaxableAmount Core taxable amount object
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     */
    private function mapOrderLineTaxableAmount($orderLine)
    {
        $amountExclTax = Amount::fromFloat(
            !empty($orderLine['price']) ? $orderLine['price'] : 0,
            $this->getCurrency()
        );
        $amountInclTax = Amount::fromFloat(
            !empty($orderLine['price_wt']) ? $orderLine['price_wt'] : 0,
            $this->getCurrency()
        );

        return TaxableAmount::fromAmounts($amountExclTax, $amountInclTax);
    }

    /**
     * Map company data.
     *
     * @return Company Core company object
     *
     * @throws InvalidArgumentException
     */
    private function mapCompany()
    {
        $customer = new \Customer($this->cart->id_customer);

        return new Company(
            !empty($this->companyData['companyName']) ? $this->companyData['companyName'] : '',
            !empty($this->companyData['registrationNumber']) ? $this->companyData['registrationNumber'] : null,
            !empty($this->companyData['vatNumber']) ? $this->companyData['vatNumber'] : null,
            !empty($customer->website) ? $customer->website : null
        );
    }

    /**
     * Map address data.
     *
     * @param int $id Presta address Id
     *
     * @return Address Core address object
     *
     * @throws InvalidArgumentException
     * @throws InvalidCountryCode
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function mapAddress($id)
    {
        $prestaAddress = new \Address($id);
        $prestaCountry = new \Country($prestaAddress->id_country);
        $state = new \State($prestaAddress->id_state);
        $country = Country::fromIsoCode($prestaCountry->iso_code);

        return new Address(
            !empty($prestaAddress->city) ? $prestaAddress->city : '',
            !empty($prestaAddress->postcode) ? $prestaAddress->postcode : '',
            $country,
            !empty($prestaAddress->address1) ? $prestaAddress->address1 : null,
            !empty($prestaAddress->address2) ? $prestaAddress->address2 : null,
            !empty($state->name) ? $state->name : null
        );
    }

    /**
     * Map customer data.
     *
     * @return Buyer Core buyer object
     *
     * @throws InvalidArgumentException
     */
    private function mapBuyer()
    {
        $addressDelivery = new \Address($this->cart->id_address_delivery);
        $phoneNumber = $addressDelivery->phone ?: $addressDelivery->phone_mobile;
        $customer = new \Customer($this->cart->id_customer);

        return new Buyer(
            !empty($addressDelivery->firstname) ? $addressDelivery->firstname : '',
            !empty($addressDelivery->lastname) ? $addressDelivery->lastname : '',
            !empty($customer->email) ? $customer->email : '',
            !empty($phoneNumber) ? $phoneNumber : null
        );
    }
}
