<?php

namespace Biller\PrestaShop\BusinessService;

use Biller\BusinessLogic\Integration\CancellationRequest;
use Biller\PrestaShop\Exception\BillerCancellationRejectedException;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\ChangeOrderStatusException;
use Biller\BusinessLogic\Integration\Cancellation\CancellationService as CancellationServiceInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\ValueObject\OrderId;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use Exception;

/**
 * Class CancellationService. Handles cancellation errors for PrestaShop.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class CancellationService implements CancellationServiceInterface
{
    /**
     * Throws exception in case of cancellation error on Biller.
     *
     * @param CancellationRequest $request
     * @param Exception $reason
     *
     * @throws BillerCancellationRejectedException
     */
    public function reject($request, Exception $reason)
    {
        throw new BillerCancellationRejectedException($reason->getMessage());
    }

    /**
     * PrestaShop does not allow partial cancellation, so this method will never be used.
     *
     * @inheritDoc
     */
    public function getAllItems($shopOrderId)
    {
        return array();
    }
}
