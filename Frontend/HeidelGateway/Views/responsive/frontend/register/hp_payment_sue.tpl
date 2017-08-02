{block name="hp_payment_sue"}
	<div class="{$grid} {$classname}" style='background-color: transparent;'>
		<div class="newreg_{$pm}" {if {$hasReg{$pm}}} style="display: none;" {/if}>	

			<label>{s name='hp_country' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
			<select id="cardBrand" name="ACCOUNT.COUNTRY">
				{foreach key=ccode item=country from=$bankCountry[$pm]}
					<option value='{$ccode}' >{$country}</option>
				{/foreach}
			</select><br />
			
			<label>{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
			<input type="text" class="text" value="{if $form_data.hpcc_holder == ''}{$user.billingaddress.firstname} {$user.billingaddress.lastname}{else}{$form_data.hpcc_holder|escape}{/if}" id="cardHolder" name="ACCOUNT.HOLDER"><br />
			
			{if ($heidel_iban == '2')}
				<label>{s name='hp_accInfo' namespace='frontend/register/hp_payment'}{/s}:</label><br />
				<select id="sepa_switch" name="hpdd_sepa">
					<option value="noiban">{s name='hp_sepa_classic' namespace='frontend/register/hp_payment'}{/s}</option>
					<option value="iban">{s name='hp_sepa_iban' namespace='frontend/register/hp_payment'}{/s}</option>
				</select><br />
			{/if}
	
			{if ($heidel_iban == '0') || ($heidel_iban == '2')}
			<div>
				<label>{s name='hp_account' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				<input type="text" class="text " value="" id="account" name="ACCOUNT.NUMBER"><br />
			</div>
			<div>
				<label>{s name='hp_bank' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				<input type="text" class="text " value="" id="bankcode" name="ACCOUNT.BANK"><br />
			</div>
			{/if}
			{if ($heidel_iban == '1') || ($heidel_iban == '2')}
			<div>
				<label>{s name='hp_iban' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				<input type="text" class="text " value="" id="iban" name="ACCOUNT.IBAN"><br />
			</div>
			<div>
				<label>{s name='hp_bic' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
				<input type="text" class="text " value="" id="bic" name="ACCOUNT.BIC"><br />
			</div>
			{/if}

			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
	</div>
{/block}