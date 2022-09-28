var Biller = Biller || {};

/**
 * Handles cancellation on button click.
 */
function cancelOrder(orderId) {
    const endpointURL = $('input[name="CANCEL_URL"]').val();

    Biller.ajaxService().post(endpointURL, {'orderId': orderId}, (response, status) => {
        location.reload();
    });
}

/**
 * Handles order capture on button click.
 */
function captureOrder(orderId) {
    const endpointURL = $('input[name="CAPTURE_URL"]').val();
    Biller.ajaxService().post(endpointURL, {'orderId': orderId}, (response, status) => {
        location.reload();
    });
}
