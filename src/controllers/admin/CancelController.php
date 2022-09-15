<?php

use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Response;
use Biller\Infrastructure\ServiceRegister;
use Biller\Domain\Order\Status;
use Biller\Infrastructure\Logger\Logger;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;
use Biller\PrestaShop\Utility\FlashBag;

/**
 * CancelController class. Used for handling order cancellation on order page.
 */
class CancelController extends ModuleAdminController
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'CancelController';

    public function __construct()
    {
        parent::__construct();
        Bootstrap::init();

        $this->bootstrap = true;
    }

    /**
     * Handles ajax call for order cancellation. Try to change order history and trigger hookActionOrderStatusUpdate.
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function displayAjaxCancelOrder()
    {
        $orderId = Tools::getValue('orderId');
        $order = new Order($orderId);
        try {
            $this->getOrderStatusTransitionService()->updateStatus(
                $order->id_cart,
                Status::fromString(Status::BILLER_STATUS_CANCELLED)
            );

            FlashBag::getInstance()->setMessage('success', 'Successful update.');

            Response::die200();
        } catch (Exception $exception) {
            Logger::logError($exception->getMessage());

            Response::die400(array('message' => $this->module->l(
                $exception->getMessage(),
                self::FILE_NAME
            )));
        }
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
