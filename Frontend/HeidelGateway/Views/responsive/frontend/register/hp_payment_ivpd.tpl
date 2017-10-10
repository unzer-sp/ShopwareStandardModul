{block name="hp_payment_ivpd"}
    <div class="{$grid} {$classname}" style='background-color: transparent;'>
        <div class="newreg_{$pm}" id="payType" style="width: 30rem;">
            {* Code Sascha *}
            <div>
                <label>{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
                {if isset($salutation)}
                    {if ($salutation == "MRS")}
                        <select id="salutation" class="hgw_val_ivpd" name="NAME.SALUTATION">
                            <option value="MR" >{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
                            <option value="MRS" selected="selected">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
                        </select><br />
                    {else}
                        <select id="salutation" class="hgw_val_ivpd" name="NAME.SALUTATION">
                            <option value="MR" selected="selected">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
                            <option value="MRS" >{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
                        </select><br />
                    {/if}	<!-- salutation == mrs -->
                {else}
                    <select id="salutation" class="hgw_val_ivpd" name="NAME.SALUTATION">
                        <option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
                        <option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
                    </select><br />
                {/if} <!-- if isset salutation -->
                {if isset($accountHolder)}
                    <input type="text" value="{$accountHolder}" disabled><br />
                {/if}

            </div>
            <br />
            <label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
            {if isset($birthdate)}
                {assign var=payment_data value=$birthdate}
                {html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
            {else}
                {html_select_date|utf8_encode start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
            {/if}

            {if isset($birthdate)}
                <input type="hidden" name="NAME.BIRTHDATE" id="birthdate_ivpd" value="{$birthdate}">
            {else}
                <input type="hidden" name="NAME.BIRTHDATE" id="birthdate_ivpd" value="-">
            {/if}

            <p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
        </div>
    </div>
{/block}