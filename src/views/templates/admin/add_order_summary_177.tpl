<div class="form-group row type-text biller-display" id="biller-company-name-div">
    <label for="biller-company-name" class="form-control-label">
        <span class="text-danger">*</span>
        {l s='Company name' mod='biller'}
    </label>
    <div class="col-sm input-container">
        <input type="text" id="biller-company-name" name="biller-company-name" class="form-control"/>
    </div>
</div>
<div class="form-group row type-text biller-display" id="biller-registration-number-div">
    <label for="biller-registration-number" class="form-control-label">
        {l s='Registration number' mod='biller'}
    </label>
    <div class="col-sm input-container">
        <input type="text" id="biller-registration-number" name="biller-registration-number"
               class="form-control"/>
    </div>
</div>
<div class="form-group row type-text biller-display" id="biller-vat-number-div">
    <label for="biller-vat-number" class="form-control-label">
        {l s='VAT number' mod='biller'}
    </label>
    <div class="col-sm input-container">
        <input type="text" id="biller-vat-number" name="biller-vat-number" class="form-control"/>
    </div>
</div>
<input name="biller-company-info-url" class="custom-file biller-display" value="{$companyInfoURL}"/>
