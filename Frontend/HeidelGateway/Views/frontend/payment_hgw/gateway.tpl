{extends file="frontend/checkout/confirm.tpl"}
{block name='frontend_index_content_left'}{/block}

{* Javascript *}
{block name="frontend_index_header_javascript" append}
	<script type="text/javascript">
		$(document).ready(function($){
			jQuery('#payment_frame').css('display', 'none');
			jQuery('#payment_loader').css('display', 'block');
			
			jQuery('#payment_frame').load(function(){
				jQuery('#payment_loader').css('display', 'none');
				jQuery('#payment_frame').css('display', 'block');
			});
		});
	</script>
	
	{if $swfActive}
		<script type='text/javascript' src='{$pluginPath}/Views/frontend/_resources/javascript/valPaymentSWF.js'></script>
	{else}
		<script type='text/javascript' src='{$pluginPath}/Views/frontend/_resources/javascript/valPayment.js'></script>
	{/if}
	<script type='text/javascript' src='{$pluginPath}/Views/frontend/_resources/javascript/hpf_script.js'></script>
{/block}

{block name="frontend_index_header_css_screen" append}
	<link type="text/css" media="all" rel="stylesheet" href="{$pluginPath}/Views/frontend/_resources/styles/hpf_style.css" />
{/block}

{* Main content *} 
{block name="frontend_index_content"}

{if $isMobile || $swfActive}<link rel="stylesheet" media="all" type="text/css" href="{$pluginPath}/Views/frontend/register/mobile.css">{/if}

<div id="payment" class="grid_20">
	{if $PaymentUrl}
		{if $useIframe}
			{assign var='formTarget' value='payment_frame'}
			<h2 class="headingbox_dark largesize">{s name='PaymentHeader' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
			<center>
				<iframe name="payment_frame" id="payment_frame" width="500px" frameborder="0" border="0" src="{$PaymentUrl}" style="width: 450px; border: 0px solid #000;"></iframe>
			</center>
			<div id="payment_loader" class="ajaxSlider" style="height: 100px; border: 0 none; display: none">
				<div class="loader" style="width: 80px; margin-left: -50px;">{s name='PaymentInfoWait' namespace='frontend/payment_heidelpay/gateway'}{/s}</div>
			</div>
		{else}
			{assign var='formTarget' value='_top'}
		{/if}
		
		{if $Input}
			<h2 class="headingbox_dark largesize">{s name='PaymentHeader' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
			<form name="heidelpay" action="{$PaymentUrl}" target="{$formTarget}" method="post" autocomplete="off">
				<div style="width: 480px; margin: auto;">
					{foreach from=$Input key=k item=v}
						<input type="hidden" name="{$k}" value="{$v}" />
					{/foreach}

					<script>
                    	$(document).ready(function($){
                        	jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                            $($.loadingIndicator.config.overlay).fadeTo($.loadingIndicator.config.animationSpeed, $.loadingIndicator.config.overlayOpacity);
                            $.loadingIndicator.open();

							$(document).ready(function(){
                            	document.forms['heidelpay'].submit();
							});
						});
					</script>

					<noscript>
						<h2 class="headingbox_dark largesize">{s name='PaymentRedirect' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
						<br />
						<input type="submit" class="button-right large right" value="{s name='ListingLinkNext' namespace='frontend/content/paging'}{/s}"><br/>
					</noscript>
				</div>
			</form>
		{/if}
	{elseif $formUrl}
		{assign var="grid" value="grid_10"}
		{assign var="classname" value="payment_method debit"}

		{block name='hp_js'}
			<div style="display: none;">
				<div class='msg_checkPymnt'>{s name='RegisterPaymentHeadline' namespace='frontend/register/payment_fieldset'}{/s}</div>
				<div class='msg_fill'>{s name='ErrorFillIn' namespace='frontend/account/internalMessages'}{/s}</div>
				<div class='msg_crdnr'>{s name='ErrorCrdNr' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_cvv'>{s name='ErrorCvv' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_iban'>{s name='ErrorIban' namespace='frontend/register/hp_payment'}{/s}</div>
				<!-- <div class='msg_bic'>{s name='ErrorBic' namespace='frontend/register/hp_payment'}{/s}</div> -->
				<div class='msg_account'>{s name='ErrorAccount' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_bank'>{s name='ErrorBank' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_exp'>{s name='ErrorExp' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_salut'>{s name='ErrorSalut' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_cb'>{s name='ErrorCb' namespace='frontend/register/hp_payment'}{/s}</div>
			</div>
		{/block}

		<script type='text/javascript'>
        	$(document).ready(function(){
            	//add error div
                if(jQuery('#payment .error').length < 1){
                	jQuery('#payment').prepend('<div class="error" style="display: none;"><h2>{s name='RegisterErrorHeadline' namespace='frontend/register/error_message'}{/s}</h2><ul></ul></div>');
				}
			});
		</script>

		<h2 class="headingbox_dark largesize">{s name='PaymentHeader' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
		{if ($pm == 'cc') || ($pm == 'dc')}
			<form name="heidelpay" class="payment" action='' method='post' autocomplete="off" onsubmit="return valGatewayForm();">
				<div class="payment register" style="width: 345px; margin: auto;">
					{assign var=formUrl value=[$pm=>$formUrl]}
					{assign var="classname" value="{$classname} hgw_{$pm}"}
					{include file="frontend/register/hp_payment_{$pm}.tpl" pm=$pm formUrl=$formUrl classname=$classname}
		{else}
			<form name="heidelpay" class="payment" action='{$formUrl}' method='post' autocomplete="off" onsubmit="return valGatewayForm();">
				<div class="payment register" style="width: 480px; margin: auto;">
				{if $pm == 'dd'}
					{include file="frontend/register/hp_payment_dd.tpl" pm=$pm bankCountry=$bankCountry heidel_iban=$heidel_iban grid=$grid classname=$classname}
				{elseif $pm == 'gir'}
					{include file="frontend/register/hp_payment_gir.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry heidel_iban=$heidel_iban grid=$grid classname=$classname}
				{elseif $pm == 'eps'}
					{include file="frontend/register/hp_payment_eps.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry heidel_iban=$heidel_iban grid=$grid classname=$classname}
				{elseif $pm == 'ide'}
					{include file="frontend/register/hp_payment_ide.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry heidel_iban=$heidel_iban grid=$grid classname=$classname}
				{elseif $pm == 'va'}
					{include file="frontend/register/hp_payment_va.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry heidel_iban=$heidel_iban grid=$grid classname=$classname}
				{elseif $pm == 'pf'}
					{include file="frontend/register/hp_payment_pf.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry grid=$grid classname=$classname}
				{elseif $pm == 'san'}
					{include file="frontend/register/hp_payment_san.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry grid=$grid classname=$classname}
				{elseif $pm == 'papg'}
					{include file="frontend/register/hp_payment_papg.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry grid=$grid classname=$classname}
                {elseif $pm == 'hpr'}
                    {include file="frontend/register/hp_payment_hpr.tpl" pm=$pm grid=$grid classname=$classname}
				{else}
					{if !isset($pm)}
						<script>
                        	$(document).ready(function($){
                            	jQuery('.payment.register').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                                $($.loadingIndicator.config.overlay).fadeTo($.loadingIndicator.config.animationSpeed, $.loadingIndicator.config.overlayOpacity);
								$.loadingIndicator.open();

								$(document).ready(function(){
                                	document.forms['heidelpay'].submit();
								});
							});
						</script>
					{/if}
				{/if}
		{/if}

					<input type="hidden" name='CRITERION.GATEWAY' value='1' />
					{if $DbOnRg}<input type="hidden" name='CRITERION.DBONRG' value='{$DbOnRg}' />{/if}
					{if !$showButton and $pm != 'gir' and $pm != 'ide' and $pm != 'pf' and $pm != 'eps' and $pm != 'san' and $pm != 'papg'}<noscript>
						<h2 class="headingbox_dark largesize">{s name='PaymentRedirect' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>{/if}
						<br />
						<a class="button-left large left" href="{url controller=payment_hgw action=cancel}">
							<span>{s name='hp_cancelPay' namespace='frontend/register/hp_payment'}{/s}</span>
						</a>
						<input type="submit" class="button-right large right" value="{s name='ListingLinkNext' namespace='frontend/content/paging'}{/s}">

					{if !$showButton and $pm != 'gir' and $pm != 'ide' and $pm != 'pf' and $pm != 'eps' and $pm != 'san' and $pm != 'papg'}</noscript>{/if}
				</div>
			</form>
	{else}
		<h2 class="headingbox_dark largesize">{s name='PaymentHeader' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
		<form name="heidelpay" action="{$RedirectURL}" target="_top" method="post" autocomplete="off">
			<div style="width: 480px; margin: auto;">
				{foreach from=$Input key=k item=v}
					<input type="hidden" name="{$k}" value="{$v}" />
				{/foreach}

				<script>
                	$(document).ready(function($){
                    	jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                        $($.loadingIndicator.config.overlay).fadeTo($.loadingIndicator.config.animationSpeed, $.loadingIndicator.config.overlayOpacity);
                        $.loadingIndicator.open();

                        $(document).ready(function(){
                            document.forms['heidelpay'].submit();
                        });
					});
					</script>

				<noscript>
					<h2 class="headingbox_dark largesize">{s name='PaymentRedirect' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
					<br />
					<input type="submit" class="button-right large right" value="{s name='ListingLinkNext' namespace='frontend/content/paging'}{/s}"><br/>
				</noscript>
			</div>
		</form>
	{/if}
</div>
<div class="doublespace">&nbsp;</div>
{/block}