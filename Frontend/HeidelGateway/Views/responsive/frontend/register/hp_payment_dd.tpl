{block name="hp_payment_dd"}
	<div class="{$classname}" style='background-color: transparent;'>
		{if isset($regData.$pm)}
			{assign var="hasReg{$pm}" value=true}
			<div class="reuse_{$pm}" {if {$hasReg{$pm}}} style="display: block;" {/if}>
				{s name='hp_reuse' namespace='frontend/register/hp_payment'}{/s}
				
				<table {if $isMobile} style="table-layout: fixed;"{else}style="table-layout: inherit;"{/if}>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.owner}</td></tr>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_ktoOrIban' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.kto}</td></tr>
					{if $regData.$pm.blz != ''}<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_blzOrBic' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.blz}</td></tr>{/if}
				</table>
			</div>
		{/if}

		<div class="newreg_{$pm}" id="payType" {if {$hasReg{$pm}}} style="display: none;" {/if}>
			{if ($heidel_iban == '2')}
				<!--  <label>{s name='hp_accInfo' namespace='frontend/register/hp_payment'}{/s}:</label><br />
				<select id="sepa_switch" name="hpdd_sepa">
					<option value="iban">{s name='hp_sepa_iban' namespace='frontend/register/hp_payment'}{/s}</option>
					<option value="noiban">{s name='hp_sepa_classic' namespace='frontend/register/hp_payment'}{/s}</option>
				</select><br />-->
			{/if}
	
			{if ($ddWithGuarantee) == 'true'}
			<div>
				<label>{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				{if $salutation == 'MS' || $salutation == 'MRS'}
					<select id="salutation" name="hpdd_salutation">
						<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MRS" selected>{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
					</select><br />
				{else}
					<select id="salutation" name="hpdd_salutation">
					<option value="MR" selected>{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
					<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
				</select><br />	
				{/if}
			</div>
			{/if}	
			<div>
				<label>{s name='hp_accHolder' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				<input type="text" class="text " value="{if $form_data.hpcc_holder == ''}{$user.billingaddress.firstname} {$user.billingaddress.lastname}{else}{$form_data.hpcc_holder|escape}{/if}" id="accHolder" name="ACCOUNT.HOLDER"><br />
			</div>
			{if ($ddWithGuarantee) == 'true'}
			<div>
				<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				{if isset($regData)}
					<!--{assign var=payment_data value=$regData|json_decode:1}-->
					{assign var=birthdate value=$regData}
					{html_select_date|utf8_encode time=$birthdate.birthdate.formatted start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
				{else}
					{html_select_date|utf8_encode start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
				{/if}
				<input type="hidden" id="birthdate_dd" value="" name="NAME.BIRTHDATE">
			</div>
			{/if}
			
			{if ($heidel_iban == '0') || ($heidel_iban == '1') || ($heidel_iban == '2')}
			<div id="ibanLabelField">
				<label>{s name='hp_iban' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				<input type="text" class="text " value="" id="iban" name="ACCOUNT.IBAN"><br />
			</div>
			{/if}
			
			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
		
		{if isset($regData.$pm)}
		<div class="space">&nbsp;</div>
		<div><input class="reues_{$pm}" type='checkbox' onclick="hgwToggleReuse('_dd')">{s name='hp_reenter' namespace='frontend/register/hp_payment'}{/s}</div>{/if}
	</div>
{/block}