<?php

namespace Biller\PrestaShop\Utility\Services;

use Biller;
use Biller\Domain\Order\Status;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;
use Context;
use Tools;

/**
 * SettingsService class.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class SettingsService
{
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
     * @return array
     */
    public function saveSettings()
    {
        $errors = array();

        $name = Tools::getValue(Config::BILLER_NAME_KEY);
        $description = Tools::getValue(Config::BILLER_DESCRIPTION_KEY);

        if (!$name || !$description) {
            Context::getContext()->controller->errors[] =
                $errors[] = $this->module->l('Name and description are required!');

            return $errors;
        }

        /** @var BillerPaymentConfiguration $paymentConfiguration */
        $paymentConfiguration = ServiceRegister::getService(BillerPaymentConfigurationInterface::class);

        $paymentConfiguration->setName($name);
        $paymentConfiguration->setDescription($description);

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $orderStatusMap = array();

        $orderStatusMap[Status::BILLER_STATUS_PENDING] =
            Tools::getValue(Status::BILLER_STATUS_PENDING);
        $orderStatusMap[Status::BILLER_STATUS_ACCEPTED] =
            Tools::getValue(Status::BILLER_STATUS_ACCEPTED);
        $orderStatusMap[Status::BILLER_STATUS_REFUNDED] =
            Tools::getValue(Status::BILLER_STATUS_REFUNDED);
        $orderStatusMap[Status::BILLER_STATUS_PARTIALLY_REFUNDED] =
            Tools::getValue(Status::BILLER_STATUS_PARTIALLY_REFUNDED);
        $orderStatusMap[Status::BILLER_STATUS_CAPTURED] =
            Tools::getValue(Status::BILLER_STATUS_CAPTURED);
        $orderStatusMap[Status::BILLER_STATUS_FAILED] =
            Tools::getValue(Status::BILLER_STATUS_FAILED);
        $orderStatusMap[Status::BILLER_STATUS_REJECTED] =
            Tools::getValue(Status::BILLER_STATUS_REJECTED);
        $orderStatusMap[Status::BILLER_STATUS_CANCELLED] =
            Tools::getValue(Status::BILLER_STATUS_CANCELLED);
        $orderStatusMap[Status::BILLER_STATUS_PARTIALLY_CAPTURED] =
            Tools::getValue(Status::BILLER_STATUS_PARTIALLY_CAPTURED);

        $orderStatusMapping->saveOrderStatusMap($orderStatusMap);

        return $errors;
    }

    /**
     * Updates the enabled status of the Biller plugin if changed in configuration form.
     *
     * @return void
     */
    public function updateEnabledStatus()
    {
        $enabled = Tools::getValue(Config::BILLER_ENABLE_BUSINESS_INVOICE_KEY);

        if ($enabled !== $this->module->isEnabledForShopContext()) {
            if ($enabled) {
                $this->module->enable();
            } else {
                $this->module->disable();
            }
        }
    }
}
