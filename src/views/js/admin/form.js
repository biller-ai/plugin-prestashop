var Biller = Biller || {};

$(document).ready(function () {
    let billerWrapper = $(".biller-wrapper-password");
    for (let wrapper of billerWrapper) {
        Biller.passwordWidget.init(wrapper);
    }
});
