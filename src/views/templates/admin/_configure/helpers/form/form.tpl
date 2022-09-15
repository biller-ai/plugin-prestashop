{extends file="helpers/form/form.tpl"}
{block name="input"}
    {if $input.type === 'biller-password'}
        <div class="input-group fixed-width-lg biller-wrapper-password">
            <span class="input-group-addon">
                <i class="icon-key"></i>
            </span>
            <input type="password"
                   id="{if isset($input.id)}{$input.id|intval}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
                   name="{$input.name|escape:'html':'UTF-8'}"
                   class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if} js-visible-password"
                   value="{$fields_value[$input.name]|escape:'html':'UTF-8'}"
                   {if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if}
                    {if isset($input.required) && $input.required } required="required" {/if}
            />
            <span class="input-group-btn">
              <button
                      class="btn biller-password-hide-btn"
                      type="button"
                      data-action="show-hide-password"
                      name="show-hide-button"
                      data-show-label="{l s='Show'}"
                      data-hide-label="{l s='Hide'}">{l s='Show'}
              </button>
            </span>
        </div>
    {elseif $input.type === 'biller-h2'}
        <br>
        <h2>{$input.title|escape:'html':'UTF-8'}</h2>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
