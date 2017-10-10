{extends file="parent:frontend/checkout/confirm.tpl"}
{block name='frontend_index_content_left'}{/block}

{* Javascript *}
{block name="frontend_index_header_javascript_jquery_lib" append}
	{if $swVersion >= "5.3"}
		<script type="text/javascript">
			document.asyncReady(function() {
				jQuery('#payment_frame').css('display', 'none');
				jQuery('#payment_loader').css('display', 'block');

				jQuery('#payment_frame').load(function(){
					jQuery('#payment_loader').css('display', 'none');
					jQuery('#payment_frame').css('display', 'block');
				});
			});
		</script>

		<script type='text/javascript'>
			document.asyncReady(function() {
				//add error div
				if(jQuery('#payment .panel.has--border .alert--content').length < 1){
					jQuery('#payment').prepend('<div class="alert is--error is--rounded" style="display: none;"><div class="alert--icon"><i class="icon--element icon--cross"></i></div><div class="alert--content"><ul class="alert--list"></ul></div></div>');
				}
			});
		</script>
    {else}
		<script type="text/javascript">
            $(document).ready(function(){
                jQuery('#payment_frame').css('display', 'none');
                jQuery('#payment_loader').css('display', 'block');

                jQuery('#payment_frame').load(function(){
                    jQuery('#payment_loader').css('display', 'none');
                    jQuery('#payment_frame').css('display', 'block');
                });
            });
		</script>
		<script type='text/javascript'>
           $(document).ready(function(){
				//add error div
                if(jQuery('#payment .panel.has--border .alert--content').length < 1){
                    jQuery('#payment').prepend('<div class="alert is--error is--rounded" style="display: none;"><div class="alert--icon"><i class="icon--element icon--cross"></i></div><div class="alert--content"><ul class="alert--list"></ul></div></div>');
                }
            });
		</script>
	{/if}

	{if isset($pluginPath) && $pluginPath != ''}
		<script type='text/javascript'>var swVersion = "{$swVersion}";</script>
		{if $swVersion >= "5.3"}
			<script type='text/javascript' src='{$pluginPath}/Views/responsive/frontend/_public/src/js53/valPayment.js' defer='defer'></script>
			<script type='text/javascript' src='{$pluginPath}/Views/responsive/frontend/_public/src/js53/hpf_script.js' defer='defer'></script>
		{else}
			<script type='text/javascript' src='{$pluginPath}/Views/responsive/frontend/_public/src/js52/valPayment.js'></script>
			<script type='text/javascript' src='{$pluginPath}/Views/responsive/frontend/_public/src/js52/hpf_script.js'></script>
		{/if}


	{/if}

    {if $swVersion >= "5.3"}
		<script type='text/javascript'>
            //sepa switch
            document.asyncReady(function() {
                var call = true;
                if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
                if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }

                jQuery('#sepa_switch').change(function(){
                    if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
                    if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }
                });

                function iban(){
                    if(jQuery('#iban').parent().is(':hidden') || call){
                        jQuery('#account').parent().hide();
                        jQuery('#bankcode').parent().hide();
                        jQuery('#iban').parent().show();
                        jQuery('#bic').parent().show();
                        call = false;
                    }
                }
                function noiban(){
                    if(jQuery('#account').parent().is(':hidden') || call){
                        jQuery('#account').parent().show();
                        jQuery('#bankcode').parent().show();
                        jQuery('#iban').parent().hide();
                        jQuery('#bic').parent().hide();
                        call = false;
                    }
                }
                jQuery('#iban').on('input', function(){
                    if(jQuery(this).val().match(/^(D|d)(E|e)/) && !$('#bic').parent().parent().hasClass('newreg_gir')){
                        jQuery('#bic').parent().fadeOut();
                        jQuery('#bic').attr('disabled', 'disabled');
                    }else{
                        jQuery('#bic').removeAttr('disabled');
                        jQuery('#bic').parent().fadeIn();
                    }
                });
            });
		</script>
    {else}
		<script type='text/javascript'>
            //sepa switch
            $(document).ready(function(){
                var call = true;
                if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
                if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }

                jQuery('#sepa_switch').change(function(){
                    if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }
                    if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }
                });

                function iban(){
                    if(jQuery('#iban').parent().is(':hidden') || call){
                        jQuery('#account').parent().hide();
                        jQuery('#bankcode').parent().hide();
                        jQuery('#iban').parent().show();
                        jQuery('#bic').parent().show();
                        call = false;
                    }
                }
                function noiban(){
                    if(jQuery('#account').parent().is(':hidden') || call){
                        jQuery('#account').parent().show();
                        jQuery('#bankcode').parent().show();
                        jQuery('#iban').parent().hide();
                        jQuery('#bic').parent().hide();
                        call = false;
                    }
                }
                jQuery('#iban').on('input', function(){
                    if(jQuery(this).val().match(/^(D|d)(E|e)/) && !$('#bic').parent().parent().hasClass('newreg_gir')){
                        jQuery('#bic').parent().fadeOut();
                        jQuery('#bic').attr('disabled', 'disabled');
                    }else{
                        jQuery('#bic').removeAttr('disabled');
                        jQuery('#bic').parent().fadeIn();
                    }
                });
            });
		</script>
	{/if}


	{if isset($PaymentUrl)}
    	{if $swVersion >= "5.3"}
            {if isset($Input)}
				<script type='text/javascript'>
                    //               $(document).ready(function(){
                    document.asyncReady(function() {
                        jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                        $.overlay.open();
                        $.loadingIndicator.open();
                        document.forms['heidelpay'].submit();
                    });
				</script>
            {/if}
		{else}
            {if isset($Input)}
				<script type='text/javascript'>
                    $(document).ready(function(){
                        jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                        $.overlay.open();
                        $.loadingIndicator.open();
                        document.forms['heidelpay'].submit();
                    });
				</script>
            {/if}
		{/if}
	{elseif isset($formUrl)}
    	{if $swVersion >= "5.3"}
			{if !isset($pm)}
				<script type='text/javascript'>
					document.asyncReady(function() {
						jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
						$.overlay.open();
						$.loadingIndicator.open();
						document.forms['heidelpay'].submit();
					});
				</script>
			{/if}
		{else}
            {if !isset($pm)}
				<script type='text/javascript'>
                    $(document).ready(function(){
                        jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                        $.overlay.open();
                        $.loadingIndicator.open();
                        document.forms['heidelpay'].submit();
                    });
				</script>
            {/if}
		{/if}

	{else}
    	{if $swVersion >= "5.3"}
			<script type='text/javascript'>
                document.asyncReady(function() {
                    jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                    $.overlay.open();
                    $.loadingIndicator.open();
                    document.forms['heidelpay'].submit();
                });
			</script>
		{else}
			<script type='text/javascript'>
				$(document).ready(function(){
                    jQuery('#payment form[name="heidelpay"] div').prepend("<h2>{s name='PaymentRedirectInfo' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>");
                    $.overlay.open();
                    $.loadingIndicator.open();
                    document.forms['heidelpay'].submit();
                });
			</script>
		{/if}


	{/if}
{/block}

{* Main content *} 
{block name="frontend_index_content"}

{if $isMobile || $swfActive}<link rel="stylesheet" media="all" type="text/css" href="{$pluginPath}/Views/responsive/frontend/register/mobile.css">{/if}

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
					<noscript>
						<h2 class="headingbox_dark largesize">{s name='PaymentRedirect' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
						<input class="btn is--primary register--submit right" type="submit" value="Weiter"/>
					</noscript>
				</div>
			</form>
		{/if}
	{elseif $formUrl}
		<h2 class="headingbox_dark largesize">{s name='PaymentHeader' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
		{assign var="grid" value="grid_10"}
		{assign var="classname" value="payment_method debit"}
		
		{block name='hp_js'}
			<div style="display: none;">
				<div class='msg_checkPymnt'>{s name='RegisterPaymentHeadline' namespace='frontend/register/payment_fieldset'}{/s}</div>
				<div class='msg_fill'>{s name='ErrorFillIn' namespace='frontend/account/internalMessages'}{/s}</div>
				<div class='msg_crdnr'>{s name='ErrorCrdNr' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_cvv'>{s name='ErrorCvv' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_iban'>{s name='ErrorIban' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_bic'>{s name='ErrorBic' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_account'>{s name='ErrorAccount' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_bank'>{s name='ErrorBank' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_exp'>{s name='ErrorExp' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_salut'>{s name='ErrorSalut' namespace='frontend/register/hp_payment'}{/s}</div>
				<div class='msg_cb'>{s name='ErrorCb' namespace='frontend/register/hp_payment'}{/s}</div>
			</div>
		{/block}

		{if ($pm == 'cc') || ($pm == 'dc')}
			<form name="heidelpay" class="payment" action='' method='post' autocomplete="off" onsubmit="return valGatewayForm();">
				<div class="payment register" style="width: 290px; margin: auto;">
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
				{* && !$hasReg *}
				{elseif $pm == 'papg'}
					{include file="frontend/register/hp_payment_papg.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry grid=$grid classname=$classname}
				{elseif $pm == 'san'}
					{include file="frontend/register/hp_payment_san.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry grid=$grid classname=$classname}
                {elseif $pm == 'ivpd'}
                    {include file="frontend/register/hp_payment_ivpd.tpl" pm=$pm cardBrands=$cardBrands bankCountry=$bankCountry grid=$grid classname=$classname}
                {elseif $pm == 'hpr'}
                    {include file="frontend/register/hp_payment_hpr.tpl" pm=$pm grid=$grid classname=$classname}
				{else}
					{if !isset($pm)}
						<noscript><h2 class="headingbox_dark largesize">{s name='PaymentRedirect' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2></noscript>
					{/if}
				{/if}
		{/if}
					<input type="hidden" name='CRITERION.GATEWAY' value='1' />
					{if $DbOnRg}<input type="hidden" name='CRITERION.DBONRG' value='{$DbOnRg}' />{/if}
					{if !$showButton and $pm != 'gir' and $pm != 'ide' and $pm != 'pf' and $pm != 'eps' and $pm != 'san' and $pm != 'papg'}<noscript>{/if}
						<a class="btn is--secondary left" href="{url controller=payment_hgw action=cancel}">
							<span>{s name='hp_cancelPay' namespace='frontend/register/hp_payment'}{/s}</span>
						</a>
						<input type="submit" class="btn is--primary register--submit right" value="{s name='ListingLinkNext' namespace='frontend/content/paging'}{/s}">
					{if !$showButton and $pm != 'gir' and $pm != 'ide' and $pm != 'pf' and $pm != 'san' and $pm != 'papg'}</noscript>{/if}
				</div>
			</form>
	{else}
		<h2 class="headingbox_dark largesize">{s name='PaymentHeader' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
		<form name="heidelpay" action="{$RedirectURL}" method="post" target="_top" autocomplete="off">
			<div style="width: 480px; margin: auto;">
				{foreach from=$Input key=k item=v}
					<input type="hidden" name="{$k}" value="{$v}" />
				{/foreach}
				<noscript>
					<h2 class="headingbox_dark largesize">{s name='PaymentRedirect' namespace='frontend/payment_heidelpay/gateway'}{/s}</h2>
					<input class="btn is--primary register--submit right" type="submit" value="Weiter"/>
				</noscript>
			</div>
		</form>
	{/if}
</div>
<div class="doublespace">&nbsp;</div>
{/block}