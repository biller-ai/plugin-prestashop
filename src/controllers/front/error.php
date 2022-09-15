<?php

use Biller\PrestaShop\Bootstrap;

/**
 * Class BillerErrorModuleFrontController
 */
class BillerErrorModuleFrontController extends ModuleFrontController {

    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * @return void
     */
    public function postProcess()
    {
        $this->errors[] = $this->module->l('Biller payment transaction failed. Please choose another billing option or change the company data.');
        $this->redirectWithNotifications(\Context::getContext()->link->getPageLink('order'));
    }
}
