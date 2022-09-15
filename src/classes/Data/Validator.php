<?php

namespace Biller\PrestaShop\Data;

use BillerPaymentModuleFrontController;
use PrestaShop\PrestaShop\Adapter\Entity\Address;
use Tools;

class Validator
{
    /**
     * @param BillerPaymentModuleFrontController $controller
     * @param \Cart $cart
     * @return void
     */
    public static function validate($controller, $cart)
    {
        $address = new Address($cart->id_address_delivery);
        Validator::validateCompanyName($controller, $address);
        Validator::validateCompanyNameLength($controller, $address);
        Validator::validateRegistrationNumber($controller);
        Validator::validateVatNumber($controller, $address);
    }

    /**
     * @param BillerPaymentModuleFrontController $controller
     * @param Address $address
     * @return void
     */
    private static function validateVatNumber($controller, $address)
    {
        if ((Tools::getIsset('vat_number') &&
            Tools::getValue('vat_number') &&
            !is_numeric(Tools::getValue('vat_number')))) {
            Validator::redirect($controller, 'Invalid field: Vat number should be a numeric value!');
        } elseif (!Tools::getIsset('vat_number') &&
            isset($address->vat_number) &&
            !is_numeric($address->vat_number)) {
            Validator::redirect($controller, 'Invalid field: Vat number should be a numeric value!');
        }
    }

    /**
     * @param BillerPaymentModuleFrontController $controller
     * @param Address $address
     * @return void
     */
    private static function validateRegistrationNumber($controller)
    {
        if ((Tools::getIsset('registration_number') &&
            Tools::getValue('registration_number') &&
            !is_numeric(Tools::getValue('registration_number')))) {
            Validator::redirect($controller, 'Invalid field: Registration number should be a numeric value!');
        }
    }

    /**
     * @param BillerPaymentModuleFrontController $controller
     * @param Address $address
     * @return void
     */
    private static function validateCompanyName($controller, $address)
    {
        if ((Tools::getIsset('company_name') && !Tools::getValue('company_name'))) {
            Validator::redirect($controller, 'Company name is required!');
        } elseif (!Tools::getIsset('company_name') &&
            !$address->company) {
            Validator::redirect($controller, 'Company name is required!');
        }
    }

    /**
     * @param BillerPaymentModuleFrontController $controller
     * @param Address $address
     * @return void
     */
    private static function validateCompanyNameLength($controller, $address)
    {
        if ((Tools::getIsset('company_name') && strlen(Tools::getValue('company_name')) < 2)) {
            Validator::redirect($controller, 'Company name has to have at least 2 characters!');
        } elseif (!Tools::getIsset('company_name') && strlen($address->company) < 2) {
            Validator::redirect($controller, 'Company name has to have at least 2 characters!!');
        }
    }

    /**
     * @param BillerPaymentModuleFrontController $controller
     * @param string $message
     * @return void
     */
    private static function redirect($controller, $message)
    {
        $controller->errors[] = $controller->module->l($message);
        $controller->redirectWithNotifications(\Context::getContext()->link->getPageLink('order'));
    }
}
