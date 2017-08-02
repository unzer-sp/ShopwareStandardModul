{extends file='parent:frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' append}
	{$sBreadcrumb[] = ['name'=>"{s name='PaymentProcess' namespace='frontend/payment_heidelpay/cancel'}{/s}"]}
{/block}

{block name='frontend_index_header_javascript_jquery_lib' append}
	<script type="text/javascript">
	if(top!=self){
		top.location=self.location;
	}
	</script>
{/block}

{* Main content *}
{block name='frontend_index_content'}
	<div id="center" class="grid_13">
		<div>
		<h2><img align="left" vspace="10" hspace="20" alt="Warnung" src="{link file='frontend/payment_hgw/img/exclamation_mark.png'}" style=" height: 50px; width: 50px;">
		{s name='PaymentCancel' namespace='frontend/payment_heidelpay/cancel'}{/s}</h2>
		</div>
		<div class="actions">
			<br />
			<br />
			<br />
			<br />
			<br />
			<br />
			<a class="btn is--secondary left" href="{url controller=checkout action=cart}" title="{s name='basket' namespace='frontend/payment_heidelpay/cancel'}{/s}" style="margin: 0 0 0 20px;">
				{s name='basket' namespace='frontend/payment_heidelpay/cancel'}{/s}
			</a>
		</div>
	</div>
{/block}


{block name='frontend_index_actions'}{/block}
{block name='frontend_index_checkout_actions'}{/block}
{block name='frontend_index_search'}{/block}
