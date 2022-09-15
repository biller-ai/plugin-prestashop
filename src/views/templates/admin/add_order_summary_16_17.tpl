<div class="form-group biller-display" id="biller-company-name-div">
    <label for="biller-company-name" class="control-label col-lg-3 required">
        <span class="label-tooltip" data-toggle="tooltip" data-html="true">
			{l s='Company name' mod='biller'}
		</span>
    </label>
    <div class="col-lg-4">
        <input type="text" id="biller-company-name" name="biller-company-name"/>
    </div>
</div>
<div class="form-group biller-display" id="biller-registration-number-div">
    <label for="biller-registration-number" class="control-label col-lg-3">
        {l s='Registration number' mod='biller'}
    </label>
    <div class="col-lg-4">
        <input type="text" id="biller-registration-number" name="biller-registration-number"/>
    </div>
</div>
<div class="form-group biller-display" id="biller-vat-number-div">
    <label for="biller-vat-number" class="control-label col-lg-3">
        {l s='VAT number' mod='biller'}
    </label>
    <div class="col-lg-4">
        <input type="text" id="biller-vat-number" name="biller-vat-number"/>
    </div>
</div>
<input name="biller-company-info-url" class="custom-file biller-display" value="{$companyInfoURL}"/>
