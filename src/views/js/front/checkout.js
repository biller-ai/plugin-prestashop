$(document).ready(function () {
    let divPlaceOrder = document.getElementById('payment-confirmation');
    let placeOrder = $(divPlaceOrder).find('[type=submit]');
    let companyName = document.getElementById('biller-company-name');
    let formConditions = document.getElementById('conditions-to-approve');
    let checkBox = $(formConditions).find('[type=checkbox]');
    let billerForm = document.getElementById('biller-form');
    let billerDiv = billerForm.parentNode;

    let observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutationRecord) {
            if(!mutationRecord.target.getAttribute('style') && !$(companyName).val() ){
                placeOrder.attr('disabled', 'disabled');
                placeOrder.addClass('disabled');
            }
        });
    });

    observer.observe(billerDiv, { attributes : true, attributeFilter : ['style'] });
    $(checkBox).change(function (event) {
        let selected = $("input[type='radio'][name='payment-option']:checked");
        if($(checkBox).is(":checked") && !$(companyName).val() && $(selected).attr('data-module-name') === 'biller') {
            placeOrder.attr('disabled', 'disabled');
            placeOrder.addClass('disabled');
            event.stopPropagation();
        }
    })
    $(companyName).on('input', function () {
        if (!$(companyName).val()) {
            placeOrder.attr('disabled', 'disabled');
            placeOrder.addClass('disabled');
        } else if($(checkBox).is(":checked")) {
            placeOrder.removeAttr('disabled')
            placeOrder.removeClass('disabled')
        }
    });
})
