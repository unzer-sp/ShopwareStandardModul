{extends file='frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' append}
	{$sBreadcrumb[] = ['name'=>"{s name='PaymentProcess' namespace='frontend/payment_heidelpay/error'}{/s}"]}
{/block}

{* Main content *}
{block name='frontend_index_content'}
<div id="center" class="grid_13">
	<div>		
		<form id="payForm" action="{$formUrl}" method="post" autocomplete="off">
			<input type="hidden"value="{$payCode}" name="register[payment]">
			{*	<noscript>	*}
				kein JS - bitte Button selbst klicken<br />
				<input type="submit" value="senden" />
			{*	</noscript>	*}
		</form>		
		<script type='text/javascript'>
			if(window.location.href.indexOf('?') != '-1'){
//				jQuery('#payForm').submit();
			}
		</script>
	</div>
	
	<div style="width: 100%; margin-top: 200px; text-align: center;">
		<img alt="Loading" src="{link file='templates/_default/backend/_resources/resources/themes/images/default/shared/large-loading.gif' fullPath}" />
	</div>
</div>
{/block}

{block name='frontend_index_actions'}{/block}
{block name='frontend_index_checkout_actions'}{/block}
{block name='frontend_index_search'}{/block}
