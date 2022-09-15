<?php

namespace Biller\PrestaShop\Utility\Services;

use Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference;
use Biller\BusinessLogic\Order\OrderService as OrderServiceCore;
use Biller\Infrastructure\ORM\RepositoryRegistry;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Data\OrderMapper;
use Biller\PrestaShop\Repositories\OrderReferenceRepository;
use Cart;

/**
 * Class OrderService. For creating Biller order request.
 *
 * @package Biller\PrestaShop\Utility\Services
 */
class OrderService
{
    /**
     * Creates Biller order request from given cart.
     *
     * @param Cart $cart PrestaShop cart
     * @param array $responseURLs Response URLs to be redirected to upon Biller payment resolve
     * @param array $companyData Biller input fields - company name, registration number and vat number
     *
     * @return string Biller payment page URL
     *
     * @throws \Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException
     * @throws \Biller\Domain\Exceptions\CurrencyMismatchException
     * @throws \Biller\Domain\Exceptions\InvalidArgumentException
     * @throws \Biller\Domain\Exceptions\InvalidCountryCode
     * @throws \Biller\Domain\Exceptions\InvalidCurrencyCode
     * @throws \Biller\Domain\Exceptions\InvalidLocale
     * @throws \Biller\Domain\Exceptions\InvalidTaxPercentage
     * @throws \Biller\Domain\Exceptions\InvalidTypeException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createRequest($cart, $responseURLs, $companyData)
    {
        /** @var OrderServiceCore $orderService */
        $orderService = ServiceRegister::getService(OrderServiceCore::class);
        /** @var OrderReferenceRepository $orderReferenceRepository */
        $orderReferenceRepository = RepositoryRegistry::getRepository(
            OrderReference::getClassName()
        );

        $webshopUID = $orderReferenceRepository->getWebshopUID();
        $orderRequest = (new OrderMapper($cart, $webshopUID, $responseURLs, $companyData))->mapOrder();

        return $orderService->create($orderRequest);
    }
}
