<?php

namespace Biller\PrestaShop\Utility\Version\TemplateAndJs\Contract;

/**
 * Contains methods for template and js files that vary by PrestaShop versions.
 */
interface TemplateAndJSVersionInterface
{
    /**
     * Returns checkout js path.
     *
     * @return string
     */
    public function getCheckoutJS();

    /**
     * Returns Add order summary template path.
     *
     * @return string
     */
    public function getAddOrderSummaryTemplate();

    /**
     * Returns Payment return path.
     *
     * @return string
     */
    public function getPaymentReturnTemplate();

    /**
     * Returns Add order summary js path.
     *
     * @return string
     */
    public function getAddOrderSummaryJS();

    /**
     *  Returns Tab link template path.
     *
     * @return string
     */
    public function getTabLinkTemplate();

    /**
     *  Returns Order Biller Section template path.
     *
     * @return string
     */
    public function getOrderBillerSectionTemplate();



}
