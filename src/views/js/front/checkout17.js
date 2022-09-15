/**
 * Disables Place order button on checkout page if Company name is not set.
 */
$(document).ready(function () {
    let divPlaceOrder = document.getElementById('payment-confirmation');
    let placeOrder = $(divPlaceOrder).find('[type=submit]');
    let companyName = $('#biller-company-name');
    let formConditions = document.getElementById('conditions-to-approve');
    let checkBox = $(formConditions).find('[type=checkbox]');
    let billerForm = document.getElementById('biller-form');
    let billerDiv = billerForm.parentNode;
    let selected = $("input[type='radio'][name='payment-option']:checked");

    let observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutationRecord) {
            if (!mutationRecord.target.getAttribute('style') && !companyName.val()) {
                disablePlaceOrderButton();
            }
        });
    });

    observer.observe(billerDiv, {attributes: true, attributeFilter: ['style']});
    checkBox.change(function (event) {
        if (checkBox.is(":checked") && !companyName.val() && selected.attr('data-module-name') === 'biller') {
            disablePlaceOrderButton();
            event.stopPropagation();
        }
    })

    companyName.on('input', function () {
        if (!companyName.val()) {
            disablePlaceOrderButton();
        } else if (checkBox.is(":checked")) {
            placeOrder.removeAttr('disabled')
            placeOrder.removeClass('disabled')
        }
    });

    function disablePlaceOrderButton()
    {
        placeOrder.attr('disabled', 'disabled');
        placeOrder.addClass('disabled');
    }
})
