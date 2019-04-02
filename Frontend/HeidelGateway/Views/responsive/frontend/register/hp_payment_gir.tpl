{$URL}
{*{block name="hp_payment_gir"}*}
	{*<div class="{$grid} {$classname}" style='background-color: transparent;'>*}
		{*<div class="newreg_{$pm}" {if {$hasReg{$pm}}} style="display: none;" {/if}>	*}

			{*<label>{s name='hp_country' namespace='frontend/register/hp_payment'}{/s}*:</label><br />*}
			{*<select id="cardBrand" name="ACCOUNT.COUNTRY">*}
				{*{foreach key=ccode item=country from=$bankCountry[$pm]}*}
					{*<option value='{$ccode}' >{$country}</option>*}
				{*{/foreach}*}
			{*</select><br />*}
			{**}
			{*<label>{s name='hp_AccountHolder' namespace='frontend/register/hp_payment'}{/s}*:</label><br />*}
			{*<input type="text" class="text" value="{if $form_data.hpcc_holder == ''}{$user.billingaddress.firstname} {$user.billingaddress.lastname}{else}{$form_data.hpcc_holder|escape}{/if}" id="cardHolder" name="ACCOUNT.HOLDER"><br />*}

			{*<div>*}
				{*<label>{s name='hp_iban' namespace='frontend/register/hp_payment'}{/s}*:</label><br />*}
				{*<input type="text" class="text " value="" id="iban" name="ACCOUNT.IBAN"><br />*}
			{*</div>*}
			{*<div>			*}
				{*<label>{s name='hp_bic' namespace='frontend/register/hp_payment'}{/s}*:</label><br />*}
				{*<input type="text" class="text " value="" id="bic" name="ACCOUNT.BIC"><br />*}
			{*</div>*}

			{*<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>*}
		{*</div>*}
	{*</div>*}
{*{/block}*}