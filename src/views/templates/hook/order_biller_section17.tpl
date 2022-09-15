<div class="tab-pane" id="billerTabContent">
    <p class="mb-1" style="padding-bottom: 1em">
        <strong>{l s='Payment status' mod='biller'}: </strong>
        <span id="biller-order-status">
            {html_entity_decode($status|escape:'html':'UTF-8')}
        </span>
    </p>
    {if $paymentLink}
        <p class="mb-1">
            <strong>{l s='Payment link' mod='biller'}</strong>
        </p>
        <div class="form-group" style="padding-bottom: 3em">
            <div class="col-lg-6" style="padding-left: inherit">
                <input type="text"
                       name="billerPaymentLinkInput"
                       value="{$paymentLink}"
                       disabled>
            </div>
            <div class="col-lg-1">
                <button name="billerPaymentLinkButton" class="btn btn-default biller-payment-link-button">
                    {l s='Copy' mod='biller'}
                </button>
            </div>
        </div>
    {/if}
    {if $accepted}
        <div class="form-group">
            <input type="hidden" name="CANCEL_URL" value="{html_entity_decode($cancelURL|escape:'html':'UTF-8')}">
            <button class="btn btn-default"
                    onclick="cancelOrder({html_entity_decode($orderId|escape:'html':'UTF-8')})">
                {l s='Cancel' mod='biller'}
            </button>
            <input type="hidden" name="CAPTURE_URL" value="{html_entity_decode($captureURL|escape:'html':'UTF-8')}">
            <button class="btn btn-default"
                    onclick="captureOrder({html_entity_decode($orderId|escape:'html':'UTF-8')})">
                {l s='Capture' mod='biller'}
            </button>
        </div>
    {/if}
</div>