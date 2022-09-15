/**
 * Admin Biller payment order creation extension logic.
 */
$(document).ready(function () {
    const displayHiddenClass = 'biller-display';
    const paymentSelectInput = $('#cart_summary_payment_module')
    const orderStatusSelectInput = $('#cart_summary_order_state');

    const paymentSelectDiv = paymentSelectInput.parent().parent();
    const orderStatusSelectDiv = orderStatusSelectInput.parent().parent();
    const companyNameDiv = $("#biller-company-name-div");
    const registrationNumberDiv = $("#biller-registration-number-div");
    const vatNumberDiv = $("#biller-vat-number-div");

    const companyInfoURL = $('input[name="biller-company-info-url"]').val();
    let companyNameInput = $("#biller-company-name"),
        vatNumberInput = $("#biller-vat-number"),
        registrationNumberInput = $("#biller-registration-number");

    let createOrderButton = $('#create-order-button');

    vatNumberDiv.insertAfter(paymentSelectDiv);
    registrationNumberDiv.insertAfter(paymentSelectDiv);
    companyNameDiv.insertAfter(paymentSelectDiv);

    if (typeof billerAvailable !== 'undefined' && !billerAvailable) {
        $("#cart_summary_payment_module option[value='biller']").remove();

        return;
    }

    /**
     * Display Biller form if selected.
     */
    paymentSelectInput.on('change', () => {
        const billerSelected = paymentSelectInput.val() === 'biller';
        companyNameInput.prop('required', billerSelected);
        orderStatusSelectDiv.toggleClass(displayHiddenClass, billerSelected);

        toggleBillerForm(billerSelected);
        if (!billerSelected) {
            createOrderButton.prop('disabled', false);
        }
    });

    /**
     * Validate company name length.
     */
    companyNameInput.on('input', () => {
        createOrderButton.prop('disabled', !companyNameInput.val().length);
    })

    /**
     * Validate vat number is numeric value.
     */
    vatNumberInput.on('input', () => {
        createOrderButton.prop('disabled', isNaN(vatNumberInput.val()));
    })

    /**
     * Validate registration number is numeric value.
     */
    registrationNumberInput.on('input', () => {
        createOrderButton.prop('disabled', isNaN(registrationNumberInput.val()));
    })

    /**
     * Toggles Biller payment method form.
     *
     * @param isBillerSelected Biller payment method form visibility status
     */
    function toggleBillerForm(isBillerSelected) {
        if (isBillerSelected) {
            orderStatusSelectInput.val('' + billerPendingStatus);

            const cartId = $('.js-place-order-cart-id').val();
            Biller.ajaxService().get(companyInfoURL + '&cartId=' + cartId, {}, (response, status) => {
                if (status === 200) {
                    response = JSON.parse(response);

                    companyNameInput.val(response.companyName);
                    if (!isNaN(response.vatNumber)) {
                        vatNumberInput.val(response.vatNumber);
                    }

                    toggleInputsVisible(true);
                }
            });
        } else {
            toggleInputsVisible(false);
        }
    }

    /**
     * Shows/hides Biller input fields.
     *
     * @param status Input field visibility status to be set
     */
    function toggleInputsVisible(status) {
        companyNameDiv.toggleClass(displayHiddenClass, !status);
        registrationNumberDiv.toggleClass(displayHiddenClass, !status);
        vatNumberDiv.toggleClass(displayHiddenClass, !status);
    }
});
