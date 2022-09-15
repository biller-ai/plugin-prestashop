{extends file="helpers/form/form.tpl"}
{block name="input"}
    {if $input.type === 'biller-password'}
        <div class="input-group fixed-width-lg biller-wrapper-password">
            <span class="input-group-addon">
                <i class="icon-key"></i>
            </span>
            <input type="password"
                   id="{if isset($input.id)}{$input.id|intval}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
                   name="{if isset($input.name)}{$input.name|escape:'html':'UTF-8'}{/if}"
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
                      data-show-label="{l s='Show' mod='biller'}"
                      data-hide-label="{l s='Hide' mod='biller'}">{l s='Show' mod='biller'}
              </button>
            </span>
        </div>
    {elseif $input.type === 'biller-h2'}
        <br>
        <h2>{$input.title|escape:'html':'UTF-8'}</h2>
    {elseif $input.type === 'biller-notification-tab'}
        <div data-tab-id="notifications" class="biller-notifications">
            <table>
                <thead>
                <tr>
                    <th class="column-id">#</th>
                    <th class="column-date">{l s='Date' mod='biller'}</th>
                    <th class="column-type">{l s='Type' mod='biller'}</th>
                    <th class="column-order-number">{l s='Order number' mod='biller'}</th>
                    <th class="column-message">{l s='Message' mod='biller'}</th>
                    <th class="column-details">{l s='Details' mod='biller'}</th>
                </tr>
                </thead>
                <tbody id="notifications-table">
                </tbody>
            </table>
        </div>
        <template id="notification-template">
            <tr>
                <td class="column-id">%id%</td>
                <td>%date%</td>
                <td>
                    <button type="button" class="notification-type notification-type-%severity%">%severityLabel%</button>
                </td>
                <td>#%orderNumber%</td>
                <td class="column-message">%message%</td>
                <td class="column-details">%description%</td>
            </tr>
        </template>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
