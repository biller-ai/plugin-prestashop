$(document).ready(() => {
    const paymentLinkInput = $('input[name="billerPaymentLinkInput"]'),
    paymentLinkButton = $('button[name="billerPaymentLinkButton"]');

    paymentLinkButton.click(() => {
        navigator.clipboard.writeText(paymentLinkInput[0].value);
    });
});
