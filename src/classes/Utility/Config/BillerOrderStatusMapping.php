<?php

namespace Biller\PrestaShop\Utility\Config;

use Biller\Domain\Order\Status;
use Biller\Infrastructure\Configuration\Configuration as ConfigurationInterface;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\InfrastructureService\ConfigurationService;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Module;

/**
 * Class BillerOrderStatusMapping. Implementation of BillerOrderStatusMapping interface.
 *
 * @package Biller\PrestaShop\Utility\Config
 */
class BillerOrderStatusMapping implements Contract\BillerOrderStatusMapping
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'BillerOrderStatusMapping';

    /** @var ConfigurationService */
    private $configurationService;

    /**
     * @var int[]
     */
    private $prestaMap;

    /**
     * @var array
     */
    private $statusMap;

    /**
     * @return string
     */
    public static function getOrderStatusLabel($orderStatus)
    {
        $module = Module::getInstanceByName('biller');

        $orderStatusLabelMap = array(
            Status::BILLER_STATUS_PENDING => $module->l('Pending', self::FILE_NAME),
            Status::BILLER_STATUS_ACCEPTED => $module->l('Accepted', self::FILE_NAME),
            Status::BILLER_STATUS_REFUNDED => $module->l('Refunded', self::FILE_NAME),
            Status::BILLER_STATUS_CAPTURED => $module->l('Captured', self::FILE_NAME),
            Status::BILLER_STATUS_FAILED => $module->l('Failed', self::FILE_NAME),
            Status::BILLER_STATUS_REJECTED => $module->l('Rejected', self::FILE_NAME),
            Status::BILLER_STATUS_CANCELLED => $module->l('Cancelled', self::FILE_NAME),
            Status::BILLER_STATUS_PARTIALLY_REFUNDED => $module->l('Partially refunded', self::FILE_NAME),
            Status::BILLER_STATUS_PARTIALLY_CAPTURED => $module->l('Partially captured', self::FILE_NAME)
        );

        return $orderStatusLabelMap[$orderStatus];
    }

    /**
     * @inheritDoc
     */
    public function getAvailableStatuses()
    {
        $availableOrderStatuses = $this->getConfigurationService()->getAvailableOrderStatuses();

        if (\Context::getContext()->language->id != 1) {
            $availableOrderStatuses = $this->mapIdLanguage(
                \Context::getContext()->language->id,
                $availableOrderStatuses
            );
        }

        return $availableOrderStatuses;
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusMap()
    {
        $defaultOrderStatusMap = $this->getDefaultOrderStatusMap();
        $orderStatusMap = $this->getConfigurationService()->getOrderStatusMap();

        return $orderStatusMap ?: $defaultOrderStatusMap;
    }


    /**
     * @inheritDoc
     */
    public function saveOrderStatusMap($orderStatusMap)
    {
        return $this->getConfigurationService()->saveOrderStatusMap($orderStatusMap);
    }

    /**
     * Returns default status map based on PrestaShop version.
     *
     * @return int[]|string[]
     */
    public function getDefaultOrderStatusMap()
    {
        if (!$this->prestaMap) {
            $this->prestaMap = $this->mapPrestaStatuses();
        }

        return $this->prestaMap;
    }

    /**
     * @return int[]
     */
    private function mapPrestaStatuses()
    {
        return array(
            Status::BILLER_STATUS_PENDING => $this->getPrestaShopOrderStatusId('On backorder (not paid)'),
            Status::BILLER_STATUS_ACCEPTED => $this->getPrestaShopOrderStatusId('Processing in progress'),
            Status::BILLER_STATUS_REFUNDED => $this->getPrestaShopOrderStatusId(
                $this->getOrderRefundMapperVersion()->getRefundedStatusLabel()
            ),
            Status::BILLER_STATUS_CAPTURED => $this->getPrestaShopOrderStatusId('Shipped'),
            Status::BILLER_STATUS_FAILED => $this->getPrestaShopOrderStatusId('Payment error'),
            Status::BILLER_STATUS_REJECTED => $this->getPrestaShopOrderStatusId('Payment error'),
            Status::BILLER_STATUS_CANCELLED => $this->getPrestaShopOrderStatusId('Canceled'),
            Status::BILLER_STATUS_PARTIALLY_REFUNDED => 0,
            Status::BILLER_STATUS_PARTIALLY_CAPTURED => 0,
        );
    }

    /**
     * Returns ID of PrestaShop status based on status name given as first parameter.
     *
     * @param string $status Status which ID is returned
     *
     * @return int ID of PrestaShop status
     */
    private function getPrestaShopOrderStatusId($status)
    {
        return $this->getStatusMap()[$status];
    }

    /**
     * @return array
     */
    private function getStatusMap()
    {
        if (!$this->statusMap) {
            $this->statusMap = array_column(
                $this->getConfigurationService()->getAvailableOrderStatuses(),
                'id_order_state',
                'name'
            );
        }

        return $this->statusMap;
    }

    /**
     * Gets configuration service instance.
     *
     * @return ConfigurationService Configuration service instance
     */
    private function getConfigurationService()
    {
        if (!$this->configurationService) {
            $this->configurationService = ServiceRegister::getService(ConfigurationInterface::CLASS_NAME);
        }

        return $this->configurationService;
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

    /**
     * Translate order statuses for label if language is different from english.
     *
     * @param int $id
     * @param array $englishStatuses Array of statuses that are translated to english
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function mapIdLanguage($id, $englishStatuses)
    {
        $map = array();

        foreach ($englishStatuses as $status) {
            $orderState = new \OrderState($status['id_order_state']);
            $status['name'] = $orderState->name[$id];
            $map[] = $status;
        }

        return $map;
    }
}
