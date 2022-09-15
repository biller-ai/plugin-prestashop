<?php

namespace Biller\PrestaShop\Utility\Version\TemplateAndJs;

use Biller\PrestaShop\Utility\Version\TemplateAndJs\Contract\TemplateAndJSVersionInterface;

/**
 * Class TemplateAndJsVersion16. Used for getting template and js file names.
 * Used for versions from 1.6.0.14 to 1.7.0.0.
 *
 * @package Biller\PrestaShop\Utility\TemplateAndJs\Redirection
 */
class TemplateAndJsVersion16 implements TemplateAndJSVersionInterface
{
    /**
     * @inheritDoc
     */
    public function getCheckoutJS()
    {
        return 'views/js/front/checkout16.js';
    }

    /**
     * @inheritDoc
     */
    public function getAddOrderSummaryTemplate()
    {
        return 'views/templates/admin/add_order_summary_16_17.tpl';
    }

    /**
     * @inheritDoc
     */
    public function getPaymentReturnTemplate()
    {
        return 'payment_return.tpl';
    }

    /**
     * @inheritDoc
     */
    public function getAddOrderSummaryJS()
    {
        return 'views/js/admin/addOrderSummary1617.js';
    }

    /**
     * @inheritDoc
     */
    public function getTabLinkTemplate()
    {
        return 'tab_link16.tpl';
    }

    /**
     * @inheritDoc
     */
    public function getOrderBillerSectionTemplate()
    {
        return 'order_biller_section16.tpl';
    }
}
