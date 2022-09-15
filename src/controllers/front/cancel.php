<?php

use Biller\PrestaShop\Bootstrap;

class BillerCancelModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    public function postProcess()
    {
        $this->redirectWithNotifications(\Context::getContext()->link->getPageLink('order'));
    }
}
