var Biller = Biller || {};

/**
 * Initializes module confirmation form.
 */
$(document).ready(() => {
    let billerWrapper = $(".biller-wrapper-password");

    if (!Array.isArray(billerWrapper)) {
        billerWrapper = [billerWrapper];
    }

    for (let wrapper of billerWrapper) {
        Biller.passwordWidget.init(wrapper);
    }

    Biller.notificationService().init();
});
