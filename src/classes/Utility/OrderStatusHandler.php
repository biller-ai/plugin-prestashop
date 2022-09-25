<?php

namespace Biller\PrestaShop\Utility;

use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Exception\BillerRefundRejectedException;
use Biller\PrestaShop\BusinessService\RefundAmountRequestService;
use Biller\Infrastructure\ServiceRegister;
use Biller\BusinessLogic\Integration\Refund\RefundAmountRequestService as RefundAmountRequestServiceInterface;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\Domain\Order\Status;
use Biller\BusinessLogic\Integration\CancellationRequest;
use Biller\BusinessLogic\Cancellation\CancellationHandler;
use Biller\BusinessLogic\Notifications\NotificationHub;
use Biller\BusinessLogic\Notifications\NotificationHub as NotificationHubInterface;
use Biller\BusinessLogic\Notifications\NotificationText;
use Biller\PrestaShop\BusinessService\ShipmentService;
use Biller\BusinessLogic\Shipment\ShipmentHandler;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Biller\BusinessLogic\Integration\RefundLineRequest;
use Biller\PrestaShop\Repositories\OrderReferenceRepository;
use Biller\Infrastructure\ORM\RepositoryRegistry;
use Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference;
use Biller\BusinessLogic\Order\OrderService;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\BusinessLogic\Order\Exceptions\InvalidOrderReferenceException;
use Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Exceptions\InvalidTypeException;
use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;
use Cart;
use Module;
use Order;
use Tools;
use Exception;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class OrderStatusHandler. Used for handling order status change.
 *
 * @package Biller\PrestaShop\Utility
 */
class OrderStatusHandler
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'OrderStatusHandler';

    /**
     * Handles refund when status is changed.
     *
     * @param Order $order Order to be fully refunded
     *
     * @return void
     */
    public static function handleFullRefund($order)
    {
        try {
            /** @var RefundAmountRequestService $refundAmountRequestService */
            $refundAmountRequestService =
                ServiceRegister::getService(
                    RefundAmountRequestServiceInterface::class
                );

            $refundError = $refundAmountRequestService->processRefund($order);

            if (!empty($refundError)) {
                throw new BillerRefundRejectedException($refundError);
            }
        } catch (Exception $exception) {
            FlashBag::getInstance()->setMessage('error', Module::getInstanceByName('biller')->l(
                $exception->getMessage(),
                self::FILE_NAME
            ));

            Tools::redirectAdmin(self::getRedirectionHandler()->generateOrderPageUrl($order));
        }
    }

    /**
     * Handles order cancellation on status change.
     *
     * @param Order $order Order to be cancelled
     *
     * @return void
     */
    public static function handleCancellation($order)
    {
        try {
            $cancellationRequest = new CancellationRequest($order->id_cart, false);
            $cancellationHandler = new CancellationHandler();

            $cancellationHandler->handle($cancellationRequest);

            self::getNotificationHub()->pushInfo(
                new NotificationText(
                    'biller.payment.order.cancellation.success.title'
                ),
                new NotificationText(
                    'biller.payment.order.cancellation.success.description'
                ),
                $order->id
            );
        } catch (Exception $exception) {
            Logger::logError($exception->getMessage());
            self::getNotificationHub()->pushError(
                new NotificationText('biller.payment.order.cancellation.title'),
                new NotificationText(
                    'biller.payment.order.cancellation.description',
                    array($exception->getMessage())
                ),
                $order->id
            );
            FlashBag::getInstance()->setMessage('error', Module::getInstanceByName('biller')->l(
                $exception->getMessage(),
                self::FILE_NAME
            ));

            Tools::redirectAdmin(self::getRedirectionHandler()->generateOrderPageUrl($order));
        }
    }

    /**
     * Handles order capture on status change.
     *
     * @param Order $order Order to be captured
     *
     * @return void
     */
    public static function handleCapture($order)
    {
        try {
            $shipmentService = new ShipmentService();
            $shipmentRequest = $shipmentService->createShipmentRequest($order);
            $shipmentHandler = new ShipmentHandler();
            /** @var NotificationHub $notificationHub */
            $notificationHub = ServiceRegister::getService(
                NotificationHubInterface::class
            );

            $shipmentHandler->handle($shipmentRequest);

            $notificationHub->pushInfo(
                new  NotificationText(
                    'biller.payment.order.capture.success.title'
                ),
                new  NotificationText(
                    'biller.payment.order.capture.success.description'
                ),
                $order->id
            );
        } catch (Exception $exception) {
            Logger::logError($exception->getMessage());
            self::getNotificationHub()->pushError(
                new NotificationText('biller.payment.order.capture.title'),
                new NotificationText(
                    'biller.payment.order.capture.description',
                    array($exception->getMessage())
                ),
                $order->id
            );
            FlashBag::getInstance()->setMessage(
                'error',
                Module::getInstanceByName('biller')->l(
                    $exception->getMessage(),
                    self::FILE_NAME
                )
            );

            Tools::redirectAdmin(self::getRedirectionHandler()->generateOrderPageUrl($order));
        }
    }

    /**
     * Handles Partial refund on Products return or Partial refund button.
     *
     * @param Cart $cart
     * @param array $lines
     * @param Order $order
     * @param array $quantity Used only for version 1.6
     * @param bool|null $isFullRefund Used only for version 1.6
     *
     * @return void
     *
     * @throws HttpRequestException
     * @throws InvalidOrderReferenceException
     * @throws RequestNotSuccessfulException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws HttpCommunicationException
     * @throws InvalidCurrencyCode
     * @throws RequestNotSuccessfulException
     * @throws CurrencyMismatchException
     * @throws InvalidTaxPercentage
     * @throws InvalidTypeException
     * @throws HttpCommunicationException
     * @throws QueryFilterInvalidParamException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     */
    public static function handlePartialRefund($cart, $lines, $order, $quantity = array(), $isFullRefund = null)
    {
        Bootstrap::init();

        $refundLines = self::getOrderRefundMapperVersion()->getRefundLines($lines, $quantity, $isFullRefund);

        $refundLineRequest = new RefundLineRequest(
            (string)$cart->id,
            $refundLines,
            'Partial Biller refund'
        );

        /** @var RefundAmountRequestService $refundAmountRequestService */
        $refundAmountRequestService =
            ServiceRegister::getService(
                RefundAmountRequestServiceInterface::class
            );

        $refundAmountRequestService->processPartialRefund($refundLineRequest);

        /** @var OrderStatusTransitionService $orderStatusTransitionService */
        $orderStatusTransitionService = ServiceRegister::getService(
            OrderStatusTransitionServiceInterface::class
        );
        $status = self::getBillerOrderStatus($order);
        if ($status->isRefundedPartially()) {
            $orderStatusTransitionService->updateStatus(
                $cart->id,
                $status
            );
        }
    }

    /**
     * Returns biller order status.
     *
     * @param Order $order PrestaShop order used for getting external order uuid
     *
     * @return Status Biller order status
     *
     * @throws HttpRequestException
     * @throws InvalidOrderReferenceException
     * @throws RequestNotSuccessfulException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws HttpCommunicationException
     */
    public static function getBillerOrderStatus($order)
    {
        /** @var OrderReferenceRepository $orderReferenceRepository */
        $orderReferenceRepository = RepositoryRegistry::getRepository(
            OrderReference::getClassName()
        );
        /** @var  OrderService $orderService */
        $orderService = ServiceRegister::getService(
            OrderService::class
        );

        $externalOrderUUID = $orderReferenceRepository->getExternalOrderUUIDByCartId($order->id_cart);

        return $externalOrderUUID ?
            $orderService->getStatus((int)$externalOrderUUID) :
            Status::fromString(Status::BILLER_STATUS_PENDING);
    }

    /**
     * Handles status change to processing in progress.
     * If previous status was refunded or cancelled, message should be pushed to notification hub and logger.
     *
     * @param Order $order Order that is being updated
     *
     * @return void
     */
    public static function handleProcessingStatus($order)
    {
        self::getNotificationHub()->pushWarning(
            new NotificationText(
                'biller.payment.order.synchronization.warning.title'
            ),
            new NotificationText(
                'biller.payment.order.synchronization.warning.description'
            ),
            $order->id
        );
    }

    /**
     * Returns notification hub class.
     *
     * @return NotificationHub
     */
    private static function getNotificationHub()
    {
        return ServiceRegister::getService(
            NotificationHubInterface::class
        );
    }

    /**
     * Returns RedirectionVersion class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 RedirectionVersion16 is returned.
     * For versions from 1.7.0.0 to 1.7.7.0 RedirectionVersion17  is returned.
     * For versions from 1.7.7.0+ RedirectionVersion177  is returned.
     *
     * @return RedirectionVersionInterface
     */
    private static function getRedirectionHandler()
    {
        return ServiceRegister::getService(RedirectionVersionInterface::class);
    }

    /**
     * Returns OrderRefundMapper class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 OrderRefundMapperVersion16 is returned.
     * For versions from 1.7.0.0+ OrderRefundMapperVersion177  is returned.
     *
     * @return OrderRefundMapperVersion
     */
    private static function getOrderRefundMapperVersion()
    {
        return ServiceRegister::getService(OrderRefundMapperVersion::class);
    }
}
