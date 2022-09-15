<?php

namespace Biller\PrestaShop\Utility\FormBuilder;

use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository as UserInfoRepositoryInterface;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Repositories\ConfigurationRepository;
use Biller\PrestaShop\Repositories\UserInfoRepository;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller;
use AdminController;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;
use HelperForm;
use Tools;

/**
 * BaseFormBuilder class.
 *
 * @package Biller\PrestaShop\Utility\FormBuilder
 */
class BaseFormBuilder
{
    /** @var Biller */
    protected $module;
    /** @var string */
    const TAB_NAME_AUTHORIZATION = 'authorization';

    /**
     * @param Biller $module
     */
    public function __construct(Biller $module)
    {
        $this->module = $module;
    }

    /**
     * Generates Biller module config form.
     *
     * @param bool $formSubmitted Bool indicating whether form is being generated for the first time or after post request
     *
     * @return string Form HTML.
     */
    public function generateConfigForm($formSubmitted)
    {
        $this->assingTemplateVars();
        $html = $this->renderLogo();

        $tabs = $this->getTabs();
        $inputs = $this->getInputs();

        $fields = array(
            'form' => array(
                'tabs' => $tabs,
                'input' => $inputs,
                'desc' => $this->module->display($this->module->getPathUri(), 'views/templates/admin/version.tpl'),
                'submit' => array(
                    'title' => $this->module->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'tab' => 'authorization',
                ),
            ),
        );

        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->table = $this->module->getTable();
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->identifier = $this->module->getIdentifier();
        $helper->currentIndex =
            AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->module->name]);
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->default_form_language = $this->module->getContext()->language->id;
        $helper->fields_value = $this->getDefaultValues($formSubmitted);

        return $html . $helper->generateForm(array($fields));
    }

    /**
     * Generates authorization section of config form.
     *
     * @return array Authorization section inputs.
     */
    protected function getInputs()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->module->l('Enable Biller business invoice'),
            'desc' => $this->module->l('Enable/disable Biller payment method.'),
            'name' => Config::BILLER_ENABLE_BUSINESS_INVOICE_KEY,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'values' => array(
                array(
                    'id' => 'yes',
                    'value' => 1,
                    'label' => $this->module->l('Yes'),
                ),
                array(
                    'id' => 'no',
                    'value' => 0,
                    'label' => $this->module->l('No'),
                )
            ),
            'is_bool' => true,
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->module->l('Mode'),
            'desc' => $this->module->l('Options field that allows merchants to choose either live or sandbox mode.'),
            'name' => Config::BILLER_MODE_KEY,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'options' => array(
                'query' => array(
                    array(
                        'id_option' => 'sandbox',
                        'name' => 'Sandbox',
                    ),
                    array(
                        'id_option' => 'live',
                        'name' => 'Live',
                    ),
                ),
                'id' => 'id_option',
                'name' => 'name',
            )
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Webshop UID'),
            'name' => Config::BILLER_WEBSHOP_UID_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'desc' => $this->module->l('Unique identifier of the Webshop.'),
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Username'),
            'name' => Config::BILLER_USERNAME_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'desc' => $this->module->l('Biller seller username.'),
        );

        $inputs[] = array(
            'type' => 'biller-password',
            'label' => $this->module->l('Password'),
            'name' => Config::BILLER_PASSWORD_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'desc' => $this->module->l('Biller seller password.'),
            'class' => 'fixed-width-xl'
        );

        return $inputs;
    }

    /**
     * Gets available tabs for the form.
     *
     * @return array Array of form tabs and their names.
     */
    protected function getTabs()
    {
        return array(
            self::TAB_NAME_AUTHORIZATION => $this->module->l('Authorization'),
        );
    }

    /**
     * Gets default field values.
     *
     * @param bool $formSubmitted Bool indicating whether form is being generated for the first time or after post request
     *
     * @return array
     */
    protected function getDefaultValues($formSubmitted)
    {
        $values = array();

        if ($formSubmitted) {
            $submittedValues = Tools::getAllValues();

            $values[Config::BILLER_MODE_KEY] = $submittedValues[Config::BILLER_MODE_KEY];
            $values[Config::BILLER_WEBSHOP_UID_KEY] = $submittedValues[Config::BILLER_WEBSHOP_UID_KEY];
            $values[Config::BILLER_USERNAME_KEY] = $submittedValues[Config::BILLER_USERNAME_KEY];
            $values[Config::BILLER_PASSWORD_KEY] = $submittedValues[Config::BILLER_PASSWORD_KEY];
        } else {
            /** @var UserInfoRepository $userInfoRepository */
            $userInfoRepository = ServiceRegister::getService(UserInfoRepositoryInterface::class);
            $userInfo = $userInfoRepository->getActiveUserInfo();

            $values[Config::BILLER_MODE_KEY] = $userInfo ? $userInfo->getMode() : UserInfoRepository::DEFAULT_MODE;
            $values[Config::BILLER_WEBSHOP_UID_KEY] = $userInfo ? $userInfo->getWebShopUID() : '';
            $values[Config::BILLER_USERNAME_KEY] = $userInfo ? $userInfo->getUsername() : '';
            $values[Config::BILLER_PASSWORD_KEY] = $userInfo ? $userInfo->getPassword() : '';
        }

        $values[Config::BILLER_ENABLE_BUSINESS_INVOICE_KEY] = (int)$this->module->isEnabledForShopContext();

        return $values;
    }

    /**
     * Assigns smarty template variables.
     *
     * @return void
     */
    private function assingTemplateVars()
    {
        $this->module->getSmarty()->assign(array(
            'logo' => $this->module->getPathUri() . 'views/img/biller_logo.svg',
            'version' => $this->module->version
        ));
    }

    /**
     * Renders form logo.
     *
     * @return string
     */
    private function renderLogo()
    {
        return $this->module->display($this->module->getPathUri(), 'views/templates/admin/logo.tpl');
    }
}
