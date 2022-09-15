<?php

use Biller\BusinessLogic\Order\OrderService;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\Domain\Order\Status;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;

/**
 * Class BillerSuccessModuleFrontController
 * Used for handling success case from Biller and redirecting to Order confirmation page.
 */
class BillerSuccessModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * Handle success from Biller and redirect to order-confirmation page.
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $isPaymentLink = Tools::getIsset('orderId');
        if ($isPaymentLink) {
            // payment link
            $orderId = Tools::getValue('orderId');
            /** @noinspection PhpUnhandledExceptionInspection */
            $order = new Order($orderId);
            $cart = new Cart($order->id_cart);
        } else {
            // checkout
            $cart = Context::getContext()->cart;
        }

        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::class);
        try {
            if ($orderService->isPaymentAccepted($cart->id)) {
                $orderAcceptedStatusId = $this->getOrderAcceptedStatus();

                if (!$isPaymentLink) {
                    $this->createOrder($cart, $orderAcceptedStatusId);
                } else {
                    $this->getOrderStatusTransitionService()->updateStatus(
                        $cart->id,
                        Status::fromString(Status::BILLER_STATUS_ACCEPTED)
                    );
                }

                $successUrl = $this->generateSuccessURL($cart);

                Tools::redirect($successUrl);
            }
        } catch (Exception $exception) {
            Logger::logError('Creation order failed!' . $exception->getMessage());
        }
    }

    /**
     * Gets id of PS order status currently that's Biller accepted status currently mapped to.
     *
     * @return int ID of PS accepted order status
     */
    private function getOrderAcceptedStatus()
    {
        /** @var BillerOrderStatusMapping $orderStatusMapperService */
        $orderStatusMapperService = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        return $orderStatusMapperService->getOrderStatusMap()[Status::BILLER_STATUS_ACCEPTED];
    }

    /**
     * Creates presta from the given cart order.
     *
     * @param Cart $cart Cart for order creation
     * @param int $orderAcceptedStatusId ID of accepted order state
     *
     * @return void
     *
     * @throws Exception
     */
    private function createOrder($cart, $orderAcceptedStatusId)
    {
        $total = $cart->getOrderTotal();
        $this->module->validateOrder(
            $cart->id,
            $orderAcceptedStatusId,
            $total,
            $this->module->displayName
        );
    }

    /**
     * Create URL to order-confirmation page.
     *
     * @param Cart $cart
     *
     * @return string
     */
    private function generateSuccessURL($cart)
    {
        return $this->context->link->getPageLink(
            'order-confirmation',
            true,
            null,
            array(
                'id_cart' => (int)$cart->id,
                'id_module' => (int)$this->module->id,
                'id_order' => $this->module->currentOrder,
                'key' => $cart->secure_key
            )
        );
    }

    /**
     * Returns order status transition service from service register.
     *
     * @return OrderStatusTransitionService
     */
    private function getOrderStatusTransitionService()
    {
        return ServiceRegister::getService(
            OrderStatusTransitionServiceInterface::class
        );
    }
}
