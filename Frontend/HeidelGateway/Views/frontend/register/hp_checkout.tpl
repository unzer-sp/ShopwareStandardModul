{* Table foot *}
{block name='frontend_checkout_cart_cart_footer'}
	{include file="frontend/checkout/cart_footer.tpl"}
	</div>

	<div class="space">&nbsp;</div>
	{* Action Buttons *}
	{include file="frontend/checkout/actions.tpl"}
	<div class="space">&nbsp;</div>
	{if (isset($avPayments.hgw_mpa) && $avPayments.hgw_mpa.active == '1')}
		{block name="masterpass_button"}{/block}
		<div class="space">&nbsp;</div>
	{/if}
	<div class="clear"></div>
	<div class="doublespace"></div>

	{if $sPremiums}
	<div class="table_head">
		<div class="grid_19">{s name="sCartPremiumsHeadline" namespace="frontend/checkout/premiums"}Bitte w&auml;hlen Sie zwischen den folgenden Pr&auml;mien{/s}</div>
	</div>
	{/if}
	{* Premium articles *}
	{include file='frontend/checkout/premiums.tpl'}
{/block}

{block name="masterpass_button"}
	<a href="{url controller='PaymentHgw' action='wallet' wallet='masterpass'}" class="right wallet">
		<img style="width: 215px;" src="https://www.mastercard.com/mc_us/wallet/img/{$lang}/{$langStu}/mcpp_wllt_btn_chk_290x068px.png" alt="MasterPass" />
	</a>
	<a href="https://www.mastercard.com/mc_us/wallet/learnmore/{$lang}/{$langStu}/" style="clear: both; margin: 10px 0 0 0;" class="right" target="_blank">
	{s name='hp_moreMpa' namespace='frontend/register/hp_payment'}{/s}</a>

	<script type='text/javascript'>
		var mobile = "{$isMobile}";
		$(document).ready(function(){
			jQuery('.wallet').click(function(){
				jQuery('#lbOverlay').fadeIn(350);
				$.loadingIndicator.open();
				
				if(mobile){
					// workaround for mobile devices
					var href = $(this).attr('href');
					setTimeout(function(){ window.location = href }, 500);
				}
			});
		});
	</script>
{/block}