var Biller = Biller || {};

/**
 * Handles cancellation on button click.
 */
function cancelOrder(orderId)
{
    const endpointURL = $('input[name="CANCEL_URL"]').val();

    Biller.ajaxService().post(endpointURL, {'orderId': orderId}, (response, status) => {

        if (status === 200) {
            location.reload();
        } else {
            let ajaxDiv = document.getElementById('ajax_confirmation');
            let data = JSON.parse(response);
            ajaxDiv.innerText = data['message'];
            ajaxDiv.removeAttribute('style');
            alertError(ajaxDiv)
        }
    });
}

/**
 * Handles order capture on button click.
 */
function captureOrder(orderId)
{
    const endpointURL = $('input[name="CAPTURE_URL"]').val();
    Biller.ajaxService().post(endpointURL, {'orderId': orderId}, (response, status) => {
        if (status === 200) {
            location.reload();
        } else {
            let ajaxDiv = document.getElementById('ajax_confirmation');
            let data = JSON.parse(response);
            ajaxDiv.innerText = data['message'];
            ajaxDiv.removeAttribute('style');
            alertError(ajaxDiv)
        }
    });
}

/**
 * Displays error message on order page.
 */
function alertError(ajaxDiv)
{
    ajaxDiv.classList.remove('alert-success');
    ajaxDiv.classList.add('alert-danger');
}
