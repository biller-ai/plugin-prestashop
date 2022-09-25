<?php

namespace Biller\PrestaShop\Utility;

use Module;

/**
 * Class TranslationUtility. For manipulating languages and internationalization.
 *
 * @package Biller\PrestaShop\Utility
 */
class TranslationUtility
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'TranslationUtility';


    /** @var array */
    private $translations;

    /**
     * Initializes notification message translation dictionary.
     */
    public function __construct()
    {
        $module = Module::getInstanceByName('biller');

        $this->translations['biller.payment.order.capture.title'] = $module->l('Order capture is rejected by Biller.', self::FILE_NAME);
        $this->translations['biller.payment.order.capture.description'] = $module->l('Biller error message: %s', self::FILE_NAME);
        $this->translations['biller.payment.order.cancellation.title'] =
            $module->l('Cancellation is rejected by Biller.', self::FILE_NAME);
        $this->translations['biller.payment.order.cancellation.description'] = $module->l('Biller error message: %s', self::FILE_NAME);
        $this->translations['biller.payment.webhook.notification.order_status_changed_error.title'] =
            $module->l('Order line refund failed', self::FILE_NAME);
        $this->translations['biller.payment.webhook.notification.order_status_changed_error.description'] =
            $module->l('Error message: %s', self::FILE_NAME);
        $this->translations['biller.payment.amount.refund.error.title'] = $module->l('Order amount refund failed!', self::FILE_NAME);
        $this->translations['biller.payment.amount.refund.error.description'] =
            $module->l('Order refund finished with errors: %s', self::FILE_NAME);
        $this->translations['biller.payment.refund.line.error.title'] = $module->l('Order line refund failed!', self::FILE_NAME);
        $this->translations['biller.payment.refund.line.error.description'] =
            $module->l('Order refund finished with errors: %s', self::FILE_NAME);
        $this->translations['biller.payment.webhook.error.title'] =
            $module->l('Automatic order synchronization failed!', self::FILE_NAME);
        $this->translations['biller.payment.webhook.error.description'] =
            $module->l('There was an error during the synchronization of the order %s. The plugin will not be able to synchronize any further changes automatically. Failure message: %s', self::FILE_NAME);

        $this->translations['biller.payment.order.cancellation.success.title'] =
            $module->l('Order cancelled successfully.', self::FILE_NAME);
        $this->translations['biller.payment.order.cancellation.success.description'] =
            $module->l('Order successfully cancelled on Biller portal.', self::FILE_NAME);
        $this->translations['biller.payment.order.capture.success.title'] =
            $module->l('Order captured successfully.', self::FILE_NAME);
        $this->translations['biller.payment.order.capture.success.description'] =
            $module->l('Order successfully captured on Biller portal.', self::FILE_NAME);
        $this->translations['biller.payment.order.synchronization.warning.title'] =
            $module->l('Shop changes not synchronized.', self::FILE_NAME);
        $this->translations['biller.payment.order.synchronization.warning.description'] =
            $module->l('Order changes are not synchronized to Biller.', self::FILE_NAME);
        $this->translations['biller.payment.address.synchronization.warning.title'] =
            $module->l('Shop changes not synchronized.', self::FILE_NAME);
        $this->translations['biller.payment.address.synchronization.warning.description'] =
            $module->l('Order changes are not synchronized to Biller.', self::FILE_NAME);
    }

    /**
     * Translates notification message if message key is in dictionary.
     *
     * @param string $messageKey Message key to index the translation dictionary array
     * @param array $messageParams Message parameters for interpolation into the message
     *
     * @return string Translated message or empty string if message key isn't in the dictionary
     */
    public function translateMessage($messageKey, array $messageParams)
    {
        if (!array_key_exists($messageKey, $this->translations)) {
            return '';
        }

        return vsprintf($this->translations[$messageKey], $messageParams);
    }
}
