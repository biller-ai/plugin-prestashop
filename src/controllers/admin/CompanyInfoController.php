<?php

use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Response;

/**
 * CompanyInfoController class. Endpoint for fetching invoice address company information for given cart.
 */
class CompanyInfoController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        Bootstrap::init();

        $this->bootstrap = true;
    }

    /**
     * Fetches invoice address company information.
     *
     * @return void
     */
    public function displayAjaxFetchCompanyInfo()
    {
        $cart = new \Cart(\Tools::getValue('cartId'));
        $invoiceAddress = new \Address($cart->id_address_invoice);

        Response::die200(array(
            'companyName' => $invoiceAddress->company,
            'vatNumber' => $invoiceAddress->vat_number,
        ));
    }
}
