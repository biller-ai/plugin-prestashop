<?php

namespace Biller\PrestaShop\Data;

use Module;
use Tools;

/**
 * Class Validator. Used for validation of Biller input fields on checkout page.
 *
 * @package Biller\PrestaShop\Data
 */
class Validator
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'Validator';

    /**
     * @var array Array of error messages
     */
    private static $errors;

    /**
     * Returns array of errors.
     *
     * @return array Array of error messages
     */
    public static function getErrors()
    {
        return self::$errors;
    }

    /**
     * Validate order details - company name, vat number and registration number.
     *
     * @return bool True if inputs are valid, false otherwise
     */
    public static function validate()
    {
        self::isCompanyNameValid();
        self::isCompanyNameLengthValid();
        self::isRegistrationNumberValid();
        self::isVatNumberValid();

        if (
            !empty(self::$errors)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Validates vat number format if vat number is set.
     *
     * @return void
     */
    private static function isVatNumberValid()
    {
        if (
            Tools::getIsset('vat_number') &&
            Tools::getValue('vat_number') &&
            !is_numeric(Tools::getValue('vat_number'))
        ) {
            self::addError(Module::getInstanceByName('biller')->l('Invalid field: Vat number should be a numeric value.', self::FILE_NAME));
        }
    }

    /**
     * Validates registration number format if vat number is set.
     *
     * @return void
     */
    private static function isRegistrationNumberValid()
    {
        if (
            Tools::getIsset('registration_number') &&
            Tools::getValue('registration_number') &&
            !is_numeric(Tools::getValue('registration_number'))
        ) {
            self::addError(Module::getInstanceByName('biller')->l('Registration number should be a numeric value.', self::FILE_NAME));
        }
    }

    /**
     * Validates if company name is set. Company name is required.
     *
     * @return void
     */
    private static function isCompanyNameValid()
    {
        if (
            Tools::getIsset('company_name') &&
            !Tools::getValue('company_name')
        ) {
            self::addError(Module::getInstanceByName('biller')->l('Company name cannot be empty.', self::FILE_NAME));
        }
    }

    /**
     * Validates if company name has at least 2 characters.
     *
     * @return void
     */
    private static function isCompanyNameLengthValid()
    {
        if (
            Tools::getIsset('company_name')
            && strlen(Tools::getValue('company_name')) < 2
        ) {
            self::addError(Module::getInstanceByName('biller')->l('Company name has to have at least 2 characters!', self::FILE_NAME));
        }
    }

    /**
     * Adds new error message.
     *
     * @param string $error Error message to be added
     *
     * @return void
     */
    private static function addError($error)
    {
        self::$errors[] = $error;
    }
}
