<?php

use Biller\BusinessLogic\Notifications\NotificationController;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Response;
use Biller\PrestaShop\Utility\TranslationUtility;

/**
 * NotificationsHubController class. Endpoint for fetching notifications.
 */
class NotificationsHubController extends ModuleAdminController
{
    /** @var int */
    const NOTIFICATIONS_PER_PAGE = 10;

    public function __construct()
    {
        parent::__construct();
        Bootstrap::init();

        $this->bootstrap = true;
    }

    /**
     * Fetches notifications.
     *
     * @return void
     */
    public function displayAjaxFetchNotifications()
    {
        $page = Tools::getValue('page');
        if (!$this->isPageValid($page)) {
            Response::die400();
        }

        /** @var TranslationUtility $translationUtility */
        $translationUtility = ServiceRegister::getService(TranslationUtility::class);

        $notificationController = new NotificationController();
        $notificationList = $notificationController->get(
            self::NOTIFICATIONS_PER_PAGE,
            self::NOTIFICATIONS_PER_PAGE * (int)$page
        );

        $notifications = array_map(function ($notification) use ($translationUtility) {
            $date = date('d-m-Y h:i:s A', $notification->getTimestamp());
            $desc = $notification->getDescription();
            $message = $notification->getMessage();

            $notificationArray['id'] = $notification->getId();
            $notificationArray['orderNumber'] = $notification->getOrderNumber();
            $notificationArray['severity'] = $notification->getSeverity();
            $notificationArray['message'] = $translationUtility->translateMessage(
                $message->getMessageKey(),
                $message->getMessageParams()
            );
            $notificationArray['description'] = $translationUtility->translateMessage(
                $desc->getMessageKey(),
                $desc->getMessageParams()
            );
            $notificationArray['date'] = $date;

            return $notificationArray;
        }, $notificationList->getNotifications());

        $response = array(
            'notifications' => $notifications,
            'pageCount' => (int)ceil($notificationList->getTotalCount() / self::NOTIFICATIONS_PER_PAGE)
        );

        Response::die200($response);
    }

    /**
     * Validates notifications 'page' parameter.
     *
     * @param int $page Page query parameter
     *
     * @return bool Validation status
     */
    private function isPageValid($page)
    {
        return $page !== false && is_numeric($page) && (intval($page) >= 0);
    }
}
