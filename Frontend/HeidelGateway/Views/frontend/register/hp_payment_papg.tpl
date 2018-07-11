{block name="hp_payment_papg"}
	<div class="{$grid} {$classname}" style='background-color: transparent;'>
		<div class="newreg_{$pm}" id="payType" style="width: 22rem;">
			<label>{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
			{if isset($salutation)}
				{if ($salutation === "MR")}
					<select id="salutation" name="NAME.SALUTATION">
						<option value="MR" selected="selected">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
					</select><br />
				{else}
					<select id="salutation" name="NAME.SALUTATION">
						<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MRS" selected="selected">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
					</select><br />	
				{/if}	<!-- salutation == mr -->
			{else}
				<select id="salutation" name="NAME.SALUTATION">
					<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
					<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
				</select><br />
			{/if} <!-- if isset salutation -->
			{if isset($accountHolder)}
			<input type="text" value="{$accountHolder}" disabled><br />
			{/if}
			<br />
			
			<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br /><br />
			{assign var=payment_data value=$birthdate_papg}
			{if isset($birthdate_papg)}
				{html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
			{else}
				{html_select_date|utf8_encode time=$payment_data start_year='-14' end_year='-100' reverse_years='true'
				day_value_format='%02d' field_order='DMY'
				day_empty="{s name='hp_day' namespace='frontend/register/hp_payment'}{/s}"
				month_empty="{s name='hp_month' namespace='frontend/register/hp_payment'}{/s}"
				year_empty="{s name='hp_year' namespace='frontend/register/hp_payment'}{/s}"}
			{/if}
			{if isset($formUrl)}
				<input type="hidden" class="formUrl" value="{$formUrl['papg']}">
			{/if}
			{if isset($birthdate)}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_papg" value="{$birthdate}">
			{else}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_papg" value="">
			{/if}
			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
	</div>
{/block}