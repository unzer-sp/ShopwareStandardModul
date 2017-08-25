{block name="hp_payment_dd"}
	<div class="{$grid} {$classname}" style='background-color: transparent; border: 0;'>
		{if isset($regData.$pm)}
			{assign var="hasReg{$pm}" value=true}
			<div class="reuse_{$pm}" {if {$hasReg{$pm}}} style="display: block;" {/if}>
				{s name='hp_reuse' namespace='frontend/register/hp_payment'}{/s}
				<table>
					<tr><td>{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.owner}</td></tr>
					<tr><td>{s name='hp_ktoOrIban' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.kto}</td></tr>
					{if $regData.$pm.blz != ''}<tr><td>{s name='hp_blzOrBic' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.blz}</td></tr>{/if}
				</table>
			</div>
		{/if}

		<div class="newreg_{$pm}" id="payType" {if {$hasReg{$pm}}} style="display: none;" {/if}>
			{if ($heidel_iban == '2')}
				<script type='text/javascript'>
					$(document).ready(function(){
						if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
						if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }
						
						jQuery('#sepa_switch').change(function(){
							if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
							if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }
						});
						
						function iban(){
							jQuery('.newreg_dd #account').parent().hide();
							jQuery('.newreg_dd #bankcode').parent().hide();
							jQuery('.newreg_dd #cardBrand').parent().hide();
							jQuery('.newreg_dd #cardBrand').parent().prevAll('label:first').hide();
							jQuery('.newreg_dd #iban').parent().show();
//							jQuery('.newreg_dd #bic').parent().show();
						}					
						function noiban(){
							jQuery('.newreg_dd #account').parent().show();
							jQuery('.newreg_dd #bankcode').parent().show();
							jQuery('.newreg_dd #cardBrand').parent().show();
							jQuery('.newreg_dd #cardBrand').parent().prevAll('label:first').show();
							jQuery('.newreg_dd #iban').parent().hide();
//							jQuery('.newreg_dd #bic').parent().hide();
						}
						jQuery('.newreg_dd #iban').on('input', function(){
							if(jQuery(this).val().match(/^(D|d)(E|e)/)){
								jQuery('.newreg_dd #bic').parent().fadeOut();
								jQuery('.newreg_dd #bic').attr('disabled', 'disabled');
							}else{
								jQuery('.newreg_dd #bic').removeAttr('disabled');
								jQuery('.newreg_dd #bic').parent().fadeIn();
							}
						});
					});
				</script>
			{/if}
		
			{if ($ddWithGuarantee) == 'true'}
				<div>
					<label for="salutation">{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label>
					{if $salutation == 'MRS'}
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
					
					<!--<select id="salutation" name="hpdd_salutation">
						<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
					</select><br />-->
				</div>	
			{/if}
			
			<label>{s name='hp_accHolder' namespace='frontend/register/hp_payment'}{/s}*:</label>
			<input type="text" class="text " value="{if $form_data.hpcc_holder == ''}{$user.billingaddress.firstname} {$user.billingaddress.lastname}{else}{$form_data.hpcc_holder|escape}{/if}" id="accHolder" name="ACCOUNT.HOLDER"><br />
			
			{if ($ddWithGuarantee) == 'true'}
				<div>
					<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
					{if isset($birthdate)}
						{assign var=birthdate value=$regData}
						{html_select_date|utf8_encode time=$birthdate start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
					{else}
						{html_select_date|utf8_encode start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
					{/if}
					<input type="hidden" id="birthdate" value="" name="NAME.BIRTHDATE">
				</div>
			{/if}
			
			{if ($heidel_iban == '0') || ($heidel_iban == '1') || ($heidel_iban == '2')}
			<div>
				<label>{s name='hp_iban' namespace='frontend/register/hp_payment'}{/s}*:</label>
				<input type="text" class="text " value="" id="iban" name="ACCOUNT.IBAN"><br />
			</div>
			{/if}
			
			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
		
		{if isset($regData.$pm)}
		<div class="space">&nbsp;</div>
		<div><input class="reues_{$pm}" type='checkbox'>{s name='hp_reenter' namespace='frontend/register/hp_payment'}{/s}</div>{/if}
	</div>
{/block}