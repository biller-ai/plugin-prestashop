<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\Domain\Refunds\RefundCollection;
use Biller\BusinessLogic\Integration\Refund\OrderRefundService as OrderRefundServiceInterface;

class OrderRefundService implements OrderRefundServiceInterface
{

    /**
     * @inheritDoc
     */
    public function refund($externalExternalOrderUUID, RefundCollection $billerRefunds = null)
    {
        // TODO: Implement refund() method.
    }
}