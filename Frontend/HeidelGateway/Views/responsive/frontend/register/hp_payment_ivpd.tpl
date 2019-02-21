{block name="hp_payment_ivpd"}
    <div class="{$grid} {$classname}" style='background-color: transparent;'>
        <div class="newreg_{$pm}" id="payType" style="width: 30rem;">
            <div>
                <label>{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
                {if isset($salutation_ivpd)}
                    {if ($salutation_ivpd == "MRS")}
                        <select id="salutation_ivpd" class="hgw_val_ivpd" name="NAME.SALUTATION">
                            <option value="MR" >{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
                            <option value="MRS" selected="selected">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
                        </select><br />
                    {else}
                        <select id="salutation_ivpd" class="hgw_val_ivpd" name="NAME.SALUTATION">
                            <option value="MR" selected="selected">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
                            <option value="MRS" >{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
                        </select><br />
                    {/if}	<!-- salutation == mrs -->
                {else}
                    <select id="salutation_ivpd" class="hgw_val_ivpd" name="NAME.SALUTATION">
                        <option value="UNKNOWN">{s name='hp_accSal_unknown' namespace='frontend/register/hp_payment'}{/s}</option>
                        <option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
                        <option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
                    </select><br />
                {/if}

                <input type="text" value="{$accountHolder}" disabled><br />
            </div>

                <label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
                {assign var=payment_data value=$birthdate_ivpd}
                {if isset($birthdate_ivpd)}
                    {html_select_date|utf8_encode prefix ='DatePay_' time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
                {else}
                    {html_select_date|utf8_encode time=$payment_data start_year='-14' end_year='-100' reverse_years='true'
                    prefix ='DatePay_'
                    day_value_format='%02d' field_order='DMY'
                    day_empty="{s name='hp_valueDay' namespace='frontend/register/hp_payment'}{/s}"
                    month_empty="{s name='hp_valueMonth' namespace='frontend/register/hp_payment'}{/s}"
                    year_empty="{s name='hp_valueYear' namespace='frontend/register/hp_payment'}{/s}"}
                {/if}
                {if isset($birthdate_ivpd)}
                    <input type="hidden" name="NAME.BIRTHDATE" id="birthdate_ivpd" value="{$birthdate}">
                {else}
                    <input type="hidden" name="NAME.BIRTHDATE" id="birthdate_ivpd" value="-">
                {/if}
                <br />

                {* Show Phonenumber for NL-Customers *}
                {if $showPhoneEntry == "TRUE"}
                    <label>{s name='hp_RegisterPhone' namespace='frontend/register/hp_payment'}{/s}*:</label>
                    <div class="register--phone">
                        <input autocomplete="section-personal tel" name="CONTACT_PHONE" type="tel" id="phone_ivpd" value="{$phonenumber_ivpd}" class="register--field">
                    </div>
                {/if}


            {if isset($optinText)}
                <div>
                    <label for="hgw_privpol_ivpd">{s name='hp_sanPrivacyPolicy' namespace='frontend/register/hp_payment'}{/s} *:</label></br>
                    {$optinText}
                </div>
            {/if}

            <input type="hidden" name="BRAND" id="handover_brand_ivpd" value="PAYOLUTION_DIRECT">
            <p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
        </div>
    </div>
{/block}