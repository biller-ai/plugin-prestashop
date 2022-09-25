<?php

namespace Biller\PrestaShop\Utility\Services;

use Biller;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;
use Context;
use Tools;

/**
 * Class SettingsService
 *
 * @package Biller\PrestaShop\Utility\Services
 */
class SettingsService
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'SettingsService';

    /** @var Biller */
    private $module;

    /**
     * @param Biller $module
     */
    public function __construct(Biller $module)
    {
        $this->module = $module;
    }

    /**
     * @return array Array of errors
     */
    public function saveSettings()
    {
        $errors = array();

        $name = Tools::getValue(Config::NAME_KEY);
        $description = Tools::getValue(Config::DESCRIPTION_KEY);
        $enabled = Tools::getValue(Config::ENABLE_BUSINESS_INVOICE_KEY);

        if (!$name || !$description) {
            Context::getContext()->controller->errors[] =
                $errors[] = $this->module->l('Name and description are required!', self::FILE_NAME);

            return $errors;
        }

        /** @var BillerPaymentConfiguration $paymentConfiguration */
        $paymentConfiguration = ServiceRegister::getService(BillerPaymentConfigurationInterface::class);

        $paymentConfiguration->setName($name);
        $paymentConfiguration->setDescription($description);
        $paymentConfiguration->setEnabled($enabled);

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $orderStatusMap = array();

        foreach ($orderStatusMapping->getDefaultOrderStatusMap() as $key => $value) {
            $orderStatusMap[$key] = \Tools::getValue($key);
        }

        $orderStatusMapping->saveOrderStatusMap($orderStatusMap);

        return $errors;
    }
}
