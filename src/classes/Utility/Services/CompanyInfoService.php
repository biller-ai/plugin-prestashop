<?php

namespace Biller\PrestaShop\Utility\Services;

use Biller\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Biller\Infrastructure\ORM\QueryFilter\Operators;
use Biller\Infrastructure\ORM\QueryFilter\QueryFilter;
use Biller\Infrastructure\ORM\RepositoryRegistry;
use Biller\PrestaShop\Entity\CompanyInfo;
use Biller\PrestaShop\Repositories\CompanyInfoRepository;

/**
 * Class CompanyInfoService. For manipulation of CompanyInfo data.
 *
 * @package Biller\PrestaShop\Utility\Services
 */
class CompanyInfoService
{
    /**
     * @var CompanyInfoRepository
     */
    private $repository;

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Gets company info for the order with the given id.
     *
     * @param int $orderId Order id
     *
     * @return CompanyInfo|null
     */
    public function getCompanyInfo($orderId)
    {
        $queryFilter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $queryFilter->where('orderId', Operators::EQUALS, $orderId);
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @var CompanyInfo $companyInfo */
        $companyInfo = $this->getRepository()->selectOne($queryFilter);

        return $companyInfo;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Save company info to database
     *
     * @param CompanyInfo $companyInfo
     *
     * @return void
     */
    public function saveCompanyInfo(CompanyInfo $companyInfo)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getRepository()->save($companyInfo);
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns repository instance.
     *
     * @return RepositoryInterface CompanyInfo repository
     */
    protected function getRepository()
    {
        if ($this->repository === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->repository = RepositoryRegistry::getRepository(CompanyInfo::getClassName());
        }

        return $this->repository;
    }
}
