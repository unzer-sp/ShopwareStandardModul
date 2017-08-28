{include file='frontend/checkout/cart_footer.tpl'}

{* copied Block from cart_footer.tpl and used labels from namespace='frontend/checkout/cart_footer' and added 2 labels *}

{block name='frontend_checkout_cart_footer_field_labels'}
	{* Field labels *}
	<div id="aggregation_left" class="grid_4">
		<p>
			<strong>{s name='CartFooterSum' namespace='frontend/checkout/cart_footer'}{/s}</strong>
		</p>
		<div class="border">
			<p>
				<strong>{s name="CartFooterShipping" namespace='frontend/checkout/cart_footer'}{/s}</strong>
			</p>
		</div>
		<div class="totalamount border">
			<p>
				<strong>{s name="CartFooterTotal" namespace='frontend/checkout/cart_footer'}{/s}</strong>
			</p>
		</div>
	
		{if $sUserData.additional.charge_vat}
			<div class="tax">
				<p>
					<strong>{s name="CartFooterTotalNet" namespace='frontend/checkout/cart_footer'}{/s}</strong>
				</p>
			</div>
			{foreach $sBasket.sTaxRates as $rate=>$value}
				<div>
					<p>
						<strong>{s name="CartFooterTotalTax" namespace='frontend/checkout/cart_footer'}{/s}</strong>
					</p>
				</div>
			{/foreach}
		{/if}
		
		<div class="strong doubleborder">
			<p>
				<strong>{s name='hp_interest' namespace='frontend/register/hp_payment'}{/s}</strong>
			</p>
		</div>
		<div class="totalamount" style="color:#e74c3c">
			<p>
				<strong>{s name='hp_totalInterest' namespace='frontend/register/hp_payment'}{/s}</strong>
			</p>
		</div>
	</div>
{/block}

{* copied Block from cart_footer.tpl and appended 2 values *}
{block name='frontend_checkout_cart_footer_tax_rates' append}
	<p class="textright doubleborder">
		<strong>{$zinsen|currency}</strong>
	</p>	
	<p class="textright totalamount " style="color:#e74c3c">
		<strong>{$totalWithInterest|currency}</strong>
	</p>
{/block}