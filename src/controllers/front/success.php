<?php

use Biller\BusinessLogic\Order\OrderService;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\Domain\Order\Status;
use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Order\Exceptions\InvalidOrderReferenceException;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;


class BillerSuccessModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * @return void
     * * @throws PrestaShopException
     */
    public function postProcess()
    {
        /** @var  OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::class);
        $cart = Context::getContext()->cart;
        try {
            if ($orderService->isPaymentAccepted($cart->id)) {
                $this->createOrder($cart);
                $successUrl = $this->generateSuccessURL($cart);
                Tools::redirect($successUrl);
            }
        } catch (RequestNotSuccessfulException $e) {
            Logger::logError('Request not successful!' . $e->getMessage());
        } catch (InvalidOrderReferenceException $e) {
            Logger::logError('Invalid order reference!' . $e->getMessage());
        } catch (HttpCommunicationException $e) {
            Logger::logError('Http communication exception!' . $e->getMessage());
        } catch (HttpRequestException $e) {
            Logger::logError('Http request exception!' . $e->getMessage());
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError('Query filter invalid parameters exception!' . $e->getMessage());
        } catch (Exception $e) {
            Logger::logError('Creation order failed!' . $e->getMessage());
        }
    }

    /**
     * @param \Cart $cart
     * @return void
     * @throws \Exception
     */
    private function createOrder($cart)
    {
        /** @var BillerOrderStatusMapping $orderStatusMapperService */
        $orderStatusMapperService = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $idStatus = $orderStatusMapperService->getOrderStatusMap()[Status::BILLER_STATUS_ACCEPTED];
        $total = $cart->getOrderTotal();
        $this->module->validateOrder($cart->id, $idStatus, $total, $this->module->displayName);
    }

    /**
     * @param \Cart $cart
     * @return string
     */
    private function generateSuccessURL($cart)
    {
        return $this->context->link->getPageLink(
            'order-confirmation',
            true,
            null,
            [
                'id_cart' => (int)$cart->id,
                'id_module' => (int)$this->module->id,
                'id_order' => $this->module->currentOrder,
                'key' => $cart->secure_key,
            ]
        );
    }
}
