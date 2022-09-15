<?php

namespace Biller\PrestaShop\Utility\FormBuilder;

use Biller\Domain\Order\Status;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;

/**
 * AuthorizedFormBuilder class.
 *
 * @package Biller\PrestaShop\Utility\FormBuilder
 */
class AuthorizedFormBuilder extends BaseFormBuilder
{
    /** @var string */
    const TAB_NAME_SETTINGS = 'settings';
    const TAB_NAME_NOTIFICATIONS = 'notifications';

    /**
     * @param $module
     */
    public function __construct($module)
    {
        parent::__construct($module);
    }

    /**
     * @inheritDoc
     */
    protected function getInputs()
    {
        $inputs = parent::getInputs();

        $inputs = array_merge($inputs, $this->getInputsSettings());
        $inputs = array_merge($inputs, $this->getInputsNotifications());

        return $inputs;
    }

    /**
     * @inheritDoc
     */
    protected function getTabs()
    {
        $tabs = parent::getTabs();

        $tabs['settings'] = $this->module->l('Settings');
        $tabs['notifications'] = $this->module->l('Notifications');

        return $tabs;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultValues($formSubmitted)
    {
        $defaultValues = parent::getDefaultValues($formSubmitted);

        /** @var BillerPaymentConfiguration $paymentConfiguration */
        $paymentConfiguration = ServiceRegister::getService(BillerPaymentConfigurationInterface::class);

        $defaultValues[Config::BILLER_NAME_KEY] = $paymentConfiguration->getName();
        $defaultValues[Config::BILLER_DESCRIPTION_KEY] = $paymentConfiguration->getDescription();

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $orderStatusMap = $orderStatusMapping->getOrderStatusMap();

        $defaultValues[Status::BILLER_STATUS_PENDING] = $orderStatusMap[Status::BILLER_STATUS_PENDING];
        $defaultValues[Status::BILLER_STATUS_ACCEPTED] = $orderStatusMap[Status::BILLER_STATUS_ACCEPTED];
        $defaultValues[(Status::BILLER_STATUS_REFUNDED)] = $orderStatusMap[Status::BILLER_STATUS_REFUNDED];
        $defaultValues[(Status::BILLER_STATUS_PARTIALLY_REFUNDED)] =
            $orderStatusMap[Status::BILLER_STATUS_PARTIALLY_REFUNDED];
        $defaultValues[(Status::BILLER_STATUS_CAPTURED)] = $orderStatusMap[Status::BILLER_STATUS_CAPTURED];
        $defaultValues[(Status::BILLER_STATUS_FAILED)] = $orderStatusMap[Status::BILLER_STATUS_FAILED];
        $defaultValues[(Status::BILLER_STATUS_REJECTED)] = $orderStatusMap[Status::BILLER_STATUS_REJECTED];
        $defaultValues[(Status::BILLER_STATUS_CANCELLED)] = $orderStatusMap[Status::BILLER_STATUS_CANCELLED];
        $defaultValues[(Status::BILLER_STATUS_PARTIALLY_CAPTURED)] =
            $orderStatusMap[Status::BILLER_STATUS_PARTIALLY_CAPTURED];

        return $defaultValues;
    }

    /**
     * Returns inputs for settings section.
     *
     * @return array Array of settings section inputs.
     */
    private function getInputsSettings()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Name'),
            'name' => Config::BILLER_NAME_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_SETTINGS,
            'desc' => $this->module->l('This controls the title which the user sees during checkout.'),
            'class' => 'biller-medium-input',
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Description'),
            'name' => Config::BILLER_DESCRIPTION_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_SETTINGS,
            'desc' => $this->module->l(
                'The payment solution that advances both sides. We pay out every invoice on time.'
            ),
            'class' => 'biller-medium-input',
        );

        $inputs[] = array(
            'type' => 'biller-h2',
            'tab' => self::TAB_NAME_SETTINGS,
            'title' => $this->module->l('Order status mappings'),
            'name' => ''
        );

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $availableStatuses = $orderStatusMapping->getAvailableStatuses();

        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Pending',
            Status::BILLER_STATUS_PENDING,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Accepted',
            Status::BILLER_STATUS_ACCEPTED,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Refunded',
            Status::BILLER_STATUS_REFUNDED,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Partially refunded',
            Status::BILLER_STATUS_PARTIALLY_REFUNDED,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Captured',
            Status::BILLER_STATUS_CAPTURED,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Partially captured',
            Status::BILLER_STATUS_PARTIALLY_CAPTURED,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Failed',
            Status::BILLER_STATUS_FAILED,
            $availableStatuses
        );
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Rejected',
            Status::BILLER_STATUS_REJECTED,
            $availableStatuses)
        ;
        $inputs[] = $this->renderOrderStatusMappingSelect(
            'Cancelled',
            Status::BILLER_STATUS_CANCELLED,
            $availableStatuses
        );

        return $inputs;
    }

    /**
     * Returns inputs for notifications section.
     *
     * @return array Array of notifications section inputs.
     */
    private function getInputsNotifications()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'biller-notification-tab',
            'tab' => self::TAB_NAME_NOTIFICATIONS,
            'name' => '',
        );

        return $inputs;
    }

    /**
     * Returns array representing order status mapping input.
     *
     * @param string $label Order status mapping select element's label.
     * @param string $name Order status mapping select element's name.
     * @param array $availableStatuses Available order statuses representing select options.
     *
     * @return array Order status mapping HelperForm select input.
     */
    private function renderOrderStatusMappingSelect($label, $name, array $availableStatuses)
    {
        return array(
            'type' => 'select',
            'label' => $this->module->l($label),
            'desc' => $this->module->l("Mapped PrestaShop order status for the $label status on Biller."),
            'name' => $name,
            'tab' => self::TAB_NAME_SETTINGS,
            'options' => array(
                'query' => $availableStatuses,
                'id' => 'id_order_state',
                'name' => 'name',
            ),
        );
    }
}
