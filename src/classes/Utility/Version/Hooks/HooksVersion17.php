<?php

namespace Biller\PrestaShop\Utility\Version\Hooks;

use Biller\PrestaShop\Utility\Version\Hooks\Contract\HooksVersionInterface;

/**
 * Class HooksVersion17. Used for getting hooks.
 * Used for versions from 1.7.0.0 to 1.7.7.0.
 *
 * @package Biller\PrestaShop\Utility\Version\Hooks
 */
class HooksVersion17 implements HooksVersionInterface
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
            'actionObjectShopAddBefore'
        );
    }
}
