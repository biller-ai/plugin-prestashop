<?php

use Biller\BusinessLogic\Notifications\NotificationController;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\Utility\Response;

/**
 * NotificationsHubController class.
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
     * Fetches notifications
     *
     * @return void
     */
    public function displayAjaxFetchNotifications() {
        $page = Tools::getValue('page');
        if (!$this->isPageValid($page)) {
            Response::dieJson(array('status' => false));
        }

        $notificationController = new NotificationController();
        $notificationList = $notificationController->get(
            self::NOTIFICATIONS_PER_PAGE,
            self::NOTIFICATIONS_PER_PAGE * (int)$page
        );

        $notifications = array_map(function($notification) {
            return $notification->toArray();
        }, $notificationList->getNotifications());

        Response::dieJson(array(
            'status' => true,
            'notifications' => $notifications,
        ));

        echo 'hello';
    }

    /**
     * Validates Notifications page parameter.
     *
     * @param int $page Page query parameter
     *
     * @return bool
     */
    private function isPageValid($page)
    {
        return $page && is_numeric($page) && (intval($page) >= 0);
    }
}
