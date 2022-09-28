<?php

use Biller\PrestaShop\Data\Validator;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Services\CompanyInfoService;
use Biller\PrestaShop\Utility\Services\OrderService;
use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;
use Biller\PrestaShop\Utility\Hash;
use Biller\PrestaShop\Exception\BillerTokenNotValidException;

/**
 * Class BillerPaymentModuleFrontController. Used for handling payment request on checkout page.
 */
class BillerPaymentModuleFrontController extends ModuleFrontController
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'BillerPaymentModuleFrontController';

    public function __construct()
    {
        parent::__construct();

        Bootstrap::init();
    }

    /**
     * Handle presta payment and redirect to Biller.
     *
     * @return void
     */
    public function postProcess()
    {
        try {
            $orderId = Tools::getValue('orderId', null);
            if ($orderId) {
                // payment link
                $token = Tools::getValue('token');
                if (!Hash::getInstance()->checkKey($orderId, $token)) {
                    throw  new BillerTokenNotValidException('Order token is not valid.');
                }
                $order = new Order($orderId);
                $cart = new Cart($order->id_cart);
            } else {
                // checkout
                if (!Validator::validate()) {
                    $this->redirectError(Validator::getErrors());
                }

                $cart = Context::getContext()->cart;
            }

            /** @var OrderService $orderService */
            $orderService = ServiceRegister::getService(OrderService::class);

            $responseURLs = $this->getResponseURLs($orderId);
            $paymentPageURL = $orderService->createRequest($cart, $responseURLs, $this->getCompanyData($cart));

            Tools::redirect($paymentPageURL);
        } catch (Exception $e) {
            $this->redirectError([$e->getMessage()]);
        }
    }

    /**
     * Redirect to checkout page with error message.
     *
     * @param array $messages Error message to be displayed at checkout page
     *
     * @return void
     */
    private function redirectError($messages)
    {
        foreach ($messages as $message) {
            $this->errors[] = $this->module->l($this->formatMessage($message), self::FILE_NAME);
        }

        $this->getRedirectionHandler()->paymentErrorRedirect($this->errors);
    }

    /**
     * If message is type of json string, decode it and return only values
     *
     * @param string $message Message to be decoded
     *
     * @return string
     */
    private function formatMessage($message)
    {
        $decodedMessage = json_decode($message, true);

        if (!$decodedMessage) {
            return $message;
        }

        return implode('', $decodedMessage);
    }

    /**
     * Returns RedirectionVersion class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 RedirectionVersion16 is returned.
     * For versions from 1.7.0.0 to 1.7.7.0 RedirectionVersion17  is returned.
     * For versions from 1.7.7.0+ RedirectionVersion177  is returned.
     *
     * @return RedirectionVersionInterface
     */
    private function getRedirectionHandler()
    {
        return ServiceRegister::getService(RedirectionVersionInterface::class);
    }

    /**
     * Gets appropriate Biller order processing response URLs.
     *
     * @param int|null $orderId ID of order in case of link payment, null in case of checkout payment
     *
     * @return string[] Biller order processing response URLs
     */
    private function getResponseURLs($orderId)
    {
        $responseURLs = array();

        $moduleName = $this->module->name;

        $queryParameters = array();
        if ($orderId) {
            $queryParameters['orderId'] = $orderId;
        }

        $responseURLs['error'] =
            Context::getContext()->link->getModuleLink($moduleName, 'error', $queryParameters, true);
        $responseURLs['cancel'] =
            Context::getContext()->link->getModuleLink($moduleName, 'cancel', $queryParameters, true);
        $responseURLs['webhooks'] =
            Context::getContext()->link->getModuleLink($moduleName, 'webhooks', array(), true);
        $responseURLs['success'] =
            Context::getContext()->link->getModuleLink($moduleName, 'success', $queryParameters, true);

        return $responseURLs;
    }


    /**
     * Get company data from Biller input fields on checkout page.
     *
     * @param Cart $cart Current shopping cart
     * @return array
     */
    private function getCompanyData($cart)
    {
        if (
            Tools::getIsset('company_name') &&
            Tools::getIsset('vat_number') &&
            Tools::getIsset('registration_number')
        ) {
            return array(
                'companyName' => Tools::getValue('company_name'),
                'vatNumber' => Tools::getValue('vat_number'),
                'registrationNumber' => Tools::getValue('registration_number')
            );
        }

        /** @var CompanyInfoService $companyInfoService */
        $companyInfoService = ServiceRegister::getService(CompanyInfoService::class);
        $companyInfo = $companyInfoService->getCompanyInfo($cart->id);

        return array(
            'companyName' => $companyInfo->getCompanyName(),
            'vatNumber' => $companyInfo->getVatNumber(),
            'registrationNumber' => $companyInfo->getRegistrationNumber(),
        );
    }
}
