<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\BusinessLogic\Integration\Refund\RefundAmountRejectResponse;
use Biller\BusinessLogic\Integration\RefundAmountRequest;
use Biller\BusinessLogic\Integration\Refund\RefundAmountRequestService as RefundAmountRequestServiceInterface;
use Biller\BusinessLogic\Refunds\Contracts\RefundAmountHandlerService;
use Biller\BusinessLogic\Refunds\Handlers\RefundAmountHandler;
use Biller\BusinessLogic\Integration\RefundLineRequest;
use Biller\BusinessLogic\Refunds\Handlers\RefundLineHandler;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Infrastructure\ServiceRegister;
use Biller\Infrastructure\Singleton;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Exception;
use Order;

/**
 * Class RefundAmountRequestService. Handles standard and full refunds.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class RefundAmountRequestService extends Singleton implements RefundAmountRequestServiceInterface
{
    /**
     * @var string $errorMessage
     */
    private $errorMessage;

    /**
     * @inheritDoc
     */
    public function reject(RefundAmountRequest $request, Exception $reason)
    {
        $this->errorMessage = "Order refund rejected with error: {$reason->getMessage()}.";

        return new RefundAmountRejectResponse(true);
    }

    /**
     * Process a refund when merchant changes order status.
     *
     * @param Order $order Order that is being refunded
     *
     * @return string Error message string
     *
     * @throws InvalidCurrencyCode
     */
    public function processRefund($order)
    {
        $prestaCurrency = new \Currency($order->id_currency);
        $amount = Amount::fromFloat(
            $order->getOrdersTotalPaid() - $this->getOrderRefundMapperVersion()->getRefundedAmount($order),
            Currency::fromIsoCode($prestaCurrency->iso_code)
        );
        $request = new RefundAmountRequest(
            $order->id_cart,
            "Order with cart ID: {$order->id_cart} refund",
            $amount
        );

        try {
            $this->getRefundAmountHandlerService()->handle($request);
        } catch (Exception $exception) {
            $this->reject($request, $exception);
        }

        return $this->errorMessage;
    }

    /**
     * Passes given refund line request to core handler.
     *
     * @param RefundLineRequest $refundLineRequest Refund line request
     *
     * @return void
     *
     * @throws InvalidCurrencyCode
     * @throws \Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException
     * @throws \Biller\Domain\Exceptions\CurrencyMismatchException
     * @throws \Biller\Domain\Exceptions\InvalidTaxPercentage
     * @throws \Biller\Domain\Exceptions\InvalidTypeException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function processPartialRefund($refundLineRequest)
    {
        $refundLineHandler = new RefundLineHandler();
        $refundLineHandler->handle($refundLineRequest);
    }

    /**
     * Returns RefundAmountHandlerService
     *
     * @return RefundAmountHandler
     */
    private function getRefundAmountHandlerService()
    {
        return ServiceRegister::getService(RefundAmountHandlerService::class);
    }

    /**
     * Returns OrderRefundMapper class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 OrderRefundMapperVersion16 is returned.
     * For versions from 1.7.0.0+ OrderRefundMapperVersion177  is returned.
     *
     * @return OrderRefundMapperVersion
     */
    private function getOrderRefundMapperVersion()
    {
        return ServiceRegister::getService(OrderRefundMapperVersion::class);
    }
}
