<?php

namespace Biller\PrestaShop\Utility\Version\Hooks;

use Biller\PrestaShop\Utility\Version\Hooks\Contract\HooksVersionInterface;

/**
 * Class HooksVersion16. Used for getting hooks.
 * Used for versions from 1.6.0.14 to 1.7.0.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Hooks
 */
class HooksVersion16 implements HooksVersionInterface
{
    /**
     * @inheritDoc
     */
    public function getHooks()
    {
        return array(
            'displayAdminOrderTabOrder',
            'displayAdminOrderContentOrder',
            'actionOrderStatusUpdate',
            'actionOrderSlipAdd',
            'paymentOptions',
            'displayHeader',
            'actionAdminControllerSetMedia',
            'actionObjectAddressAddBefore',
            'actionOrderEdited',
            'displayBackOfficeHeader',
            'actionValidateOrder',
            'sendMailAlterTemplateVars',
            'actionObjectShopDeleteAfter',
            'actionObjectShopAddBefore',
            'payment',
            'paymentReturn',
            'displayPaymentTop',
            'actionDispatcher'
        );
    }
}
