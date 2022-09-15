<?php

namespace Biller\PrestaShop\Utility\FormBuilder;

use Biller\Domain\Order\Status;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInteface;

/**
 * Class AuthorizedFormBuilder. For building the module configuration form if merchant is logged into Biller.
 *
 * @package Biller\PrestaShop\Utility\FormBuilder
 */
class AuthorizedFormBuilder extends BaseFormBuilder
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'AuthorizedFormBuilder';

    /** @var string */
    const TAB_NAME_SETTINGS = 'settings';
    const TAB_NAME_NOTIFICATIONS = 'notifications';

    /** @var string */
    const NOTIFICATION_ENDPOINT_URL_KEY = 'NOTIFICATION_ENDPOINT_URL';

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
        return array_merge(parent::getInputs(), $this->getInputsSettings(), $this->getInputsNotifications());
    }

    /**
     * @inheritDoc
     */
    protected function getButtons()
    {
        $buttons = parent::getButtons();

        $buttons[] = array(
            'class' => 'btn btn-default pull-right hidden',
            'type' => 'button',
            'id' => 'btn_next',
            'name' => 'btn_next',
            'title' => $this->module->l('Next', self::FILE_NAME),
        );

        $buttons[] = array(
            'class' => 'btn btn-default pull-right hidden',
            'type' => 'button',
            'id' => 'btn_previous',
            'name' => 'btn_previous',
            'title' => $this->module->l('Previous', self::FILE_NAME),
        );

        return $buttons;
    }

    /**
     * @inheritDoc
     */
    protected function getTabs()
    {
        $tabs = parent::getTabs();

        $tabs['settings'] = $this->module->l('Settings', self::FILE_NAME);
        $tabs['notifications'] = $this->module->l('Notifications', self::FILE_NAME);

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

        $defaultValues[Config::NAME_KEY] = $paymentConfiguration->getName();
        $defaultValues[Config::DESCRIPTION_KEY] = $paymentConfiguration->getDescription();

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $orderStatusMap = $orderStatusMapping->getOrderStatusMap();

        foreach ($this->getOrderStatusMapper()->getDefaultOrderStatusMap() as $key => $value) {
            $defaultValues[$key] = $orderStatusMap[$key];
        }

        $defaultValues[self::NOTIFICATION_ENDPOINT_URL_KEY] = $this->module->getAction(
            'NotificationsHub',
            'fetchNotifications',
            array(
                'ajax' => true
            )
        );

        return $defaultValues;
    }

    /**
     * Returns inputs for settings section.
     *
     * @return array Array of settings section inputs
     */
    private function getInputsSettings()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Name', self::FILE_NAME),
            'name' => Config::NAME_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_SETTINGS,
            'desc' => $this->module->l('This controls the title which the user sees during checkout.', self::FILE_NAME),
            'class' => 'biller-medium-input',
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->module->l('Description', self::FILE_NAME),
            'name' => Config::DESCRIPTION_KEY,
            'required' => true,
            'tab' => self::TAB_NAME_SETTINGS,
            'desc' => $this->module->l('Payment method description that the customer will see on your checkout.', self::FILE_NAME),
            'class' => 'biller-medium-input',
        );

        $inputs[] = array(
            'type' => 'biller-h2',
            'tab' => self::TAB_NAME_SETTINGS,
            'title' => $this->module->l('Order status mappings', self::FILE_NAME),
            'name' => ''
        );

        /** @var BillerOrderStatusMapping $orderStatusMapping */
        $orderStatusMapping = ServiceRegister::getService(BillerOrderStatusMappingInterface::class);
        $availableStatuses = $orderStatusMapping->getAvailableStatuses();

        foreach ($this->getOrderStatusMapper()->getDefaultOrderStatusMap() as $key => $value) {
            $inputs[] = $this->renderOrderStatusMappingSelect(
                $key,
                $availableStatuses
            );
        }

        return $inputs;
    }

    /**
     * Returns array representing order status mapping input for order status represented by $orderStatusKey.
     *
     * @param string $orderStatusKey Key representing Biller order status
     * @param array $availableStatuses Available order statuses representing select options
     *
     * @return array Order status mapping HelperForm select input
     */
    private function renderOrderStatusMappingSelect($orderStatusKey, array $availableStatuses)
    {
        $label = BillerOrderStatusMapping::getOrderStatusLabel($orderStatusKey);

        if ($orderStatusKey === Status::BILLER_STATUS_PARTIALLY_CAPTURED ||
            $orderStatusKey === Status::BILLER_STATUS_PARTIALLY_REFUNDED) {
            $availableStatuses[] = array(
                'id_order_state' => 0,
                'name' => $this->module->l('None', self::FILE_NAME)
            );
        }

        return array(
            'type' => 'select',
            'label' => $label,
            'desc' => vsprintf($this->module->l('Mapped PrestaShop order status for the %s status on Biller.', self::FILE_NAME), array($label)),
            'name' => $orderStatusKey,
            'tab' => self::TAB_NAME_SETTINGS,
            'options' => array(
                'query' => $availableStatuses,
                'id' => 'id_order_state',
                'name' => 'name',
            ),
        );
    }

    /**
     * Returns inputs for notifications section.
     *
     * @return array Array of notifications section inputs
     */
    private function getInputsNotifications()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'biller-notification-tab',
            'tab' => self::TAB_NAME_NOTIFICATIONS,
            'name' => '',
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => '',
            'name' => self::NOTIFICATION_ENDPOINT_URL_KEY,
            'tab' => self::TAB_NAME_NOTIFICATIONS,
            'desc' => '',
            'class' => 'hidden',
        );

        return $inputs;
    }

    /**
     * Returns order status mapping class.
     *
     * @return BillerOrderStatusMapping
     */
    public function getOrderStatusMapper()
    {
        return ServiceRegister::getService(
            BillerOrderStatusMappingInteface::class
        );
    }
}
