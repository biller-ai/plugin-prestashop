/**
 * Admin Biller payment order creation extension logic.
 */
$(document).ready(function () {
    const summary = document.getElementById('summary_part');

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutationRecord) {
            const displayHiddenClass = 'biller-display';
            const paymentSelectInput16 = $('#payment_module_name');
            const orderStatusSelectInput = $('#id_order_state');
            const paymentSelectDiv = paymentSelectInput16.parent().parent();
            const orderStatusSelectDiv = orderStatusSelectInput.parent().parent();

            const companyNameDiv = $("#biller-company-name-div");
            const registrationNumberDiv = $("#biller-registration-number-div");
            const vatNumberDiv = $("#biller-vat-number-div");

            const companyInfoURL = $('input[name="biller-company-info-url"]').val();
            let companyNameInput = $("#biller-company-name"),
                vatNumberInput = $("#biller-vat-number"),
                registrationNumberInput = $("#biller-registration-number");

            const createOrderButton = $("[name='submitAddOrder']");

            vatNumberDiv.insertAfter(paymentSelectDiv);
            registrationNumberDiv.insertAfter(paymentSelectDiv);
            companyNameDiv.insertAfter(paymentSelectDiv);

            paymentSelectInput16.on('change', function () {
                let billerSelected = paymentSelectInput16.val() === 'biller';
                companyNameInput.prop('required', billerSelected);
                orderStatusSelectDiv.toggleClass(displayHiddenClass, billerSelected);
                if (!billerSelected) {
                    createOrderButton.prop('disabled', false);
                }

                toggleBillerForm(billerSelected);
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
                    const cartId = $('#id_cart').val();
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
    });

    if (summary) {
        observer.observe(summary, {attributes: true, attributeFilter: ['style']});
    }
})
