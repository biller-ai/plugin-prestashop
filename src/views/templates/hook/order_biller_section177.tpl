<div class="tab-pane d-print-block fade active show" id="billerTabContent" aria-labelledby="billerTab">
    <p class="mb-1">
        <strong>{l s='Payment status' mod='biller'}: </strong>
        <span id="biller-order-status">
            {html_entity_decode($status|escape:'html':'UTF-8')}
        </span>
    </p>
    {if $paymentLink}
        <p class="mb-1" style="padding-top: 1em">
            <strong>{l s='Payment link' mod='biller'}</strong>
        </p>
        <div class="form-group row biller-payment-link">
            <input type="text"
                   name="billerPaymentLinkInput"
                   class="col-md-6 form-control "
                   value="{$paymentLink}"
                   disabled>
            <button name="billerPaymentLinkButton" class="btn btn-sm btn-outline-secondary biller-payment-link-button">
                {l s='Copy' mod='biller'}
            </button>
        </div>
    {/if}
    {if $accepted}
        <div class="form-group row-buttons" style="padding-top: 1em">
            <input type="hidden" name="CANCEL_URL" value="{html_entity_decode($cancelURL|escape:'html':'UTF-8')}">
            <button class="btn btn-sm btn-outline-secondary"
                    onclick="cancelOrder({html_entity_decode($orderId|escape:'html':'UTF-8')})">
                {l s='Cancel' mod='biller'}
            </button>
            <input type="hidden" name="CAPTURE_URL" value="{html_entity_decode($captureURL|escape:'html':'UTF-8')}">
            <button class="btn btn-sm btn-outline-secondary"
                    onclick="captureOrder({html_entity_decode($orderId|escape:'html':'UTF-8')})">
                {l s='Capture' mod='biller'}
            </button>
        </div>
    {/if}
</div>