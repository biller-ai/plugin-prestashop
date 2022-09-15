<?php

use Biller\BusinessLogic\Order\OrderReference\Repository\OrderReferenceRepository;
use Biller\BusinessLogic\Webhook\WebhookHandler;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Exception\OrderNotCreatedException;
use Biller\PrestaShop\Utility\Response;

/**
 * Class BillerWebhooksModuleFrontController. Used for handling webhooks from Biller.
 */
class BillerWebhooksModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * Handles incoming Biller webhook events.
     *
     * @return void
     */
    public function postProcess()
    {
        $payload = \Tools::file_get_contents('php://input');

        /** @var OrderReferenceRepository $orderReferenceRepository */
        $orderReferenceRepository = ServiceRegister::getService(OrderReferenceRepository::class);
        $webhookHandler = new WebhookHandler($orderReferenceRepository);

        $webhookHandler->handle($payload);

        Response::die200(array('success' => true));
    }
}
