/**
 * Handle displaying pop up window and biller input form.
 * Disables Pay order button on checkout page if Company name is not set.
 */
function popUp()
{
    $.fancybox({
        'padding': 2,
        'max-width': 400,
        'width': 400,
        'height': 'auto',
        'fitToView': true,
        'autoSize': true,
        'type': 'inline',
        'content': $('#biller-form').html()
    })
    let companyName = $('.fancybox-inner #biller-company-name');
    let payButton = $('.fancybox-inner #biller-pay-button');
    companyName.on('input', function () {
        if (!companyName.val()) {
            payButton.attr('disabled', 'disabled');
        } else {
            payButton.removeAttr('disabled');
        }
    })
}


