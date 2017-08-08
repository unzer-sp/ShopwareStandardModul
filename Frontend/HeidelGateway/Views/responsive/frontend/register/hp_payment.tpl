{extends file="parent:frontend/index/index.tpl"}
{debug}
{* block name="frontend_index_header_javascript_jquery_lib" append *}
{block name="frontend_index_javascript_async_ready" append}
    <script type='text/javascript'>
		// define formUrl to make it useable in external JS
		var formUrl = {$formUrl|@json_encode};
//		$(document).ready(function(){
        document.asyncReady(function() {
			if(jQuery('#center .panel.has--border .alert--content').length < 1){
				jQuery('#center .panel.has--border').prepend('<div class="alert is--error is--rounded" style="display: none;"><div class="alert--icon"><i class="icon--element icon--cross"></i></div><div class="alert--content"><ul class="alert--list"></ul></div>');
			}
		});

		{if isset($token)}
			var token = "{$token}";
		{/if}
	</script>
	
	{*<script type='text/javascript'>*}
{*//		$(document).ready(function(){*}
		{*document.asyncReady(function() {*}
			{*$(document).ibanCheck();*}
{*//			jQuery(document).ibanCheck();*}
        {*});*}
		{*//sepa switch*}
		{*jQuery.fn.ibanCheck = function(){*}
			{*if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }*}
			{*if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }*}

			{*jQuery('#sepa_switch').change(function(){*}
				{*if(jQuery('#sepa_switch :selected').val() == 'iban'){ iban(); }*}
				{*if(jQuery('#sepa_switch :selected').val() == 'noiban'){ noiban(); }*}
			{*});*}

			{*function iban(){*}
				{*if(jQuery('.newreg_dd #iban').is(':hidden')){*}
					{*jQuery('.newreg_dd #account').parent().hide();*}
					{*jQuery('.newreg_dd #bankcode').parent().hide();*}
					{*jQuery('.newreg_dd #cardBrand').parent().hide();*}
					{*jQuery('.newreg_dd #cardBrand').parent().prevAll('label:first').hide();*}
					{*jQuery('.newreg_dd #iban').parent().show();*}
				{*}*}
			{*}*}

			{*function noiban(){*}
				{*if(jQuery('.newreg_dd #account').is(':hidden')){*}
					{*jQuery('.newreg_dd #account').parent().show();*}
					{*jQuery('.newreg_dd #bankcode').parent().show();*}
					{*jQuery('.newreg_dd #cardBrand').parent().show();*}
					{*jQuery('.newreg_dd #cardBrand').parent().prevAll('label:first').show();*}
					{*jQuery('.newreg_dd #iban').parent().hide();*}
				{*}*}
			{*}*}
		{*};*}


	{*</script>*}
	<h1>frontend_index_javascript_async_ready</h1>
    {if $action != 'cart'}
		<script type='text/javascript'>var swVersion = "{$swVersion}";</script>
		<script type='text/javascript' src='{$pluginPath}/Views/responsive/frontend/_public/src/js/valPayment.js' defer='defer'></script>
		<script type='text/javascript' src='{$pluginPath}/Views/responsive/frontend/_public/src/js/hpf_script.js' defer='defer'></script>
    {/if}
{/block}

{block name="hp_payment"}
	{assign var='path' value='frontend/register/hp_payment'}
	{assign var='bar_at' value=$payment_mean.name|strpos:'_'}
	{assign var='pm' value=$payment_mean.name|substr:($bar_at+1)}
	{if $pm == 'pay'}{assign var='pm' value='va'}{/if}
	
	{if $payment_mean.name == "hgw_cc" && $heidel_bm_cc && ($formUrl.$pm != '')}
		{include file="{$tPath|substr:1}/Views/responsive/frontend/register/hp_payment_cc.tpl"}
	{elseif $payment_mean.name == "hgw_dc" && $heidel_bm_dc && ($formUrl.$pm != '')}
		{include file="{$tPath|substr:1}/Views/responsive/frontend/register/hp_payment_dc.tpl"}
	{elseif $payment_mean.name == "hgw_dd" && $heidel_bm_dd && ($formUrl.$pm != '')}
		{include file="{$tPath|substr:1}/Views/responsive/frontend/register/hp_payment_dd.tpl"}
	{elseif $payment_mean.name == "hgw_pay" && $heidel_bm_va && ($formUrl.$pm != '')}
		{include file="{$tPath|substr:1}/Views/responsive/frontend/register/hp_payment_va.tpl" heidel_bm_va=$heidel_bm_va pm='va'}
	{*{elseif $payment_mean.name == "hgw_papg"}
		{include file="{$tPath|substr:1}/Views/responsive/frontend/register/hp_payment_papg.tpl" pm='papg'}
	 *}
	{else}
		{$smarty.block.parent}
	{/if}

	{if $action == 'shippingPayment'}
		<input type="hidden" name='CRITERION.SHIPPAY' value='1' />
	{/if}
{/block}

{block name="hp_radio"}
	{if $action == 'shippingPayment'}
		<input type="radio" name="payment" class="radio auto_submit {$payment_mean.name}" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}" {if $payment_mean.id eq $form_data.payment or ($payment_mean.id == $sPayment.id)} checked="checked"{/if} />
	{else}
		<input type="radio" name="register[payment]" class="radio {if $sRegisterFinished}auto_submit {/if}{$payment_mean.name}" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}" {if $payment_mean.id eq $form_data.payment or ($payment_mean.id == $sPayment.id)} checked="checked"{/if} />
	{/if}
{/block}

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
		<div class='msg_dob'>{s name='ErrorDob' namespace='frontend/register/hp_payment'}{/s}</div>
	</div>
{/block}

{block name='frontend_register_payment_fieldset_template'}
	{if $payment_mean.name|substr:0:$bar_at == 'hgw'}
		{assign var="classname" value="debit {$payment_mean.name}"}
		<div class="payment_logo_{$payment_mean.name}"></div>
		<div class="payment--content{if $payment_mean.id != $form_data.payment} is--hidden{/if}">
			{block name="hp_payment"}{/block}
		</div>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_register_payment_fieldset_description'}
	{if $payment_mean.name == "hgw_mpa"}
	<div class="payment--description panel--td">
		{if $payment_mean.additionaldescription|strip:'' != ''}
			{include file="string:{$payment_mean.additionaldescription}"}<br/>
		{/if}
		{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_mpa.tpl"}
	</div>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="frontend_register_payment_fieldset_input_radio"}
	{assign var='bar_at' value=$payment_mean.name|strpos:'_'}
	{if $payment_mean.name|substr:0:$bar_at == 'hgw'}
		{block name="hp_radio"}{/block}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_checkout_payment_fieldset_template'}
	{if $payment_mean.name|substr:0:$bar_at == 'hgw'}
		{assign var="classname" value="{$payment_mean.name}"}
		<div class="payment--method-logo payment_logo_{$payment_mean.name}"></div>
		<div class="method--bankdata{if $payment_mean.id != $form_data.payment} is--hidden{/if}">
			{block name="hp_payment"}{/block}
		</div>
	{else}
		{$smarty.block.parent}
	{/if}		
{/block}

{block name='frontend_checkout_payment_fieldset_description'}
	{if $payment_mean.name == "hgw_mpa"}
	<div class="method--description is--last">
		{if $payment_mean.additionaldescription|strip:'' != ''}
			{include file="string:{$payment_mean.additionaldescription}"}<br/>
		{/if}
		{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_mpa.tpl"}
	</div>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="frontend_checkout_payment_fieldset_input_radio"}
	{assign var='bar_at' value=$payment_mean.name|strpos:'_'}
	{if $payment_mean.name|substr:0:$bar_at == 'hgw'}
		{if $action == 'shippingPayment'}
			 <div class="method--input">
				{block name="hp_radio"}{/block}
			</div>		
		{else}
			{block name="hp_radio"}{/block}
		{/if}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_account_payment_error_messages'}
	{if $action == 'shippingPayment'}		
		{block name='hp_js'}{/block}
		{* workaround to get alert box w/o errors *}
		{if !isset($sErrorMessages)}
			{assign var='sErrorMessages' value=' '}
			{include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages visible=false}
		{else}
			{include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
		{/if}
	{else}
		{$smarty.block.parent}
	{/if}	
{/block}

{* change Form action *}
{block name='frontend_index_content'}
	{if ($Controller == 'account') && ($action == 'payment')}
		<div class="account--change-payment account--content register--content" data-register="true">
			{* Payment headline *}
			{block name="frontend_account_payment_headline"}
				<div class="account--welcome">
					<h1 class="panel--title">{s name="PaymentHeadline"}Zahlungsart &auml;ndern{/s}</h1>
				</div>
			{/block}			
			{block name='hp_js'}{/block}
			
			<div id="center" class="first register account--change-payment">
				<div class="panel has--border is--rounded">
					{* Error messages *}		
					{block name='frontend_account_payment_error_messages'}
						{include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
					{/block}
					
					{* Payment form *}
					<form name="frmRegister" method="post" action="{url controller=account action=savePayment sTarget=$sTarget}" class="payment" onsubmit="return valForm();" autocomplete="off">
						{include file='frontend/register/payment_fieldset.tpl' form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPaymentMeans}						
						<input type='hidden' name='sTarget' value='{$sTarget}' />						
						{block name="frontend_account_payment_action_buttons"}
						<div class="account--actions">
							{if $sTarget}
							<a class="btn is--secondary left" href="{url controller=$sTarget}" title="{s name='PaymentLinkBack' namespace='frontend/account/payment'}{/s}">
								{s name='PaymentLinkBack' namespace='frontend/account/payment'}{/s}
							</a>
							{/if}
							<input type="submit" value="{s name='PaymentLinkSend' namespace='frontend/account/payment'}{/s}" class="btn is--primary register--submit right" />
						</div>
						{/block}
					</form>
					<div class="space">&nbsp;</div>
				</div>
			</div>
		</div>
	{else}
		{$smarty.block.parent}<br />
	{/if}
{/block}

{* confirm Checkout Payment method Block *}
{block name='frontend_checkout_confirm_left_payment_method'}
	<strong class="payment--description">{$sUserData.additional.payment.description}</strong><br />
	{if !$sUserData.additional.payment.esdactive}
		<p class="payment--confirm-esd">{s name="ConfirmInfoInstantDownload" namespace="frontend/checkout/confirm_left"}{/s}</p>
	{/if}
	
	{if $swfActive}
		{$smarty.block.parent}
	{else}
		{if !$sRegisterFinished}
			{if !is_int($user.additional.payment.name|strpos:'heidelpay')}				
				{assign var='bar_at' value=$user.additional.payment.name|strpos:'_'}
				{assign var='pm' value=$user.additional.payment.name|substr:($bar_at+1)}
			{/if}
			{if $pm == 'pay'}{assign var='pm' value='va'}{/if}

			{if isset($regData.$pm)}
				<div class="test">
				<strong>{s name='hp_selectedPayData' namespace='frontend/register/hp_payment'}{/s}:</strong><br />				
					{if $pm == 'dd'}
						{$regData.$pm.kto}
					{elseif $pm == 'va'}
						{$regData.$pm.email}
					{else}
						{$regData.$pm.cardnr}
					{/if}
				</div>
			{/if}
		{/if}
	{/if}
{/block}