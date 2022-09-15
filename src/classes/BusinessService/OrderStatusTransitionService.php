<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\BusinessLogic\API\DTO\Response\RejectResponse;
use Biller\Domain\Order\Status;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;

class OrderStatusTransitionService implements OrderStatusTransitionServiceInterface
{

    /**
     * @inheritDoc
     */
    public function updateStatus($orderUUID, Status $status)
    {
        // TODO: Implement updateStatus() method.
    }

    /**
     * @inheritDoc
     */
    public function rejectRefund($shopOrderId, RejectResponse $response)
    {
        // TODO: Implement rejectRefund() method.
    }
}