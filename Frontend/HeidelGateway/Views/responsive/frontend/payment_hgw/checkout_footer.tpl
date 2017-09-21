{extends file='frontend/checkout/cart_footer.tpl'}
{block name='frontend_checkout_cart_footer_field_labels_shipping' append}
{if !empty($zinsen)}
	<li class="list--entry block-group entry--interests">
		<div class="entry--label block interest-label">
			{s name='hp_interest' namespace='frontend/register/hp_payment'}{/s}:
		</div>
		<div class="entry--value block interest-value">
			{$zinsen} €
		</div>
	</li>
{/if}	
{/block}

{block name='frontend_checkout_cart_footer_field_labels_total' append}
{if !empty($totalWithInterest)}
	<li class="list--entry block-group entry--interests entry--total" style="color:#e74c3c">
		<div class="entry--label block totalWithInterest-label">
			{s name='hp_totalInterest' namespace='frontend/register/hp_payment'}{/s}:
		</div>
		<div class="entry--value block totalWithInterest-value is--no-star">
			{$totalWithInterest} €
		</div>
	</li>
{/if}	
{/block}