<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once rtrim(_PS_MODULE_DIR_, '/') . '/biller/vendor/autoload.php';

/**
 * Biller module base class. This class represents main entry point for the plugin.
 * It is used for: installation, uninstallation, handling hook actions and handling configuration page.
 *
 * @property bool bootstrap
 * @property string module_key
 * @property string name
 * @property string tab
 * @property string version
 * @property string author
 * @property int need_instance
 * @property array ps_versions_compliancy
 * @property string displayName
 * @property string description
 * @property string confirmUninstall
 * @property \Context context
 */
class Biller extends PaymentModule
{
    /**
     * Biller module constructor
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

        $this->displayName = $this->l('Biller Business invoice');
        $this->description = $this->l(
            'The payment solution that advances both sides. We pay out every invoice on time. And buyers get to choose Buy Now, Pay Later.'
        );
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the Biller module?');
    }

    /**
     * Handle plugin installation.
     *
     * @return bool Installation status
     */
    public function install()
    {
        return (
            parent::install() &&
            $this->getInstaller()->install()
        );
    }

    /**
     * Handle plugin uninstallation.
     *
     * @return bool Uninstallation status
     */
    public function uninstall()
    {
        return (
            parent::uninstall() &&
            $this->getInstaller()->uninstall()
        );
    }

    /**
     * Gets module's context.
     *
     * @return \Context|null Module's context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Gets module's smarty reference.
     *
     * @return Smarty_Data|Smarty_Internal_TemplateBase Module's smarty reference
     */
    public function getSmarty()
    {
        return $this->smarty;
    }

    /**
     * Gets module's identifier.
     *
     * @return string Module's string identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Gets module's table.
     *
     * @return string Module's table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * This method handles the module's configuration page.
     * Display configuration page only if the shop context is selected.
     *
     * @return void|string The page's HTML content
     */
    public function getContent()
    {
        $isShopContext = \Shop::getContext() === \Shop::CONTEXT_SHOP;

        if (!$isShopContext) {
            $this->getContext()->controller->errors[] = $this->l('Please select the specific shop to configure.');

            return;
        }

        Biller\PrestaShop\Bootstrap::init();

        $authorizationService = new \Biller\PrestaShop\Utility\Services\AuthorizationService();
        $settingsService = new \Biller\PrestaShop\Utility\Services\SettingsService($this);

        $loggedIn = $authorizationService->loggedIn();
        $formSubmitted = Tools::isSubmit("submit{$this->name}");

        if ($formSubmitted) {
            $errors = $authorizationService->authorize();

            if ($loggedIn) {
                $errors = array_merge($errors, $settingsService->saveSettings());
            }

            if (!empty($errors)) {
                $this->getContext()->controller->errors = $errors;
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
     * @return string Action link
     *
     * @throws \PrestaShopException
     */
    public function getAction($controller, $action, array $params)
    {
        $query = array_merge(array('action' => $action), $params);

        return $this->getContext()->link->getAdminLink($controller) .
            '&' . http_build_query($query);
    }

    /**
     * Hook for handling partial refund through Return products option.
     *
     * @param array $params Array containing order, cart and product list of partial refund
     *
     * @return void
     */
    public function hookActionOrderSlipAdd($params)
    {
        if ($params['order']->module === $this->name) {
            \Biller\PrestaShop\Bootstrap::init();
            try {
                \Biller\PrestaShop\Utility\OrderStatusHandler::handlePartialRefund(
                    $params['cart'],
                    $params['productList'],
                    $params['order'],
                    !empty($params['qtyList']) ? $params['qtyList'] : array(),
                    !empty($params['qtyList']) ? true : null
                );
            } catch (Exception $exception) {
                Biller\PrestaShop\Utility\FlashBag::getInstance()->setMessage(
                    'error',
                    $this->l($exception->getMessage())
                );

                if ($params['order']->current_state != $this->getOrderStatusMapper()->getOrderStatusMap(
                    )[\Biller\Domain\Order\Status::BILLER_STATUS_CAPTURED] && $params['order']->current_state != $this->getOrderStatusMapper(
                    )->getOrderStatusMap()[\Biller\Domain\Order\Status::BILLER_STATUS_PARTIALLY_REFUNDED]) {
                    $this->getNotificationHub()->pushWarning(
                        new Biller\BusinessLogic\Notifications\NotificationText(
                            'biller.payment.order.synchronization.warning.title'
                        ),
                        new Biller\BusinessLogic\Notifications\NotificationText(
                            'biller.payment.order.synchronization.warning.description'
                        ),
                        $params['order']->id
                    );
                }
                \Biller\PrestaShop\Bootstrap::init();

                Tools::redirectAdmin($this->getRedirectionHandler()->generateOrderPageUrl($params['order']));
            }
        }
    }

    /**
     * Loads CSS and JS files on each page.
     *
     * @return void
     */
    public function hookDisplayHeader()
    {
        \Biller\PrestaShop\Bootstrap::init();

        $this->getContext()->controller->addJS($this->getPathUri() . $this->getTemplateAndJsVersion()->getCheckoutJS());
    }

    /**
     * Used for partial refund for PrestaShop 1.6.
     * PrestaShop 1.6 does not have specific hook for handling partial refund.
     *
     * @param array $params Array cart of order
     *
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function hookActionDispatcher($params)
    {
        \Biller\PrestaShop\Bootstrap::init();
        if (
            Tools::getIsset('partialRefundProduct') &&
            (count(array_filter(Tools::getValue('partialRefundProduct'))) ||
                Tools::getValue(
                    'partialRefundShippingCost'
                )) &&
            (new \Order(Tools::getValue('id_order')))->module === $this->name
        ) {
            try {
                \Biller\PrestaShop\Utility\OrderStatusHandler::handlePartialRefund(
                    $params['cart'],
                    array_filter(Tools::getValue('partialRefundProduct')),
                    new \Order(Tools::getValue('id_order')),
                    array_filter(Tools::getValue('partialRefundProductQuantity')),
                    false
                );
            } catch (\Exception $exception) {
                Biller\PrestaShop\Utility\FlashBag::getInstance()->setMessage(
                    'error',
                    $this->l($exception->getMessage())
                );

                $order = new \Order(Tools::getValue('id_order'));
                if ($order->current_state != $this->getOrderStatusMapper()->getOrderStatusMap(
                    )[\Biller\Domain\Order\Status::BILLER_STATUS_CAPTURED] && $order->current_state != $this->getOrderStatusMapper(
                    )->getOrderStatusMap()[\Biller\Domain\Order\Status::BILLER_STATUS_PARTIALLY_REFUNDED]) {
                    $this->getNotificationHub()->pushWarning(
                        new Biller\BusinessLogic\Notifications\NotificationText(
                            'biller.payment.order.synchronization.warning.title'
                        ),
                        new Biller\BusinessLogic\Notifications\NotificationText(
                            'biller.payment.order.synchronization.warning.description'
                        ),
                        $order->id
                    );
                }

                Tools::redirectAdmin(
                    $this->getRedirectionHandler()->generateOrderPageUrl((new \Order(Tools::getValue('id_order'))))
                );
            }
        }
    }

    /**
     * Hook for displaying messages on checkout for PrestaShop 1.6.
     *
     * @return string Template to be displayed
     */
    public function hookDisplayPaymentTop()
    {
        $message = $this->l(
            \Biller\PrestaShop\Utility\FlashBag::getInstance()->getMessage('error')
        );
        \Context::getContext()->smarty->assign(array('message' => $message));

        return $message ? $this->display(__FILE__, 'payment_top16.tpl') : '';
    }


    /**
     * Hook for displaying tab link on order page.
     *
     * @param array $params Hook parameters containing Id of the order.
     *
     * @return string Tab link HTML as string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @since 1.7.7
     */
    public function hookDisplayAdminOrderTabLink($params)
    {
        return $this->displayTabLink($params['id_order']);
    }

    /**
     * Hook for displaying tab link on order page
     * Removed in 1.7.7 in favor of displayAdminOrderTabLink.
     *
     * @param array $params Hook parameters containing ID of the order
     *
     * @return string Tab link HTML as string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function hookDisplayAdminOrderTabOrder($params)
    {
        return $this->displayTabLink($params['order']->id);
    }

    /**
     * Hook for displaying header data used in BO.
     *
     * @return false|string Header HTML data as string
     * @throws \PrestaShopException
     */
    public function hookDisplayBackOfficeHeader()
    {
        if ($this->isEnabled($this->name)) {
            $currentController = Tools::getValue('controller');

            if (
                $message = $this->l(
                    \Biller\PrestaShop\Utility\FlashBag::getInstance()->getMessage('error')
                )
            ) {
                $this->getContext()->controller->errors[] = Tools::displayError($message);
            }

            if (
                $message = $this->l(
                    \Biller\PrestaShop\Utility\FlashBag::getInstance()->getMessage('success')
                )
            ) {
                $this->getContext()->controller->confirmations[] = $message;
            }

            if ($currentController === 'AdminOrders') {
                $this->smarty->assign(array(
                    'companyInfoURL' => $this->getAction('CompanyInfo', 'fetchCompanyInfo', array('ajax' => true)),
                    'orderCreateAction' => $this->getAction(
                        'CreateOrder',
                        'createOrder',
                        array(
                            'ajax' => true
                        )
                    )
                ));
                \Biller\PrestaShop\Bootstrap::init();

                return $this->display(
                    $this->getPathUri(),
                    $this->getTemplateAndJsVersion()->getAddOrderSummaryTemplate()
                );
            }
        }
        return '';
    }

    /**
     * Hook for displaying tab content on order page.
     *
     * @param array $params Hook parameters
     *
     * @return string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException
     * @throws \Biller\BusinessLogic\Order\Exceptions\InvalidOrderReferenceException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     *
     * @since 1.7.7
     */
    public function hookDisplayAdminOrderTabContent($params)
    {
        return $this->displayTabContent($params['id_order']);
    }

    /**
     * Hook for displaying tab content on order page
     * Removed in 1.7.7 in favor of displayAdminOrderTabContent
     *
     * @param array $params Hook parameters
     *
     * @return string Tab content HTML as string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException
     * @throws \Biller\BusinessLogic\Order\Exceptions\InvalidOrderReferenceException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function hookDisplayAdminOrderContentOrder($params)
    {
        return $this->displayTabContent($params['order']->id);
    }

    /**
     * Hook called after a Biller order has been validated. Used to save order's company info into database.
     *
     * @param $params Array containing cart and order objects
     *
     * @return void
     */
    public function hookActionValidateOrder($params)
    {
        if (
            !isset($this->getContext()->controller) ||
            'admin' !== $this->getContext()->controller->controller_type ||
            $params['order']->module !== $this->name
        ) {
            return;
        }

        \Biller\PrestaShop\Bootstrap::init();

        /** @var \Biller\PrestaShop\Utility\Services\CompanyInfoService $companyInfoService */
        $companyInfoService = \Biller\Infrastructure\ServiceRegister::getService(
            \Biller\PrestaShop\Utility\Services\CompanyInfoService::class
        );

        $companyInfo = new Biller\PrestaShop\Entity\CompanyInfo();

        $companyInfo->setOrderId($params['cart']->id);
        $companyInfo->setCompanyName(\Tools::getValue('biller-company-name'));
        $companyInfo->setRegistrationNumber(\Tools::getValue('biller-registration-number'));
        $companyInfo->setVatNumber(\Tools::getValue('biller-vat-number'));

        $companyInfoService->saveCompanyInfo($companyInfo);
    }

    /**
     * Hook for adding Biller payment option if availability condition is satisfied.
     *
     * @param array $params Array containing cookie and cart objects
     *
     * @return array Array containing the Biller payment method
     */
    public function hookPaymentOptions($params)
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
     * Hook for adding Biller as a payment option on PrestaShop 1.6.
     *
     * @param array $params Hook parameters
     *
     * @return false|string Biller payment option link HTML as string
     */
    public function hookPayment($params)
    {
        \Biller\PrestaShop\Bootstrap::init();

        $billingCountryId = $this->extractBillingCountryId($params);
        $currencyId = $this->extractCurrencyId($params);

        if ($this->isPaymentMethodAvailable($billingCountryId, $currencyId)) {
            $idAddress = Context::getContext()->cart->id_address_invoice;
            $address = new Address($idAddress);
            $this->smarty->assign(array(
                'biller_name' => $this->l($this->getPaymentConfiguration()->getName()),
                'biller_description' => $this->l($this->getPaymentConfiguration()->getDescription()),
                'biller_company_name' => $address->company,
                'biller_vat_number' => $address->vat_number,
                'action' => $this->getContext()->link->getModuleLink($this->name, 'payment', array(), true),
            ));

            return $this->display(__FILE__, 'payment.tpl');
        }

        return '';
    }

    /**
     * Hook for displaying content on order confirmation page on PrestaShop 1.6
     *
     * @return false|string Template to be displayed
     */
    public function hookPaymentReturn()
    {
        $template = $this->getTemplateAndJsVersion()->getPaymentReturnTemplate();

        return $template ? $this->display(__FILE__, $template) : '';
    }

    /**
     * Hook called on order edit.
     *
     * @param $params Array containing edited order object
     *
     * @return void
     */
    public function hookActionOrderEdited($params)
    {
        /** @var \Order $order */
        $order = $params['order'];

        if ($this->getContext()->controller->controller_type !== 'admin' || $order->module !== $this->name) {
            return;
        }

        \Biller\PrestaShop\Bootstrap::init();

        $orderPending = $order->current_state ==
            $this->getOrderStatusMapper()->getOrderStatusMap()[\Biller\Domain\Order\Status::BILLER_STATUS_PENDING];
        if (!$orderPending) {
            $this->getNotificationHub()->pushWarning(
                new Biller\BusinessLogic\Notifications\NotificationText(
                    'biller.payment.order.synchronization.warning.title'
                ),
                new Biller\BusinessLogic\Notifications\NotificationText(
                    'biller.payment.order.synchronization.warning.description'
                ),
                $order->id
            );
        }
    }

    /**
     * Hook called before address update.
     *
     * @param array $params Hook parameters array containing the updated address
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionObjectAddressAddBefore($params)
    {
        if ($this->getContext()->controller->controller_type !== 'admin') {
            return;
        }

        \Biller\PrestaShop\Bootstrap::init();

        /** @var \Address $address */
        $address = $params['object'];
        $customer = new \Customer($address->id_customer);
        $carts = \Cart::getCustomerCarts($customer->id);

        foreach ($carts as $cart) {
            $order = new \Order(\Order::getOrderByCartId($cart['id_cart']));

            if (!$order) {
                continue;
            }

            $orderPending = $order->current_state ==
                $this->getOrderStatusMapper()->getOrderStatusMap()[\Biller\Domain\Order\Status::BILLER_STATUS_PENDING];
            if ($order->module === $this->name && !$orderPending) {
                $this->getNotificationHub()->pushWarning(
                    new Biller\BusinessLogic\Notifications\NotificationText(
                        'biller.payment.address.synchronization.warning.title'
                    ),
                    new Biller\BusinessLogic\Notifications\NotificationText(
                        'biller.payment.address.synchronization.warning.description',
                        array($address->id, $order->id)
                    ),
                    $order->id
                );
            }
        }
    }

    /**
     * Hook for adding JS && CSS to admin controllers.
     */
    public function hookActionAdminControllerSetMedia()
    {
        \Biller\PrestaShop\Bootstrap::init();

        $currentController = Tools::getValue('controller');
        $moduleName = Tools::getValue('configure');

        $this->getContext()->controller->addCSS(array(
            $this->getPathUri() . 'views/css/admin/orderDashboard.css',
            $this->getPathUri() . 'views/css/admin/app.css'
        ));
        $this->getContext()->controller->addJS(array(
            $this->getPathUri() . 'views/js/admin/orderDashboard.js',
            $this->getPathUri() . 'views/js/admin/orderTabContent.js',
            $this->getPathUri() . 'views/js/admin/ajax.js',
            $this->getPathUri() . $this->getTemplateAndJsVersion()->getAddOrderSummaryJS()
        ));

        if ($moduleName === $this->displayName && $currentController === 'AdminModules') {
            $this->getContext()->controller->addJS($this->getPathUri() . 'views/js/admin/moduleConfigure.js');
        }

        if ($currentController === 'AdminOrders') {
            /** @var \Biller\PrestaShop\Utility\Services\AuthorizationService $authorizationService */
            $authorizationService = \Biller\Infrastructure\ServiceRegister::getService(
                \Biller\PrestaShop\Utility\Services\AuthorizationService::class
            );

            \Media::addJsDef(array(
                'billerPendingStatus' => $this->getOrderStatusMapper()->getOrderStatusMap(
                )[\Biller\Domain\Order\Status::BILLER_STATUS_PENDING],
                'billerAvailable' => $authorizationService->loggedIn(),
            ));
        }
    }

    /**
     * Hook for handling order status update.
     *
     * @param array $params Hook parameters
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function hookActionOrderStatusUpdate($params)
    {
        $order = new Order($params['id_order']);
        $orderStatus = $params['newOrderStatus'];

        if (
            $order->module !== $this->name ||
            \Tools::getValue('controller') === 'webhooks' ||
            $orderStatus->id == $order->current_state
        ) {
            return;
        }

        \Biller\PrestaShop\Bootstrap::init();

        $status = \Biller\PrestaShop\Utility\OrderStatusHandler::getBillerOrderStatus($order);
        $orderStatusMapping = $this->getOrderStatusMapper();

        if (
            $orderStatusMapping->getOrderStatusMap()
            [$status->__toString()] == $orderStatus->id
        ) {
            return;
        }

        if (
            $orderStatusMapping->getOrderStatusMap()
            [\Biller\Domain\Order\Status::BILLER_STATUS_REFUNDED] == $orderStatus->id
        ) {
            \Biller\PrestaShop\Utility\OrderStatusHandler::handleFullRefund($order);
        }

        if (
            $orderStatusMapping->getOrderStatusMap()
            [\Biller\Domain\Order\Status::BILLER_STATUS_CANCELLED] == $orderStatus->id
        ) {
            \Biller\PrestaShop\Utility\OrderStatusHandler::handleCancellation($order);
        }

        if (
            $orderStatusMapping->getOrderStatusMap()
            [\Biller\Domain\Order\Status::BILLER_STATUS_CAPTURED] == $orderStatus->id
        ) {
            \Biller\PrestaShop\Utility\OrderStatusHandler::handleCapture($order);
        }

        if (
            $orderStatusMapping->getOrderStatusMap()
            [\Biller\Domain\Order\Status::BILLER_STATUS_ACCEPTED] == $orderStatus->id &&
            ($orderStatusMapping->getOrderStatusMap()
                [\Biller\Domain\Order\Status::BILLER_STATUS_CANCELLED] == $order->current_state ||
                $orderStatusMapping->getOrderStatusMap(
                )[\Biller\Domain\Order\Status::BILLER_STATUS_REFUNDED] == $order->current_state)
        ) {
            \Biller\PrestaShop\Utility\OrderStatusHandler::handleProcessingStatus($order);
        }
    }

    /**
     * Hook called after shop object deletion. Used to adjust Biller configuration data on store deletion when
     * multistore is feature enabled.
     *
     * @param $params Array of hook parameters containing the shop object under 'object' key
     *
     * @return void
     */
    public function hookActionObjectShopDeleteAfter($params)
    {
        /** @var \Shop $shop */
        $shop = $params['object'];

        \Biller\PrestaShop\Utility\DatabaseHandler::deleteRows(
            'configuration',
            "name LIKE '%BILLER%' && id_shop = $shop->id"
        );

        $remainingShops = \Shop::getShops(false);

        if (count($remainingShops) === 2) {
            $defaultShop = $remainingShops[\Configuration::get('PS_SHOP_DEFAULT')];

            $idShop = $defaultShop['id_shop'];
            $idShopGroup = $defaultShop['id_shop_group'];

            \Biller\PrestaShop\Utility\DatabaseHandler::updateRows(
                'configuration',
                array(
                    'id_shop' => array('type' => 'sql', 'value' => 'NULL'),
                    'id_shop_group' => array('type' => 'sql', 'value' => 'NULL'),
                ),
                "name LIKE 'BILLER%' AND id_shop = $idShop AND id_shop_group = $idShopGroup"
            );
        }
    }

    /**
     * Hook called before shop object addition. Used to adjust Biller configuration data on store addition when
     * multistore is feature enabled.
     *
     * @param $params Array of hook parameters containing the shop object under 'object' key
     *
     * @return void
     */
    public function hookActionObjectShopAddBefore($params)
    {
        $shops = \Shop::getShops(false);

        if (count($shops) === 1) {
            $defaultShop = $shops[\Configuration::get('PS_SHOP_DEFAULT')];

            $idShop = $defaultShop['id_shop'];
            $idShopGroup = $defaultShop['id_shop_group'];

            \Biller\PrestaShop\Utility\DatabaseHandler::updateRows(
                'configuration',
                array(
                    'id_shop' => $idShop,
                    'id_shop_group' => $idShopGroup,
                ),
                "name LIKE 'BILLER%'"
            );
        }
    }

    /**
     * Hook for altering email template variables before sending.
     *
     * @param array $params Array of parameters including template_vars array and cart
     *
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function hookSendMailAlterTemplateVars(array &$params)
    {
        if (isset($params['template_vars']['{biller_payment_link}'])) {
            return;
        }

        if (empty($params['cart']->id)) {
            $params['template_vars']['{biller_payment_link}'] = '';

            return;
        }

        $order = new \Order(\Order::getOrderByCartId($params['cart']->id));

        if ($order->module === $this->name) {
            $params['template_vars']['{biller_payment_link}'] = \Context::getContext()->link->getModuleLink(
                $this->name,
                'payment',
                array('orderId' => $order->id, 'ajax' => true)
            );
        }
    }

    /**
     * Adds css and js files to module config page.
     *
     * @return void
     */
    private function addConfigStylesAndJS()
    {
        $this->getContext()->controller->addCSS(array(
            $this->getPathUri() . 'views/css/admin/config.css',
            $this->getPathUri() . 'views/css/admin/notifications.css',
        ));
        $this->getContext()->controller->addJS(array(
            $this->getPathUri() . 'views/js/admin/form.js',
            $this->getPathUri() . 'views/js/admin/ajax.js',
            $this->getPathUri() . 'views/js/admin/notifications.js',
            $this->getPathUri() . 'views/js/admin/passwordWidget.js',
        ));
    }

    /**
     * Generates appropriate configuration page form for the given logged in status.
     *
     * @param bool $loggedIn Logged in flag
     * @param bool $formSubmitted Bool indicating whether form is being generated for the
     * first time or after post request
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
     * @param array $params Hook parameters
     *
     * @return int Billing country id
     */
    private function extractBillingCountryId($params)
    {
        return \Address::getCountryAndState((int)$params['cart']->id_address_invoice)['id_country'];
    }

    /**
     * Extracts currency from hook parameters containing cookie data.
     *
     * @param array $params Hook parameters
     *
     * @return int Currency id
     */
    private function extractCurrencyId($params)
    {
        return (int)$params['cookie']->__get('id_currency');
    }

    /**
     * Checks if Biller payment option is available for given billing country and currency.
     *
     * @param int $billingCountryId ID of billing country
     * @param int $currencyId ID of currency
     *
     * @return bool True if payment option available, otherwise false
     */
    private function isPaymentMethodAvailable($billingCountryId, $currencyId)
    {
        /** @var \Biller\PrestaShop\Utility\Services\AuthorizationService $authorizationService */
        $authorizationService = \Biller\Infrastructure\ServiceRegister::getService(
            \Biller\PrestaShop\Utility\Services\AuthorizationService::class
        );
        /** @var \Biller\PrestaShop\InfrastructureService\ConfigurationService $configurationService */
        $configurationService = \Biller\Infrastructure\ServiceRegister::getService(
            \Biller\Infrastructure\Configuration\Configuration::CLASS_NAME
        );

        $enabled = $configurationService->getConfigValue(
            \Biller\PrestaShop\Utility\Config\Config::ENABLE_BUSINESS_INVOICE_KEY
        );

        return
            $enabled
            && $authorizationService->loggedIn()
            && $this->isMethodEnabled()
            && $this->isValidCountry($billingCountryId)
            && $this->isValidCurrency($currencyId);
    }

    /**
     * Checks if Biller business invoice payment method is enabled.
     *
     * @return bool Method enabled status
     */
    private function isMethodEnabled()
    {
        return $this->isEnabledForShopContext();
    }

    /**
     * Checks if country is supported by Biller.
     *
     * @param int $billingCountryId Country id of the billing address
     *
     * @return bool Validity status
     */
    private function isValidCountry($billingCountryId)
    {
        $countryIsoCode = \Country::getIsoById($billingCountryId);

        return in_array($countryIsoCode, \Biller\PrestaShop\Utility\Config\Config::ACCEPTED_COUNTRY_CODES);
    }

    /**
     * Checks if currency is supported by Biller.
     *
     * @param int $currencyId Currency id
     *
     * @return bool Validity status
     */
    private function isValidCurrency($currencyId)
    {
        $currencyIsoCode = (new Currency($currencyId))->iso_code;

        return in_array($currencyIsoCode, Biller\PrestaShop\Utility\Config\Config::ACCEPTED_CURENCY_CODES);
    }

    /**
     * Creates Biller payment option.
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption Biller payment option object
     */
    private function createPaymentOption()
    {
        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();

        /** @var Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration $paymentConfiguration */
        $paymentConfiguration = Biller\Infrastructure\ServiceRegister::getService(
            Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration::class
        );

        $paymentOption->setCallToActionText($this->l($paymentConfiguration->getName()));
        $paymentOption->setForm($this->generatePaymentMethodForm($paymentConfiguration->getDescription()));
        $paymentOption->setLogo($this->getPathUri() . 'views/img/biller_logo_wide.png');
        $paymentOption->setModuleName($this->name);
        $paymentOption->setInputs(array());
        $paymentOption->setBinary(false);

        return $paymentOption;
    }

    /**
     * Generates Biller payment method form.
     *
     * @param string $moduleDescription Module description
     *
     * @return string Biller payment method form HTML
     */
    private function generatePaymentMethodForm($moduleDescription)
    {
        try {
            $idAddress = Context::getContext()->cart->id_address_invoice;
            $address = new Address($idAddress);
            $this->getContext()->smarty->assign(array(
                'description' => $this->l($moduleDescription),
                'action' => $this->getContext()->link->getModuleLink($this->name, 'payment', array(), true),
                'companyName' => $address->company,
                'VAT' => $address->vat_number
            ));

            return $this->getContext()->smarty->fetch($this->getTemplatePath('payment_form.tpl'));
        } catch (\Exception $exception) {
            Biller\Infrastructure\Logger\Logger::logError($exception->getMessage());

            return '';
        }
    }

    /**
     * Display Biller tab link on order page.
     *
     * @param int $orderId Order id
     *
     * @return string Tab link HTML as a string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function displayTabLink($orderId)
    {
        $order = new Order($orderId);

        if (\Shop::getContext() !== \Shop::CONTEXT_SHOP || $order->module !== $this->name) {
            return '';
        }

        \Biller\PrestaShop\Bootstrap::init();

        $this->getContext()->smarty->assign(
            array('biller_name' => $this->l($this->getPaymentConfiguration()->getName()))
        );

        return $this->display(__FILE__, $this->getTemplateAndJsVersion()->getTabLinkTemplate());
    }

    /**
     * Display Biller tab content on order page.
     *
     * @param $orderId
     *
     * @return false|string Biller order dashboard section's HTML as string
     *
     * @throws \Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException
     * @throws \Biller\BusinessLogic\Order\Exceptions\InvalidOrderReferenceException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Biller\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Biller\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function displayTabContent($orderId)
    {
        $order = new Order($orderId);

        if (\Shop::getContext() !== \Shop::CONTEXT_SHOP || $order->module !== $this->name) {
            return '';
        }

        \Biller\PrestaShop\Bootstrap::init();
        $orderPending = $order->current_state ==
            $this->getOrderStatusMapper()->getOrderStatusMap()[\Biller\Domain\Order\Status::BILLER_STATUS_PENDING];
        $paymentLink = $orderPending ?
            \Context::getContext()->link->getModuleLink(
                $this->name,
                'payment',
                array('orderId' => $orderId, 'ajax' => true)
            ) : null;

        $status = \Biller\PrestaShop\Utility\OrderStatusHandler::getBillerOrderStatus($order);
        $accepted = $status->isAccepted();
        $statusLabel =
            \Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping::getOrderStatusLabel((string)$status);

        $this->getContext()->smarty->assign(
            array(
                'cancelURL' => $this->getAction(
                    'Cancel',
                    'cancelOrder',
                    array(
                        'ajax' => true
                    )
                ),
                'captureURL' => $this->getAction(
                    'Capture',
                    'captureOrder',
                    array(
                        'ajax' => true
                    )
                ),
                'orderId' => $orderId,
                'status' => $statusLabel,
                'paymentLink' => $paymentLink,
                'accepted' => $accepted
            )
        );

        return $this->display(__FILE__, $this->getTemplateAndJsVersion()->getOrderBillerSectionTemplate());
    }

    /**
     * Creates Biller Installer.
     *
     * @return \Biller\PrestaShop\Utility\Installer
     */
    private function getInstaller()
    {
        $registerHookHandler = function ($key) {
            return $this->registerHook($key);
        };
        $unregisterHookHandler = function ($key) {
            return $this->unregisterHook($key);
        };

        return new Biller\PrestaShop\Utility\Installer(
            Closure::bind($registerHookHandler, $this),
            Closure::bind($unregisterHookHandler, $this),
            $this->name
        );
    }

    /**
     * Returns order status mapping class.
     *
     * @return Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping
     */
    private function getOrderStatusMapper()
    {
        /** @var \Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping $orderStatusMapper */
        return Biller\Infrastructure\ServiceRegister::getService(
            Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping::class
        );
    }

    /**
     * Returns notification hub service.
     *
     * @return \Biller\BusinessLogic\Notifications\NotificationHub
     */
    private function getNotificationHub()
    {
        /** @var \Biller\BusinessLogic\Notifications\NotificationHub $notificationHub */
        return \Biller\Infrastructure\ServiceRegister::getService(
            \Biller\BusinessLogic\Notifications\NotificationHub::class
        );
    }

    /**
     * Returns biller payment configuration service.
     *
     * @return Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration
     */
    private function getPaymentConfiguration()
    {
        return Biller\Infrastructure\ServiceRegister::getService(
            Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration::class
        );
    }

    /**
     * Returns RedirectionVersion class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 RedirectionVersion16 is returned.
     * For versions from 1.7.0.0 to 1.7.7.0 RedirectionVersion17  is returned.
     * For versions from 1.7.7.0+ RedirectionVersion177  is returned.
     *
     * @return Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface
     */
    private function getRedirectionHandler()
    {
        return Biller\Infrastructure\ServiceRegister::getService(
            Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface::class
        );
    }

    /**
     * Returns TemplateAndJsVersion class depending on used PrestaShop version.
     * For versions from 1.6.0.14 to 1.7.0.0 TemplateAndJsVersion16 is returned.
     * For versions from 1.7.0.0 to 1.7.7.0 TemplateAndJsVersion17  is returned.
     * For versions from 1.7.7.0+ TemplateAndJsVersion177  is returned.
     *
     * @return Biller\PrestaShop\Utility\Version\TemplateAndJs\Contract\TemplateAndJSVersionInterface
     */
    private function getTemplateAndJsVersion()
    {
        return Biller\Infrastructure\ServiceRegister::getService(
            Biller\PrestaShop\Utility\Version\TemplateAndJs\Contract\TemplateAndJSVersionInterface::class
        );
    }
}
