<?php

namespace Biller\PrestaShop\Utility\Config;

/**
 * Config class.
 *
 * @package Biller\PrestaShop\Utility\Config
 */
class Config
{
    /** @var string[] List of countries accepted by Biller business invoice. */
    const ACCEPTED_COUNTRY_CODES = array('NL', 'BE', 'UK', 'DK', 'DE', 'AT', 'IT');
    /** @var string[] List of currencies accepted by Biller business invoice. */
    const ACCEPTED_CURENCY_CODES = array('EUR', 'DKK', 'GBP');

    /** @var string Configuration keys. */
    const BILLER_ENABLE_BUSINESS_INVOICE_KEY = 'BILLER_ENABLE_BUSINESS_INVOICE';
    const BILLER_MODE_KEY = 'BILLER_MODE';
    const BILLER_WEBSHOP_UID_KEY = 'BILLER_WEBSHOP_UID';
    const BILLER_USERNAME_KEY = 'BILLER_USERNAME';
    const BILLER_PASSWORD_KEY = 'BILLER_PASSWORD';
    const BILLER_USER_INFO_LIVE_KEY = 'BILLER_USER_INFO_LIVE';
    const BILLER_USER_INFO_SANDBOX_KEY = 'BILLER_USER_INFO_SANDBOX';
    const BILLER_NAME_KEY = 'BILLER_NAME';
    const BILLER_DESCRIPTION_KEY = 'BILLER_DESCRIPTION';
    CONST BILLER_ORDER_STATUS_MAP_KEY = 'BILLER_ORDER_STATUS_MAPPING_MAP';
    const BILLER_ORDER_STATUS_NONE_KEY = 'BILLER_ORDER_STATUS_NONE';
}
