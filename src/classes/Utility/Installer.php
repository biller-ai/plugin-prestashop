<?php

namespace Biller\PrestaShop\Utility;

use Biller\Infrastructure\Logger\Logger;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Version\Hooks\Contract\HooksVersionInterface;
use Configuration;
use PrestaShopException;
use Tab;
use Biller\Infrastructure\ServiceRegister;

/**
 * Class Installer. Contains main logic for installing and uninstalling module.
 *
 * @package Biller\PrestaShop\Utility
 */
class Installer
{
    /** @var string */
    const BILLER_NOTIFICATIONS = 'biller_notifications';
    /** @var string */
    const BILLER_ORDER_REFERENCE = 'biller_order_reference';
    /** @var string */
    const BILLER_COMPANY_INFO = 'biller_company_info';

    /** @var string[] */
    private static $controllers = array(
        'NotificationsHub',
        'Cancel',
        'CompanyInfo',
        'Capture',
    );

    /** @var callable */
    private $registerHookHandler;

    /** @var callable */
    private $unregisterHookHandler;

    /** @var string */
    private $moduleName;

    /**
     * @param callable $registerHookHandler
     * @param callable $unregisterHookHandler
     *
     * @param string $moduleName
     */
    public function __construct($registerHookHandler, $unregisterHookHandler, $moduleName)
    {
        $this->registerHookHandler = $registerHookHandler;
        $this->unregisterHookHandler = $unregisterHookHandler;
        $this->moduleName = $moduleName;
    }

    /**
     * Initializes plugin.
     * Creates database tables, adds admin controllers, hooks, order states and initializes configuration values.
     *
     * @return bool Installation status
     */
    public function install()
    {
        Bootstrap::init();

        return (
            $this->createTables() &&
            $this->addControllers() &&
            $this->addHooks()
        );
    }

    /**
     * Drop database tables, remove hooks, controller, order states and configuration values
     *
     * @return bool Uninstallation status
     */
    public function uninstall()
    {
        Bootstrap::init();

        return (
            $this->dropTables() &&
            $this->removeControllers() &&
            $this->removeHooks() &&
            $this->deleteConfig()
        );
    }

    /**
     * Create database tables for Biller.
     *
     * @return bool Table creation status
     */
    private function createTables()
    {
        return (
            DatabaseHandler::createTable(self::BILLER_NOTIFICATIONS, 8) &&
            DatabaseHandler::createTable(self::BILLER_ORDER_REFERENCE, 8) &&
            DatabaseHandler::createTable(self::BILLER_COMPANY_INFO, 4)
        );
    }

    /**
     * Registers module Admin controllers.
     *
     * @return bool Controller addition status
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
     * Registers Admin controller.
     *
     * @param string $name Controller name
     * @param int $parentId ID of parent controller
     *
     * @return bool Controller addition status
     */
    private function addController($name, $parentId = -1)
    {
        $tab = new Tab();

        $tab->active = 1;
        $tab->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $this->moduleName;
        $tab->class_name = $name;
        $tab->module = $this->moduleName;
        $tab->id_parent = $parentId;
        $tab->add();

        return true;
    }

    /**
     * Call functions with arguments given as second parameter
     *
     * @param callable $handler Callback of function
     * @param string $arg Argument of function
     *
     * @return mixed|void Return value of the called handler
     */
    private function call($handler, $arg)
    {
        return call_user_func($handler, $arg);
    }

    /**
     * Registers module hooks.
     *
     * @return bool Hook addition status
     */
    private function addHooks()
    {
        $hooks = $this->getHooksVersion()->getHooks();
        $result = true;

        foreach ($hooks as $hook) {
            $result = $result && $this->call($this->registerHookHandler, $hook);
        }

        return $result;
    }

    /**
     * Drop database tables for Biller.
     *
     * @return bool Table deletion status
     */
    private function dropTables()
    {
        return (
            DatabaseHandler::dropTable(self::BILLER_NOTIFICATIONS) &&
            DatabaseHandler::dropTable(self::BILLER_ORDER_REFERENCE) &&
            DatabaseHandler::dropTable(self::BILLER_COMPANY_INFO)
        );
    }

    /**
     * Removes Admin controllers.
     *
     * @return bool Controller deletion status
     */
    private function removeControllers()
    {
        try {
            $tabs = Tab::getCollectionFromModule($this->moduleName);
            if ($tabs && count($tabs)) {
                foreach ($tabs as $tab) {
                    $tab->delete();
                }
            }

            return true;
        } catch (PrestaShopException $exception) {
            Logger::logError('Error removing controller! Error: ' . $exception->getMessage());

            return false;
        }
    }

    /**
     * Unregisters module hooks.
     *
     * @return bool Hook deletion status
     */
    private function removeHooks()
    {
        $hooks = $this->getHooksVersion()->getHooks();
        $result = true;
        foreach ($hooks as $hook) {
            $result = $result && $this->call($this->unregisterHookHandler, $hook);
        }

        return $result;
    }

    /**
     * Deletes Biller configuration values from database.
     *
     * @return bool Config deletion status
     */
    private function deleteConfig()
    {
        return DatabaseHandler::deleteRows('configuration', "name LIKE '%BILLER%'");
    }

    /**
     * Returns HooksVersion class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 HooksVersion16 is returned.
     * For versions from 1.7.0.0 to 1.7.7.0 HooksVersion17  is returned.
     * For versions from 1.7.7.0+ HooksVersion177 is returned.
     *
     * @return HooksVersionInterface
     */
    private function getHooksVersion()
    {
        return ServiceRegister::getService(HooksVersionInterface::class);
    }
}
