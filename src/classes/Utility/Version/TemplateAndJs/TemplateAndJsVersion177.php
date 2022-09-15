<?php

namespace Biller\PrestaShop\Utility\Version\TemplateAndJs;

/**
 * Class TemplateAndJsVersion177. Used for getting template and js file names.
 * Used for versions from 1.7.7.0+.
 *
 * @package Biller\PrestaShop\Utility\TemplateAndJs\Redirection
 */
class TemplateAndJsVersion177 extends TemplateAndJsVersion17
{
    /**
     * @inheritDoc
     */
    public function getTabLinkTemplate()
    {
        return 'tab_link177.tpl';
    }

    /**
     * @inheritDoc
     */
    public function getOrderBillerSectionTemplate()
    {
        return 'order_biller_section177.tpl';
    }

    /**
     * @inheritDoc
     */
    public function getAddOrderSummaryTemplate()
    {
        return 'views/templates/admin/add_order_summary_177.tpl';
    }

    /**
     * @inheritDoc
     */
    public function getAddOrderSummaryJS()
    {
        return 'views/js/admin/addOrderSummary177.js';
    }
}
