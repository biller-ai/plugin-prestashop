<?php

use Biller\PrestaShop\Bootstrap;
use Biller\Infrastructure\ServiceRegister;
use Biller\Domain\Order\Status;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;

/**
 * Class BillerCancelModuleFrontController. Used for handling cancel case from Biller and redirecting back to checkout
 * page.
 */
class BillerCancelModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * Handle cancellation from Biller and redirect back to checkout page.
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $this->getRedirectionHandler()->cancelRedirect();
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
