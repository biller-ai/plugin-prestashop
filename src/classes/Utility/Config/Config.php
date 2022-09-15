<?php

namespace Biller\PrestaShop\Utility\Config;

/**
 * Class Config. Holds configuration data keys as well as accepted country and currency codes.
 *
 * @package Biller\PrestaShop\Utility\Config
 */
class Config
{
    /** @var string[] List of countries accepted by Biller business invoice. */
    const ACCEPTED_COUNTRY_CODES = array('NL', 'BE', 'GB', 'DK', 'DE', 'AT', 'IT');
    /** @var string[] List of currencies accepted by Biller business invoice. */
    const ACCEPTED_CURENCY_CODES = array('EUR', 'DKK', 'GBP');

    /** @var string Configuration keys. */
    const ENABLE_BUSINESS_INVOICE_KEY = 'ENABLE_BUSINESS_INVOICE_KEY';
    const MODE_KEY = 'MODE_KEY';
    const WEBSHOP_UID_KEY = 'WEBSHOP_UID_KEY';
    const USERNAME_KEY = 'USERNAME_KEY';
    const PASSWORD_KEY = 'PASSWORD_KEY';
    const USER_INFO_LIVE_KEY = 'USER_INFO_LIVE_KEY';
    const USER_INFO_SANDBOX_KEY = 'USER_INFO_SANDBOX_KEY';
    const NAME_KEY = 'NAME_KEY';
    const DESCRIPTION_KEY = 'DESCRIPTION_KEY';
    const ORDER_STATUS_MAP_KEY = 'ORDER_STATUS_MAP_KEY';
}
