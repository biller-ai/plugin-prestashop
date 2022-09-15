<div class="form-fields" id="biller-form" style="margin-top: 1em">
    <p>{$description}</p>
    <form action="{$action}">
        <div class="form-group row">
            <label class="form-control-label col-md-4"
                   for="biller-company-name">{l s='Company name' mod='biller'}</label>
            <div class="col-md-5">
                <input class="form-control" id="biller-company-name" type="text" name="company_name"
                       value="{$companyName}"
                       required>
            </div>
        </div>
        <div class="form-group row">
            <label class="form-control-label col-md-4"
                   for="registration_number">{l s='Registration number' mod='biller'}</label>
            <div class="col-md-5">
                <input id="registration_number" class="form-control" type="text" name="registration_number">
            </div>
            <div class="col-md-1 form-control-comment"> {l s='Recommended' mod='biller'}</div>
        </div>
        <div class="form-group row">
            <label class="form-control-label col-md-4" for="vat_number">{l s='VAT number' mod='biller'}</label>
            <div class="col-md-5">
                <input id="vat_number" class="form-control" type="text" name="vat_number" value="{$VAT}">
            </div>
            <div class="col-md-1 form-control-comment"> {l s='Optional' mod='biller'}</div>
        </div>
    </form>
</div>