<?php

use Biller\PrestaShop\Data\Validator;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\BusinessLogic\Order\OrderService;
use Biller\PrestaShop\Data\Mapper;

/**
 * BillerPaymentModuleFrontController class.
 */
class BillerPaymentModuleFrontController extends ModuleFrontController
{

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
        try {
            $cart = Context::getContext()->cart;
            Validator::validate($this, $cart);
            /** @var  OrderService $orderService */
            $orderService = ServiceRegister::getService(OrderService::class);
            $orderRequest = (new Mapper($cart, $this->module))->mapOrder();
            $paymentPageURL = $orderService->create($orderRequest);
            Tools::redirect($paymentPageURL);
        } catch (Exception $e) {
            $this->redirectError($e);
        }
    }

    /**
     * @param \Exception $e
     * @return void
     */
    private function redirectError($e)
    {
        $this->errors[] = $this->module->l($e->getMessage());
        $this->redirectWithNotifications(\Context::getContext()->link->getPageLink('order'));
    }
}
