{block name="hp_payment_pf"}
	<div class="{$grid} {$classname}" style='background-color: transparent;'>
		<div class="newreg_{$pm}">
		
			<label>{s name='hp_cardBrand' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
			<select id="cardBrand" name="ACCOUNT.BRAND">
				{foreach key=brand item=brandname from=$cardBrands[$pm]}
					<option value='{$brand}'>{$brandname}</option>
				{/foreach}
			</select>

			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
	</div>
{/block}