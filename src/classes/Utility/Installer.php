<?php

namespace Biller\PrestaShop\Utility;

use Biller;
use Biller\Domain\Order\Status;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Repositories\ConfigurationRepository;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;

/**
 * Class Installer
 * @package Biller\PrestaShop\Utility
 */
class Installer
{
    /** @var Biller */
    private $module;

    /** @var string[] */
    private static $controllers = array(
        'NotificationsHub'
    );

    /** @var string[] */
    private static $hooks = array(
        'displayAdminOrderContentOrder',
        'displayAdminOrderTabContent',
        'actionOrderStatusUpdate',
        'actionOrderSlipAdd',
        'actionBuildMailLayoutVariables',
        'paymentOptions',
        'displayHeader',
        'actionAdminControllerSetMedia',
    );

    /**
     * @param Biller $module
     */
    public function __construct(Biller $module)
    {
        $this->module = $module;
    }

    /**
     * Initializes plugin
     *
     * @return bool
     */
    public function install()
    {
        Bootstrap::init();

        return (
            $this->createTables() &&
            $this->addControllers() &&
            $this->addHooks() &&
            $this->addOrderStates() &&
            $this->initConfig()
        );
    }

    /**
     * Drop tables and remove hooks
     *
     * @return bool
     */
    public function uninstall()
    {
        Bootstrap::init();

        return (
            $this->dropTables() &&
            $this->removeControllers() &&
            $this->removeHooks() &&
            $this->removeOrderStates() &&
            $this->deleteConfig()
        );
    }

    /**
     * @return bool
     */
    private function createTables()
    {
        $databaseHandler = new DatabaseHandler();

        return (
            $databaseHandler->createTable('biller_notifications') &&
            $databaseHandler->createTable('biller_order_reference')
        );
    }

    /**
     * Drop base tables
     *
     * @return bool
     */
    private function dropTables()
    {
        $databaseHandler = new DatabaseHandler();

        return ($databaseHandler->dropTable('biller_notifications')
            && $databaseHandler->dropTable('biller_order_reference')
        );
    }

    /**
     * Registers module controllers.
     *
     * @return bool
     *
     */
    private function addControllers()
    {
        $result = true;
        foreach (self::$controllers as $controller) {
            $result = $result && $this->addController($controller);
        }

        return $result;
    }

    /**
     * Registers a controller.
     *
     * @param string $name Controller name.
     * @param int $parentId Id of parent controller.
     *
     * @return bool
     */
    private function addController($name, $parentId = -1)
    {
        $tab = new \Tab();

        $tab->active = 1;
        $tab->name[(int)\Configuration::get('PS_LANG_DEFAULT')] = $this->module->l('biller');
        $tab->class_name = $name;
        $tab->module = $this->module->name;
        $tab->id_parent = $parentId;
        $tab->add();

        return true;
    }

    /**
     * Removes module controllers.
     *
     * @return bool
     */
    private function removeControllers()
    {
        try {
            $tabs = \Tab::getCollectionFromModule($this->module->name);
            if ($tabs && count($tabs)) {
                foreach ($tabs as $tab) {
                    $tab->delete();
                }
            }

            return true;
        } catch (\PrestaShopException $e) {
            Logger::logWarning('Error removing controller! Error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Registers module hooks.
     *
     * @return bool
     */
    private function addHooks()
    {
        $hooks = self::$hooks;
        $result = true;

        foreach ($hooks as $hook) {
            $result = $result && $this->module->registerHook($hook);
        }

        return $result;
    }

    /**
     * Unregisters module hooks.
     *
     * @return bool
     */
    private function removeHooks()
    {
        $hooks = self::$hooks;
        $result = true;
        foreach ($hooks as $hook) {
            $result = $result && $this->module->unregisterHook($hook);
        }

        return $result;
    }

    /**
     * Adds 'None' order state to the shop.
     *
     * @return bool
     */
    private function addOrderStates()
    {
        $orderState = new \OrderState();

        $orderState->send_email = false;
        $orderState->color = '#B4B9C4';
        $orderState->hidden = false;
        $orderState->delivery = false;
        $orderState->logable = false;
        $orderState->invoice = false;
        $orderState->module_name = $this->module->name;
        $orderState->name = TranslationUtility::createMultiLanguageField('None');
        try {
            if ($orderState->add()) {
                ConfigurationRepository::updateValue(Config::BILLER_ORDER_STATUS_NONE_KEY, (int)$orderState->id);

                return true;
            }
        } catch (\Exception $e) {}

        return false;
    }

    /**
     * Removes 'None' order state from the shop.
     *
     * @return bool
     */
    private function removeOrderStates()
    {
        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $availableStatuses = $orderStatusMapping->getAvailableStatuses();

        $deletionStatus = true;
        array_map(function($status) use (&$deletionStatus) {
            if ($status['module_name'] === $this->module->name) {
                try {
                    $orderState = new \OrderState($status['id_order_state']);
                    $orderState->delete();
                } catch (\PrestaShopException $e) {
                    $deletionStatus = false;
                }
            }
        }, $availableStatuses);

        return $deletionStatus;
    }

    /**
     * Initializes module configuration.
     *
     * @return bool
     */
    private function initConfig()
    {
        /** @var BillerPaymentConfiguration $paymentConfiguration */
        $paymentConfiguration = ServiceRegister::getService(BillerPaymentConfigurationInterface::class);

        $paymentConfiguration->setName($this->module->l('Biller business invoice'));
        $paymentConfiguration->setDescription(
            $this->module->l('The payment solution that advances both sides. We pay out every invoice on time.')
        );
        $paymentConfiguration->setMethodEnabledStatus(1);

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);

        $noneOrderStatusId = ConfigurationRepository::getValue(Config::BILLER_ORDER_STATUS_NONE_KEY);
        $defaultOrderStatusMap = array_merge(
            BillerOrderStatusMapping::DEFAULT_ORDER_STATUS_MAP,
            array(
                Status::BILLER_STATUS_PARTIALLY_REFUNDED => $noneOrderStatusId,
                Status::BILLER_STATUS_PARTIALLY_CAPTURED => $noneOrderStatusId,
            )
        );
        $orderStatusMapping->saveOrderStatusMap($defaultOrderStatusMap);

        return true;
    }

    /**
     * Deletes Biller configuration values from database.
     *
     * @return bool
     */
    private function deleteConfig()
    {
        $configValueKeys = array(
            Config::BILLER_ENABLE_BUSINESS_INVOICE_KEY,
            Config::BILLER_MODE_KEY,
            Config::BILLER_WEBSHOP_UID_KEY,
            Config::BILLER_USERNAME_KEY,
            Config::BILLER_PASSWORD_KEY,
            Config::BILLER_USER_INFO_LIVE_KEY,
            Config::BILLER_USER_INFO_SANDBOX_KEY,
            Config::BILLER_NAME_KEY,
            Config::BILLER_DESCRIPTION_KEY,
            Config::BILLER_ORDER_STATUS_MAP_KEY,
            Config::BILLER_ORDER_STATUS_NONE_KEY,
        );
        $result = true;

        foreach ($configValueKeys as $configValueKey) {
            $result = $result && \Configuration::deleteByName($configValueKey);
        }

        return $result;
    }
}
