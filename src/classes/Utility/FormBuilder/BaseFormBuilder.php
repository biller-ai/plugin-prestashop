<?php

namespace Biller\PrestaShop\Utility\FormBuilder;

use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository as UserInfoRepositoryInterface;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Repositories\UserInfoRepository;
use Biller\PrestaShop\Utility\Config\Config;
use Biller;
use AdminController;
use HelperForm;
use Tools;

/**
 * Class BaseFormBuilder. For building the module configuration form for merchant authorization with Biller.
 *
 * @package Biller\PrestaShop\Utility\FormBuilder
 */
class BaseFormBuilder
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'BaseFormBuilder';

    /** @var Biller */
    protected $module;
    /** @var string */
    const TAB_NAME_AUTHORIZATION = 'authorization';

    /** @var string[] Base form input field keys.*/
    const INPUT_FIELD_KEYS = array(
        Config::MODE_KEY,
        Config::WEBSHOP_UID_KEY,
        Config::USERNAME_KEY,
        Config::PASSWORD_KEY,
    );

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
     * @param bool $formSubmitted Bool indicating whether form is being generated for the first time or after post
     * request
     *
     * @return string Form HTML as string
     */
    public function generateConfigForm($formSubmitted)
    {
        $this->assignTemplateVars();
        $html = $this->renderLogo();

        $tabs = $this->getTabs();
        $inputs = $this->getInputs();
        $buttons = $this->getButtons();

        $fields = array(
            'form' => array(
                'tabs' => $tabs,
                'input' => $inputs,
                'desc' => $this->module->display($this->module->getPathUri(), 'views/templates/admin/version.tpl'),
                'submit' => array(
                    'title' => $this->module->l('Save', self::FILE_NAME),
                    'class' => 'btn btn-default pull-right',
                    'id' => 'btn_submit'
                ),
                'buttons' => $buttons
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
     * @return array Authorization section inputs
     */
    protected function getInputs()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->module->l('Enable Biller business invoice', self::FILE_NAME),
            'desc' => $this->module->l('Enable/disable Biller payment method.', self::FILE_NAME),
            'name' => Config::ENABLE_BUSINESS_INVOICE_KEY,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'values' => array(
                array(
                    'id' => 'yes',
                    'value' => 1,
                    'label' => $this->module->l('Yes', self::FILE_NAME),
                ),
                array(
                    'id' => 'no',
                    'value' => 0,
                    'label' => $this->module->l('No', self::FILE_NAME),
                )
            ),
            'is_bool' => true,
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->module->l('Mode', self::FILE_NAME),
            'desc' => $this->module->l('Options field that allows merchants to choose either live or sandbox mode.', self::FILE_NAME),
            'name' => Config::MODE_KEY,
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
            'label' => $this->module->l('Webshop UID', self::FILE_NAME),
            'name' => Config::WEBSHOP_UID_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'desc' => $this->module->l('Unique identifier of the Webshop.', self::FILE_NAME),
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Username', self::FILE_NAME),
            'name' => Config::USERNAME_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'desc' => $this->module->l('Biller seller username.', self::FILE_NAME),
        );

        $inputs[] = array(
            'type' => 'biller-password',
            'label' => $this->module->l('Password', self::FILE_NAME),
            'name' => Config::PASSWORD_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_AUTHORIZATION,
            'desc' => $this->module->l('Biller seller password.', self::FILE_NAME),
            'class' => 'fixed-width-xl'
        );

        return $inputs;
    }


    /**
     * Gets supplementary buttons.
     *
     * @return array
     */
    protected function getButtons()
    {
        return array();
    }

    /**
     * Gets available tabs for the form.
     *
     * @return array Array of form tabs and their names
     */
    protected function getTabs()
    {
        return array(
            self::TAB_NAME_AUTHORIZATION => $this->module->l('Authorization', self::FILE_NAME),
        );
    }

    /**
     * Gets default field values.
     *
     * @param bool $formSubmitted Bool indicating whether form is being generated for the first time or after post
     * request
     *
     * @return array Array of default input field values
     */
    protected function getDefaultValues($formSubmitted)
    {
        $values = array();

        if ($formSubmitted) {
            foreach (self::INPUT_FIELD_KEYS as $key) {
                $values[$key] = Tools::getValue($key);
            }
        } else {
            /** @var UserInfoRepository $userInfoRepository */
            $userInfoRepository = ServiceRegister::getService(UserInfoRepositoryInterface::class);
            $userInfo = $userInfoRepository->getActiveUserInfo();

            $values[Config::MODE_KEY] = $userInfo ? $userInfo->getMode() : UserInfoRepository::DEFAULT_MODE;
            $values[Config::WEBSHOP_UID_KEY] = $userInfo ? $userInfo->getWebShopUID() : '';
            $values[Config::USERNAME_KEY] = $userInfo ? $userInfo->getUsername() : '';
            $values[Config::PASSWORD_KEY] = $userInfo ? $userInfo->getPassword() : '';
        }

        $values[Config::ENABLE_BUSINESS_INVOICE_KEY] = (int)$this->module->isEnabledForShopContext();

        return $values;
    }

    /**
     * Assigns smarty template variables.
     *
     * @return void
     */
    private function assignTemplateVars()
    {
        $this->module->getSmarty()->assign(array(
            'logo' => $this->module->getPathUri() . 'views/img/biller_logo.svg',
            'version' => $this->module->version
        ));
    }

    /**
     * Renders form logo.
     *
     * @return string Logo HTML as string
     */
    private function renderLogo()
    {
        return $this->module->display($this->module->getPathUri(), 'views/templates/admin/logo.tpl');
    }
}
