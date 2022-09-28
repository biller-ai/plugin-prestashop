<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\BusinessLogic\API\DTO\Response\RejectResponse;
use Biller\Domain\Order\Status;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Exception\BillerRefundRejectedException;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Order;

/**
 * Class OrderStatusTransitionService. Implements core interface and handles order status update and order refund
 * rejection.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class OrderStatusTransitionService implements OrderStatusTransitionServiceInterface
{
    /**
     * @inheritDoc
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function updateStatus($orderUUID, Status $status)
    {
        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(
            BillerOrderStatusMappingInterface::class
        );

        $idOrder = Order::getOrderByCartId($orderUUID);

        if (!$idOrder) {
            return;
        }

        $statusId = $orderStatusMapping->getOrderStatusMap()[(string)$status];

        if ($statusId && $statusId !== (new \Order($idOrder))->current_state) {
            $history = new \OrderHistory();
            $history->id_order = $idOrder;
            $history->id_employee = isset(\Context::getContext()->employee->id) ? \Context::getContext(
            )->employee->id : "0";
            $history->changeIdOrderState($statusId, $idOrder, true);
            $history->add();
        }
    }

    /**
     * @inheritDoc
     *
     * @throws \Biller\PrestaShop\Exception\BillerRefundRejectedException
     */
    public function rejectRefund($shopOrderId, RejectResponse $response)
    {
        throw new BillerRefundRejectedException($response->getDetails());
    }
}
