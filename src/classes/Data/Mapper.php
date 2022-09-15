<?php

namespace Biller\PrestaShop\Data;

use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Order\OrderRequest;
use Biller\Domain\Order\OrderRequest\Locale;
use Biller\Domain\Exceptions\InvalidLocale;
use Biller\Domain\Order\OrderRequestFactory;
use Biller\PrestaShop\Repositories\ConfigurationRepository;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use PrestaShop\PrestaShop\Adapter\Entity\Currency as PrestaCurrency;
use PrestaShop\PrestaShop\Adapter\Entity\Address as PrestaAddress;
use PrestaShop\PrestaShop\Adapter\Entity\Country as PrestaCountry;
use PrestaShop\PrestaShop\Adapter\Entity\State as PrestaState;
use PrestaShop\PrestaShop\Adapter\Entity\Customer as PrestaCustomer;
use PrestaShop\PrestaShop\Adapter\Entity\Carrier as PrestaCarrier;
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
use Biller\Domain\Amount\Tax;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Exceptions\InvalidTypeException;
use Biller\Domain\Order\OrderRequest\Discount;
use Biller\Domain\Order\OrderRequest\PaymentLinkDuration;
use Cart;
use Context;
use Exception;
use Tools;
use Product;

/**
 * Class Mapper
 *
 * @package Biller\PrestaShop\Data
 */
class Mapper
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @param Cart $cart
     */
    public function __construct($cart, $module)
    {
        $this->cart = $cart;
        $this->module = $module;
    }

    /**
     * @return OrderRequest
     * @throws CurrencyMismatchException
     * @throws InvalidArgumentException
     * @throws InvalidCountryCode
     * @throws InvalidCurrencyCode
     * @throws InvalidLocale
     * @throws InvalidTaxPercentage
     * @throws InvalidTypeException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function mapOrder()
    {
        $locale = $this->mapLocale();
        $webshopUid = ConfigurationRepository::getWebshopUID();
        $orderId = $this->cart->id;
        $amount = Amount::fromFloat($this->cart->getCartTotalPrice(), $this->getCurrency($this->cart));
        $shippingAddress = $this->mapAddress($this->cart->id_address_delivery);
        $billingAddress = $this->mapAddress($this->cart->id_address_invoice);
        $buyer = $this->mapBuyer($this->cart);
        $company = $this->mapCompany($this->cart);
        $orderLines = $this->mapOrderLines($this->cart);
        $discount = $this->mapDiscount($this->cart);
        return $this->makeOrderRequest($webshopUid,
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
            Context::getContext()->link->getModuleLink($this->module->name, 'success', array(), true),
            Context::getContext()->link->getModuleLink($this->module->name, 'error', array(), true),
            Context::getContext()->link->getModuleLink($this->module->name, 'cancel', array(), true),
            Context::getContext()->link->getModuleLink($this->module->name, 'webhooks', array(), true)
        );
    }

    /**
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
     * @param PaymentLinkDuration $paymentLinkDuration
     * @param string $successURL
     * @param string $errorURL
     * @param string $cancelURL
     * @param string $webhookURL
     * @return OrderRequest|null
     * @throws CurrencyMismatchException
     * @throws InvalidArgumentException
     * @throws InvalidTaxPercentage
     */
    private function makeOrderRequest($webshopUID,
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
                                      $webhookURL)
    {
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
     * @return Locale
     * @throws InvalidLocale
     */
    private function mapLocale()
    {

        try {
            $locale = Locale::fromCode(Context::getContext()->language->getLocale());
        } catch (InvalidLocale $exception) {
            $locale = Locale::getDefault();
        }

        return $locale;
    }

    /**
     * @param Cart $cart
     * @return Discount
     * @throws \Exception
     */
    private function mapDiscount($cart)
    {
        $carrier = new PrestaCarrier($cart->id_carrier);

        return new Discount('Discount', $this->mapTaxableAmount($cart, $carrier, Cart::ONLY_DISCOUNTS));
    }

    /**
     * @param Cart $cart
     * @return Currency
     * @throws InvalidCurrencyCode
     */
    private function getCurrency($cart)
    {
        $prestaCurrency = new PrestaCurrency($cart->id_currency);

        return Currency::fromIsoCode($prestaCurrency->iso_code);
    }

    /**
     * @param $cart
     * @return OrderLines
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     * @throws InvalidTypeException
     * @throws \Exception
     */
    private function mapOrderLines($cart)
    {
        $orderLines = array();

        foreach ($cart->getProducts() as $orderLine) {
            $orderLines[] = $this->mapOrderLine($orderLine, $cart);
        }

        $orderLines[] = $this->getShippingOrderLine($cart);

        return new OrderLines($orderLines);
    }

    /**
     * @param Cart $cart
     * @return OrderLine
     * @throws \Exception
     */
    private function getShippingOrderLine($cart)
    {
        $carrier = new PrestaCarrier($cart->id_carrier);

        return new OrderLine(
            !empty($cart->getPackageShippingCost()) ? (string)$cart->getPackageShippingCost() : '0',
            !empty($carrier->name) ? $carrier->name : '',
            $this->mapTaxableAmount($cart, $carrier, Cart::ONLY_SHIPPING),
            !empty($carrier->getTaxesRate()) ? (string)$carrier->getTaxesRate() : ''
        );
    }


    /**
     * @param Cart $cart
     * @param PrestaCarrier $carrier
     * @param int $type
     * @return TaxableAmount
     * @throws Exception
     */
    private function mapTaxableAmount($cart, $carrier, $type)
    {
        $amountExclTax = Amount::fromFloat($cart->getOrderTotal(false, $type), $this->getCurrency($cart));
        $amountInclTax = Amount::fromFloat($cart->getOrderTotal(true, $type), $this->getCurrency($cart));
        $tax = new Tax(!empty($carrier->getTaxesRate()) ? $carrier->getTaxesRate() : 0.0);

        return new TaxableAmount($amountExclTax, $amountInclTax, $tax);
    }

    /**
     * @param array $orderLine
     * @param Cart $cart
     * @return OrderLine
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     */
    private function mapOrderLine($orderLine, $cart)
    {
        $product = new Product($orderLine['id_product']);

        return new OrderLine(
            !empty($orderLine['id_product']) ? (string)$orderLine['id_product'] : '',
            !empty($product->name[Context::getContext()->language->id]) ? $product->name[Context::getContext()->language->id] : '',
            $this->mapOrderLineTaxableAmount($orderLine, $cart),
            !empty($orderLine['rate']) ? (string)$orderLine['rate'] : '',
            !empty($orderLine['quantity']) ? $orderLine['quantity'] : 1,
            !empty($product->description[Context::getContext()->language->id]) ? $product->description[Context::getContext()->language->id] : null
        );
    }

    /**
     * @param array $orderLine
     * @param Cart $cart
     * @return TaxableAmount
     * @throws InvalidCurrencyCode
     * @throws InvalidTaxPercentage
     */
    private function mapOrderLineTaxableAmount($orderLine, $cart)
    {
        $amountExclTax = Amount::fromFloat(!empty($orderLine['price']) ? $orderLine['price'] : 0, $this->getCurrency($cart));
        $amountInclTax = Amount::fromFloat(!empty($orderLine['price_wt']) ? $orderLine['price_wt'] : 0, $this->getCurrency($cart));
        $tax = new Tax(!empty($orderLine['rate']) ? $orderLine['rate'] : 0.0);

        return new TaxableAmount($amountExclTax, $amountInclTax, $tax);
    }

    /**
     * @param \Cart $cart
     * @return Company
     * @throws InvalidArgumentException
     */
    private function mapCompany($cart)
    {
        $customer = new PrestaCustomer($cart->id_customer);
        $companyData = $this->getCompanyData($cart);

        return new Company(!empty($companyData['companyName']) ? $companyData['companyName'] : '',
            !empty($companyData['registrationNumber']) ? $companyData['registrationNumber'] : null,
            !empty($companyData['vatNumber']) ? $companyData['vatNumber'] : null,
            !empty($customer->website) ? $customer->website : null);
    }

    /**
     * @param Cart $cart
     * @return array
     */
    private function getCompanyData($cart)
    {
        if (Tools::getIsset('company_name') && Tools::getIsset('vat_number') && Tools::getIsset('registration_number')) {

            return array(
                'companyName' => Tools::getValue('company_name'),
                'vatNumber' => Tools::getValue('vat_number'),
                'registrationNumber' => Tools::getValue('registration_number')
            );
        }

        $address = new PrestaAddress($cart->id_address_delivery);

        return array(
            'companyName' => $address->company,
            'vatNumber' => $address->vat_number,
            'registrationNumber' => null
        );
    }

    /**
     * @param int $id
     * @return Address
     * @throws InvalidArgumentException
     * @throws InvalidCountryCode
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function mapAddress($id)
    {
        $prestaAddress = new PrestaAddress($id);
        $prestaCountry = new PrestaCountry($prestaAddress->id_country);
        $state = new PrestaState($prestaAddress->id_state);
        $country = Country::fromIsoCode($prestaCountry->iso_code);

        return new Address(!empty($prestaAddress->city) ? $prestaAddress->city : '',
            !empty($prestaAddress->postcode) ? $prestaAddress->postcode : '',
            $country,
            !empty($prestaAddress->address1) ? $prestaAddress->address1 : null,
            !empty($prestaAddress->address2) ? $prestaAddress->address2 : null,
            !empty($state->name) ? $state->name : null);
    }

    /**
     * @param \Cart $cart
     * @return Buyer
     * @throws InvalidArgumentException
     */
    private function mapBuyer(Cart $cart)
    {
        $addressDelivery = new PrestaAddress($cart->id_address_delivery);
        $phoneNumber = $addressDelivery->phone ?: $addressDelivery->phone_mobile;
        $customer = new PrestaCustomer($cart->id_customer);

        return new Buyer(!empty($addressDelivery->firstname) ? $addressDelivery->firstname : '',
            !empty($addressDelivery->lastname) ? $addressDelivery->lastname : '',
            !empty($customer->email) ? $customer->email : '',
            !empty($phoneNumber) ? $phoneNumber : null);
    }
}
