<p class="payment_module">
    <a class="bankwire" onclick="popUp()" title="{$biller_name}">
        {$biller_name}&nbsp;<span> ({$biller_description})</span>
    </a>
</p>

<div id="biller-form" style="display: none ">
    <div id="biller-form" class="box" style="margin-bottom: 0;">
        <p style="margin-bottom: 1em">{$biller_description}</p>
        <form class="std" action="{$action}">
            <div class="required form-group">
                <label for="biller-company-name">{l s='Company name' mod='biller'} <sup>*</sup></label>
                <input class="is_required validate form-control" id="biller-company-name" type="text"
                       name="company_name"
                       value="{$biller_company_name}"
                       required>
            </div>
            <div class="form-group">
                <label for="registration_number">{l s='Registration number (recommended)' mod='biller'}</label>
                <input id="registration_number" class="form-control" type="text" name="registration_number">
            </div>
            <div class="form-group">
                <label for="vat_number">{l s='VAT number (optional)' mod='biller'}</label>
                <input id="vat_number" class="form-control" type="text" name="vat_number" value="{$biller_vat_number}">
            </div>
            <div style="text-align: right">
                <button type="submit" id="biller-pay-button" class="btn btn-default button button-medium">
                    <span>
                    Pay
                    <i class="icon-chevron-right right"></i>
                    </span>
                </button>
            </div>

        </form>
    </div>
</div>