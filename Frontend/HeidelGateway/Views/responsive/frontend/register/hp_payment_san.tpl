{block name="hp_payment_san"}
	<div class="{$grid} {$classname}" style='background-color: transparent;'>
		<img src="{$optin_San_logoUrl}" alt="Santander-Logo"><br />
		<div class="newreg_{$pm}" id="payType">
			<div>
				<label>{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
                {if isset($salutation_san)}
                    {if ($salutation_san == "MR")}
						<select id="salutation" name="NAME.SALUTATION" class="hgw_val_san">
							<option value="MR" selected="selected">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
							<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
						</select><br />
                    {else}
						<select id="salutation" name="NAME.SALUTATION" class="hgw_val_san">
							<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
							<option value="MRS" selected="selected">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
						</select><br />
                    {/if}	<!-- salutation == mr -->
                {else}
					<select id="salutation" class="hgw_required hgw_val_san" name="NAME.SALUTATION">
						<option value="UNKNOWN">{s name='hp_accSal_gender' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
					</select><br />
                {/if} <!-- if isset salutation -->
                {if isset($accountHolder_San)}
					<input type="text" value="{$accountHolder_San}" disabled><br />
                {/if}

			</div>
			<br />
			<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />

            {*{if isset($birthdate)}*}
            {*{assign var=payment_data value=$birthdate}*}
            {*{html_select_date|utf8_encode time=$payment_data start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}*}
            {*{else}*}
			{assign var=payment_data value=$birthdate_san}
            {if isset($birthdate_san)}
                {html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true'
				prefix ='DateSan_'
				day_value_format='%02d'
				field_order='DMY'}
            {else}
				{html_select_date|utf8_encode time=$payment_data start_year='-14' end_year='-100' reverse_years='true'
				prefix ='DateSan_'
				day_value_format='%02d' field_order='DMY'
				day_empty="{s name='hp_valueDay' namespace='frontend/register/hp_payment'}{/s}"
				month_empty="{s name='hp_valueMonth' namespace='frontend/register/hp_payment'}{/s}"
				year_empty="{s name='hp_valueYear' namespace='frontend/register/hp_payment'}{/s}"}
			{/if}
            {if isset($birthdate_san)}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_san" value="{$birthdate_san}">
            {else}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_san" value="-">
            {/if}

			<div>
				<label for="hgw_adv_san">{s name='hp_sanAdvPermission' namespace='frontend/register/hp_payment'}{/s}:</label></br>
                {if $checkOptin_San == "TRUE"}
					<input type="checkbox" id="hgw_adv_san" name="CUSTOMER.OPTIN" value="TRUE" class="checkbox" checked="checked">
				{else}
					<input type="checkbox" id="hgw_adv_san" name="CUSTOMER.OPTIN" value="TRUE" class="checkbox">
				{/if}
				{$optin_San_adv}<br /><br />

				<label for="hgw_privacyPolicy">{s name='hp_sanPrivacyPolicy' namespace='frontend/register/hp_payment'}{/s} *:</label></br>
				{if $optin_San_privpol == "TRUE"}
					<input type="checkbox" id="hgw_privacyPolicy" class="hgw_required" name="CUSTOMER.ACCEPT_PRIVACY_POLICY" value="TRUE" class="checkbox" checked="checked">
				{else}
					<input type="checkbox" id="hgw_privacyPolicy" class="hgw_required" name="CUSTOMER.ACCEPT_PRIVACY_POLICY" value="TRUE" class="checkbox">
				{/if}
				{$optin_San_privpol}<br /><br />
			</div>

			<input type="hidden" name="BRAND" id="handover_brand_san" value="SANTANDER">
			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
	</div>
{/block}
