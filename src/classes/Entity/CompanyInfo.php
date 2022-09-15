<?php

namespace Biller\PrestaShop\Entity;

use Biller\Infrastructure\ORM\Configuration\EntityConfiguration;
use Biller\Infrastructure\ORM\Configuration\IndexMap;
use Biller\Infrastructure\ORM\Entity;

/**
 * Class CompanyInfo. For storing order company info.
 *
 * @package Biller\PrestaShop\Entity
 */
class CompanyInfo extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /** @var int */
    protected $orderId;
    /**
     * @var string
     */
    protected $companyName;
    /**
     * @var null|string
     */
    protected $vatNumber;
    /**
     * @var null|string
     */
    protected $registrationNumber;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'orderId', 'companyName', 'vatNumber', 'registrationNumber'
    );

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIntegerIndex('orderId')
            ->addStringIndex('companyName')
            ->addStringIndex('vatNumber')
            ->addStringIndex('registrationNumber');

        return new EntityConfiguration($map, 'CompanyInfo');
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $data = parent::toArray();
        $data['orderId'] = $this->orderId;
        $data['companyName'] = $this->companyName;
        $data['vatNumber'] = $this->vatNumber;
        $data['registrationNumber'] = $this->registrationNumber;

        return $data;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string|null
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param string|null $vatNumber
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    }

    /**
     * @return string|null
     */
    public function getRegistrationNumber()
    {
        return $this->registrationNumber;
    }

    /**
     * @param string|null $registrationNumber
     */
    public function setRegistrationNumber($registrationNumber)
    {
        $this->registrationNumber = $registrationNumber;
    }
}
