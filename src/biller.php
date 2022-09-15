<?php


use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once rtrim(_PS_MODULE_DIR_, '/') . '/biller/vendor/autoload.php';

/**
 * Biller class.
 */
class Biller extends PaymentModule
{
    /**
     * Biller module constructor.
     */
    public function __construct()
    {
        $this->name = 'biller';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';

        $this->author = $this->l('Biller');
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6.0.14', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Biller business invoice');
        $this->description = $this->l(
            'The payment solution that advances both sides. We pay out every invoice on time.'
        );
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the biller module?');
    }

    /**
     * Handle plugin installation
     *
     * @return bool
     */
    public function install()
    {
        $installer = new Biller\PrestaShop\Utility\Installer($this);

        return (
            parent::install() &&
            $installer->install()
        );
    }

    /**
     * Handle plugin uninstallation
     *
     * @return bool
     */
    public function uninstall()
    {
        $installer = new Biller\PrestaShop\Utility\Installer($this);

        return (parent::uninstall() &&
            $installer->uninstall());
    }

    /**
     * Gets module's context.
     *
     * @return Context|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Gets module's smarty reference.
     *
     * @return Smarty_Data|Smarty_Internal_TemplateBase
     */
    public function getSmarty()
    {
        return $this->smarty;
    }

    /**
     * Gets module's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Gets module's table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * This method handles the module's configuration page.
     * Display configuration page only if the shop context is selected.
     *
     * @return void|string The page's HTML content.
     */
    public function getContent()
    {
        $isShopContext = \Shop::getContext() === \Shop::CONTEXT_SHOP;

        if (!$isShopContext) {
            $this->context->controller->errors[] = $this->l('Please select the specific shop to configure.');

            return;
        }

        Biller\PrestaShop\Bootstrap::init();

        $authorizationService = new \Biller\PrestaShop\Utility\Services\AuthorizationService($this);
        $settingsService = new \Biller\PrestaShop\Utility\Services\SettingsService($this);

        $loggedIn = $authorizationService->loggedIn();
        $formSubmitted = Tools::isSubmit("submit{$this->name}");

        if ($formSubmitted) {
            $errors = $authorizationService->authorize();
            $settingsService->updateEnabledStatus();

            if ($loggedIn) {
                $errors = array_merge($errors, $settingsService->saveSettings());
            }

            if (!empty($errors)) {
                $this->context->controller->errors = $errors;
            } else {
                Context::getContext()->controller->confirmations = $this->l('Your settings have been saved.');
            }

            if (!$loggedIn) {
                $loggedIn = $authorizationService->loggedIn();
            }
        }

        $this->addConfigStylesAndJS();

        return $this->generateConfigForm($loggedIn, $formSubmitted);
    }

    /**
     * Retrieves link to controller and it's appropriate method.
     *
     * @param string $controller Controller name
     * @param string $action Method name
     * @param array $params URL parameters
     *
     * @return string
     */
    public function getAction($controller, $action, array $params)
    {
        $query = array_merge(array('action' => $action), $params);

        return $this->context->link->getAdminLink($controller) .
            '&' . http_build_query($query);
    }

    /**
     * Loads CSS and JS files on each page.
     *
     * @return void
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS(array($this->getPathUri() . 'views/js/front/checkout.js'));
    }


    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function hookDisplayAdminOrderTabContent($params)
    {
        $order = new Order($params['id_order']);

        if ($order->payment === $this->displayName) {
            \Biller\PrestaShop\Bootstrap::init();

            $externalOrderUUID = $this->getExternalOrderUUID($order->id_cart);

            /** @var  Biller\BusinessLogic\Order\OrderService $orderService */
            $orderService = Biller\Infrastructure\ServiceRegister::getService(Biller\BusinessLogic\Order\OrderService::class);

            try {
                $this->context->smarty->assign(array('status' => $this->getStatusLabel($orderService->getStatus($externalOrderUUID))));
            } catch (Exception $e) {
            }

            Tools::clearAllCache();
            return $this->display(__FILE__, 'order_biller_section.tpl');
        }

        return '';
    }


    /**
     * Hook for adding Biller payment option if availability condition is satisfied.
     *
     * @param array $params Array containing cookie and cart objects
     *
     * @return array
     */
    public function hookPaymentOptions(array $params)
    {
        \Biller\PrestaShop\Bootstrap::init();

        $billingCountryId = $this->extractBillingCountryId($params);
        $currencyId = $this->extractCurrencyId($params);

        if ($this->isPaymentMethodAvailable($billingCountryId, $currencyId)) {
            return array($this->createPaymentOption());
        }

        return array();
    }


    /**
     * Add JS && CSS to admin controllers.
     */
    public function hookActionAdminControllerSetMedia()
    {
        $currentController = Tools::getValue('controller');
        $moduleName = Tools::getValue('configure');

        // on the module configuration page
        if ($moduleName === $this->name && $currentController === 'AdminModules') {
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/moduleConfigure.js');
        }
    }

    /**
     * Adds css and js files to module config page.
     *
     * @return void
     */
    private function addConfigStylesAndJS()
    {
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/config.css');
        $this->context->controller->addJS(array(
            $this->getPathUri() . 'views/js/admin/form.js',
            $this->getPathUri() . 'views/js/admin/passwordWidget.js'
        ));
    }

    /**
     * Generates appropriate configuration page form for the given logged in status.
     *
     * @param bool $loggedIn Logged in flag
     * @param bool $formSubmitted Bool indicating whether form is being generated for the first time or after post request
     *
     * @return string Configuration page HTML
     */
    private function generateConfigForm($loggedIn, $formSubmitted)
    {
        $formBuilder = $loggedIn ?
            new Biller\PrestaShop\Utility\FormBuilder\AuthorizedFormBuilder($this) :
            new Biller\PrestaShop\Utility\FormBuilder\BaseFormBuilder($this);

        return $formBuilder->generateConfigForm($formSubmitted);
    }

    /**
     * Extracts billing country from hook parameters containing cart data.
     *
     * @param array $params Hook parameters.
     *
     * @return int Billing country id.
     */
    private function extractBillingCountryId(array $params)
    {
        return \Address::getCountryAndState((int)$params['cart']->id_address_invoice)['id_country'];
    }

    /**
     * Extracts currency from hook parameters containing cookie data.
     *
     * @param array $params Hook parameters.
     *
     * @return int Currency id.
     */
    private function extractCurrencyId(array $params)
    {
        return (int)$params['cookie']->__get('id_currency');
    }

    /**
     * Checks if Biller payment option is available for given billing country and currency.
     *
     * @param int $billingCountryId Id of billing country.
     * @param int $currencyId Id of currency.
     *
     * @return bool True if payment option available, otherwise false.
     */
    private function isPaymentMethodAvailable($billingCountryId, $currencyId)
    {
        return $this->isMethodEnabled()
            && $this->isValidCountry($billingCountryId)
            && $this->isValidCurrency($currencyId);
    }

    /**
     * Checks if Biller business invoice payment method is enabled.
     *
     * @return bool
     */
    private function isMethodEnabled()
    {
        /** @var BillerPaymentConfiguration $paymentConfiguration */
        $paymentConfiguration = Biller\Infrastructure\ServiceRegister::getService(Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration ::class);

        return $this->isEnabledForShopContext() && intval($paymentConfiguration->getMethodEnabledStatus());
    }

    /**
     * Checks if country is supported by Biller.
     *
     * @param int $billingCountryId
     *
     * @return bool
     */
    private function isValidCountry($billingCountryId)
    {
        $countryIsoCode = \Country::getIsoById($billingCountryId);

        return in_array($countryIsoCode, \Biller\PrestaShop\Utility\Config\Config::ACCEPTED_COUNTRY_CODES);
    }

    /**
     * Checks if currency is supported by Biller.
     *
     * @param int $currencyId
     *
     * @return bool
     */
    private function isValidCurrency($currencyId)
    {
        $currencyIsoCode = \Currency::getIsoCodeById($currencyId);

        return in_array($currencyIsoCode, \Biller\PrestaShop\Utility\Config\Config::ACCEPTED_CURENCY_CODES);
    }

    /**
     * Creates Biller payment option.
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption Biller payment option object.
     */
    private function createPaymentOption()
    {
        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();

        $paymentOption->setCallToActionText($this->l('Biller business invoice'));
        $paymentOption->setForm($this->generatePaymentMethodForm());
        $paymentOption->setLogo($this->getPathUri() . 'views/img/biller_logo_wide.png');
        $paymentOption->setModuleName($this->name);
        $paymentOption->setInputs(array());
        $paymentOption->setBinary(false);

        return $paymentOption;
    }

    /**
     * Generates Biller payment method form.
     *
     * @return string Biller payment method form HTML.
     */
    private function generatePaymentMethodForm()
    {
        try {
            $idAddress = Context::getContext()->cart->id_address_invoice;
            $address = new Address($idAddress);
            $this->context->smarty->assign(array(
                'description' => $this->l(
                    'The payment solution that advances both sides. We pay out every invoice on time.'
                ),
                'action' => $this->context->link->getModuleLink($this->name, 'payment', array(), true), // TODO not needed in case of 'PLACE ORDER' submission
                'companyName' => $address->company,
                'VAT' => $address->vat_number
            ));

            return $this->context->smarty->fetch($this->getTemplatePath('paymentForm.tpl'));
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return void
     */
    private function assignSmarty()
    {
        $this->smarty->assign(array('logo' => $this->getPathUri() . 'views/img/biller_logo.svg',
            'version' => $this->version
        ));
    }


    /**
     * Get status label
     *
     * @param Biller\Domain\Order\Status $status
     *
     * @return string
     */
    private function getStatusLabel( $status)
    {
        switch ($status) {
            case $status->isPending():
                return 'Pending';
            case $status->isAccepted():
                return 'Accepted';
            case $status->isCaptured():
                return 'Captured';
            case $status->isPartiallyCaptured():
                return 'Partially captured';
            case $status->isRefunded():
                return 'Refunded';
            case $status->isRefundedPartially():
                return 'Partially refunded';
            case $status->isCancelled():
                return 'Cancelled';
            case $status->isRejected():
                return 'Rejected';
            case $status->isFailed():
                return 'Failed';
            default:
                return 'Unknown';
        }
    }

    /**
     * Returns external order uid
     *
     * @param int $idCart
     * @return string
     * @throws \Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    private function getExternalOrderUUID($idCart)
    {
        /** @var  \Biller\PrestaShop\Repositories\OrderReferenceRepository $orderService */

        $orderReferenceRepository = Biller\Infrastructure\ORM\RepositoryRegistry::getRepository(Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference::class);

        $queryFilter = new Biller\Infrastructure\ORM\QueryFilter\QueryFilter();
        $queryFilter->where('externalUUID', '=', $idCart);

        /** @var \Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference $orderReference */
        $orderReference = $orderReferenceRepository->selectOne($queryFilter);

        return $orderReference->getExternalUUID();
    }
}
