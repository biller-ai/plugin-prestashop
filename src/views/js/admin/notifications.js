var Biller = Biller || {};

/**
 * Service for handling front-end notfications hub logic and display.
 *
 * @returns {{init: init}}
 */
const notificationService = () => {
    const tabAuthorization = $('a[href="#authorization"]').parent();
    const tabSettings = $('a[href="#settings"]').parent();
    const tabNotifications = $('a[href="#notifications"]').parent();

    const btnSubmit = $('#btn_submit');
    const btnNext = $('#btn_next');
    const btnPrevious = $('#btn_previous');
    btnPrevious.attr('disabled', true);
    const endpointURL = $('input[name="NOTIFICATION_ENDPOINT_URL"]').val();

    const tableBody = $('#notifications-table');
    const notificationTemplate = $('#notification-template').html();

    const classHidden = 'hidden';

    let spinner = null;

    const SEVERITY_MAP = [
        {
            classSufix: "info",
            label: "Info"
        },
        {
            classSufix: "warning",
            label: "Warning"
        },
        {
            classSufix: "error",
            label: "Error"
        },
    ];

    let currentPage = 0;

    /**
     * Hides/shows configuration form buttons on active tab switch.
     *
     * @param tab Clicked on configuration form tab
     */
    const switchToTab = (tab) => {
        const selectedNotifications = tab === tabNotifications;

        btnSubmit.toggleClass(classHidden, selectedNotifications);
        btnNext.toggleClass(classHidden, !selectedNotifications);
        btnPrevious.toggleClass(classHidden, !selectedNotifications);

        if (selectedNotifications) {
            if (!spinner) {
                createSpinner();

                Biller.ajaxService().post(endpointURL, {'page': currentPage}, (response, status) => {
                    hideSpinner();
                    if (status === 200) {
                        let data = JSON.parse(response);
                        if (data['pageCount'] === 1 || data['pageCount'] === 0) {
                            btnNext.attr('disabled', true);
                        }
                        renderNotifications(data['notifications']);
                    }
                });

                showSpinner();
            }
        }
    };

    /**
     * Sends request for next page of notifications on 'next' button click.
     */
    const nextPage = () => {
        showSpinner()
        Biller.ajaxService().post(endpointURL, {'page': currentPage + 1}, (response, status) => {
            hideSpinner();
            if (status === 200) {
                let data = JSON.parse(response);
                renderNotifications(data['notifications']);
                currentPage++;
                if ((currentPage + 1) === data['pageCount']) {
                    btnNext.attr('disabled', true);
                }
                btnPrevious.attr('disabled', false);
            }
        });
    }

    /**
     * Sends request for previous page of notifications on 'previous' button click.
     */
    const previousPage = () => {
        if (currentPage > 0) {
            showSpinner();
            Biller.ajaxService().post(endpointURL, {'page': currentPage - 1}, (response, status) => {
                hideSpinner();
                if (status === 200) {
                    let data = JSON.parse(response);
                    renderNotifications(data['notifications']);
                    currentPage--;
                    if (currentPage === 0) {
                        btnPrevious.attr('disabled', true);
                    }
                    btnNext.attr('disabled', false);
                }
            });
        }
    }

    /**
     * Generates HTML table row for given notification data.
     *
     * @param data Notification JSON data
     *
     * @returns {string} HTML of notification table row as string.
     */
    const generateNotificationFromTemplate = (data) => {
        return notificationTemplate.replace(
            /%(\w*)%/g,
            (m, key) => data.hasOwnProperty(key) ? data[key] : ""
        ).trim();
    }

    /**
     * Renders fetched notifications.
     *
     * @param notifications Notifications to be rendered.
     */
    const renderNotifications = (notifications) => {
        tableBody.empty();
        for (const notification of notifications) {
            tableBody.append(generateNotificationFromTemplate({
                id: notification.id,
                date: notification.date,
                severity: SEVERITY_MAP[notification.severity].classSufix,
                severityLabel: SEVERITY_MAP[notification.severity].label,
                orderNumber: notification.orderNumber,
                message: notification.message,
                description: notification.description
            }));
        }
    }

    /**
     * Adds click event listeners on form tabs.
     */
    const initTabs = () => {
        tabAuthorization.click(() => switchToTab(tabAuthorization));
        tabSettings.click(() => switchToTab(tabSettings));
        tabNotifications.click(() => switchToTab(tabNotifications));
    }

    /**
     * Adds click event listeners on form buttons.
     */
    const initButtons = () => {
        btnNext.click(() => nextPage());
        btnPrevious.click(() => previousPage());
    }

    /**
     * Initializes notification service.
     */
    const init = () => {
        initTabs();
        initButtons();
    }

    /**
     * Creates the spinner element.
     */
    const createSpinner = () => {
        $('#notifications').append(
            '<div id="biller-spinner-div" class="' + classHidden + '"><div id="biller-spinner"></div></div>'
        );
        spinner = $('#biller-spinner-div');
    }

    /**
     * Shows the spinner element.
     */
    const showSpinner = () => {
        spinner.toggleClass(classHidden, false);
    }

    /**
     * Hides the spinner element.
     */
    const hideSpinner = () => {
        spinner.toggleClass(classHidden, true);
    }

    return {
        init: init
    };
};

let notificationServiceInstance = null;
Biller.notificationService = () => {
    if (!notificationServiceInstance) {
        notificationServiceInstance = notificationService();
    }

    return notificationServiceInstance;
};
