<?php

use Biller\PrestaShop\Bootstrap;
use Biller\Infrastructure\ServiceRegister;
use Biller\Domain\Order\Status;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;
use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;

/**
 * Class BillerErrorModuleFrontController. Used for handling error case from Biller and redirecting back to checkout
 * page.
 */
class BillerErrorModuleFrontController extends ModuleFrontController
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'BillerErrorModuleFrontController';

    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * Handles Biller order error.
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
            $order = new Order(Tools::getValue('orderId'));

            /** @var OrderStatusTransitionService $orderStatusTransitionService */
            $orderStatusTransitionService = ServiceRegister::getService(
                OrderStatusTransitionServiceInterface::class
            );

            $orderStatusTransitionService->updateStatus(
                $order->id_cart,
                Status::fromString(Status::BILLER_STATUS_FAILED)
            );
        }

        $message = $this->module->l('The unexpected error occurred, please select different payment method.', self::FILE_NAME);

        $this->getRedirectionHandler()->errorRedirect($message);
    }

    /**
     * Returns RedirectionVersion class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 RedirectionVersion16 is returned.
     * For versions from 1.7.0.0 to 1.7.7.0 RedirectionVersion17  is returned.
     * For versions from 1.7.7.0+ RedirectionVersion177  is returned.
     *
     * @return RedirectionVersionInterface
     */
    private function getRedirectionHandler()
    {
        return ServiceRegister::getService(RedirectionVersionInterface::class);
    }
}
