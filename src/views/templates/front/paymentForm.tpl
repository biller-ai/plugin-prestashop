<div class="form-fields">
    <p>{$description}</p>
    <form id="biller-form" action="{$action}">
        <div class="form-group row">
            <label class="form-control-label col-md-3" for="biller-company-name">{l s='Company name'}</label>
            <div class="col-md-6">
                <input class="form-control" id="biller-company-name" type="text" name="company_name"
                       value="{$companyName}"
                       required>
            </div>
        </div>
        <div class="form-group row">
            <label class="form-control-label col-md-3" for="registration_number">{l s='Registration number'}</label>
            <div class="col-md-6">
                <input id="registration_number" class="form-control" type="text" name="registration_number">
            </div>
            <div class="col-md-3 form-control-comment"> Recommended</div>
        </div>
        <div class="form-group row">
            <label class="form-control-label col-md-3" for="vat_number">{l s='VAT number'}</label>
            <div class="col-md-6">
                <input id="vat_number" class="form-control" type="text" name="vat_number" value="{$VAT}">
            </div>
            <div class="col-md-3 form-control-comment"> Optional</div>
        </div>
    </form>
</div>