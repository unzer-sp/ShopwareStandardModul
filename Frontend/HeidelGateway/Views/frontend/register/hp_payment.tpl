{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_header_javascript" append}
	{if isset($pluginPath)}	
		{if $swfActive}
			<script type='text/javascript' src='{$pluginPath}/Views/frontend/_resources/javascript/valPaymentSWF.js'></script>
		{else}
			<script type='text/javascript' src='{$pluginPath}/Views/frontend/_resources/javascript/valPayment.js'></script>
		{/if}
		<script type='text/javascript' src='{$pluginPath}/Views/frontend/_resources/javascript/hpf_script.js'></script>
	{/if}
{/block}

{block name="frontend_index_header_css_screen" append}
	{if isset($pluginPath)}	
		<link type="text/css" media="all" rel="stylesheet" href="{$pluginPath}/Views/frontend/_resources/styles/hpf_style.css" />
	{/if}
{/block}

{block name="hp_payment"}
	{assign var='path' value='frontend/register/hp_payment'}
	{assign var='bar_at' value=$payment_mean.name|strpos:'_'}
	{assign var='pm' value=$payment_mean.name|substr:($bar_at+1)}
	{if $pm == 'pay'}{assign var='pm' value='va'}{/if}

	{if $payment_mean.name == "hgw_cc" && $heidel_bm_cc && !$sRegisterFinished && ($formUrl.$pm != '')}
		{if !isset($regData.$pm)} {block name="hp_toggle"}{/block}	{else} {block name="hp_untoggle"}{/block} {/if}
			{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_cc.tpl"}
		</div>
	{elseif $payment_mean.name == "hgw_dc" && $heidel_bm_dc && !$sRegisterFinished && ($formUrl.$pm != '')}
		{if !isset($regData.$pm)} {block name="hp_toggle"}{/block}	{else} {block name="hp_untoggle"}{/block} {/if}
			{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_dc.tpl"}
		</div>
	{elseif $payment_mean.name == "hgw_dd" && $heidel_bm_dd && !$sRegisterFinished && ($formUrl.$pm != '')}
		{if !isset($regData.$pm)} {block name="hp_toggle"}{/block}	{else} {block name="hp_untoggle"}{/block} {/if}
			{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_dd.tpl"}
		</div>
	{elseif $payment_mean.name == "hgw_pay" && $heidel_bm_va && !$sRegisterFinished && ($formUrl.$pm != '')}
		{if !isset($regData.$pm)} {block name="hp_toggle"}{/block}	{else} {block name="hp_untoggle"}{/block} {/if}
			{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_va.tpl" heidel_bm_va=$heidel_bm_va pm='va' classname='debit hgw_va'}
		</div>

	{elseif $payment_mean.name == "hgw_san"}
		{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_san.tpl" pm='san'}
	{elseif $payment_mean.name == "hgw_ivpd"}
		{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_ivpd.tpl" pm='ivpd'}
	{elseif $payment_mean.name == "hgw_hpr"}
		{include file="{$tPath|substr:1}/Views/frontend/register/hp_payment_hpr.tpl" pm='hpr'}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="hp_toggle"}
	<span class="toggle_form" style="cursor: pointer;">{s name='hp_enter' namespace='frontend/register/hp_payment'}{/s}</span>
	<div class="show_form" style="display: none;">	
{/block}

{block name="hp_untoggle"}
	<span class="toggle_form" style="cursor: pointer; display: none;">{s name='hp_enter' namespace='frontend/register/hp_payment'}{/s}</span>
	<div class="show_form">	
{/block}

{block name="hp_radio"}
	<div class="grid_5 first">
	<input type="radio" name="register[payment]" class="radio {if $sRegisterFinished}auto_submit {/if}{$payment_mean.name}" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}" {if $payment_mean.id eq $form_data.payment or ($payment_mean.id == $sPayment.id)} checked="checked"{/if} /><label class="description" for="payment_mean{$payment_mean.id}">{$payment_mean.description}</label>
	</div>
{/block}

{block name='frontend_register_payment_fieldset_template'}
	{if $payment_mean.name|substr:0:$bar_at == 'hgw'}
		{assign var="classname" value="debit {$payment_mean.name}"}
		<div class="payment_logo_{$payment_mean.name}"></div>
		{if "frontend/plugins/payment/`$payment_mean.template`"|template_exists}
			<div class="space">&nbsp;</div>
			<div class="grid_8 bankdata">
				{block name="hp_payment"}{/block}
			</div>
		{/if}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_register_payment_fieldset_description'}
	{if $payment_mean.name == "hgw_mpa"}
	<div class="grid_10 last">
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
		<div class="payment_logo_{$payment_mean.name}"></div>
			<div class="space">&nbsp;</div>
			<div class="grid_10 bankdata">
				{block name="hp_payment"}{/block}
			</div>
	{else}
		{$smarty.block.parent}
	{/if}	
{/block}

{block name="frontend_checkout_payment_fieldset_input_radio"}
	{assign var='bar_at' value=$payment_mean.name|strpos:'_'}
	{if $payment_mean.name|substr:0:$bar_at == 'hgw'}	
		{block name="hp_radio"}{/block}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{* change Form action *}
{block name='frontend_index_content'}
	{if ($Controller == 'account') && ($action == 'payment')}
	<script type='text/javascript'>
		//define formUrl to make it useable in external JS
		var formUrl = {$formUrl|@json_encode};
		$(document).ready(function(){
			//add error div
			if(jQuery('#center .error').length < 1){
				jQuery('#center').prepend('<div class="error" style="display: none;"><h2>{s name='RegisterErrorHeadline' namespace='frontend/register/error_message'}{/s}</h2><ul></ul></div>');
			}
		});
	</script>

	{if $isMobile || $swfActive}<link rel="stylesheet" media="all" type="text/css" href="{$pluginPath}/Views/frontend/register/mobile.css" />{/if}

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
			<div class='msg_salut'>{s name='ErrorSalut' namespace='frontend/register/hp_payment'}{/s}</div>
			<div class='msg_cb'>{s name='ErrorCb' namespace='frontend/register/hp_payment'}{/s}</div>
			<div class='msg_phone'>{s name='ErrorPhone' namespace='frontend/register/hp_payment'}{/s}</div>
		</div>
	{/block}
	
	<div id="center" class="grid_16 first register">	
		<h1>{se name='PaymentHeadline'}Zahlungsart &auml;ndern{/se}</h1>		
	
		{* Error messages *}
		{block name='frontend_account_payment_error_messages'}
			{include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
		{/block}
		
		{* Payment form *}
		<form name="frmRegister" method="post" action="{url controller=account action=savePayment sTarget=$sTarget}" class="payment" onsubmit="return valForm();" autocomplete="off">		
			{include file='frontend/register/payment_fieldset.tpl' form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPaymentMeans}
			<input type='hidden' name='sTarget' value='{$sTarget}' />
			{block name="frontend_account_payment_action_buttons"}
			<div class="actions">
				{if $sTarget}
				<a class="button-left large left" href="{url controller=$sTarget}" title="{s name='PaymentLinkBack' namespace='frontend/account/payment'}{/s}">
					{s name='PaymentLinkBack' namespace='frontend/account/payment'}{/s}
				</a>
				{/if}
				<input type="submit" value="{s name='PaymentLinkSend' namespace='frontend/account/payment'}{/s}" class="button-right large right" />
			</div>
			{/block}
		</form>
		<div class="space">&nbsp;</div>
	</div>
	{else}
		{$smarty.block.parent}<br />
	{/if}
{/block}

{* confirm Checkout Payment method Block *}
{block name='frontend_checkout_confirm_left_payment_method'}
	{if $swfActive}
		{$smarty.block.parent}
	{else}
		{if !$sRegisterFinished}
			<div class="payment-display">
				<h3 class="underline">{s name='ConfirmHeaderPayment' namespace="frontend/checkout/confirm_left"}{/s}</h3>
				<p>
					<strong>{$sUserData.additional.payment.description}</strong><br />
					{if !$sUserData.additional.payment.esdactive}
						{s name='ConfirmInfoInstantDownload' namespace="frontend/checkout/confirm_left"}{/s}
					{/if}
				</p>
				<p>
					{if !is_int($user.additional.payment.name|strpos:'heidelpay')}				
						{assign var='bar_at' value=$user.additional.payment.name|strpos:'_'}
						{assign var='pm' value=$user.additional.payment.name|substr:($bar_at+1)}
					{/if}
					{if $pm == 'pay'}{assign var='pm' value='va'}{/if}

					{if isset($regData.$pm)}
						<div class="space">&nbsp;</div>
						<strong>{s name='hp_selectedPayData' namespace='frontend/register/hp_payment'}{/s}:</strong><br />
						{if $pm == 'dd'}
							{$regData.$pm.kto}
						{elseif $pm == 'va'}
							{$regData.$pm.email}
						{else}
							{$regData.$pm.cardnr}
						{/if}					
					{/if}
				</p>			
				{* Action buttons *}
				<div class="actions">
					<a href="{url controller=account action=payment sTarget=checkout}" class="button-middle small">
						{s name='ConfirmLinkChangePayment' namespace="frontend/checkout/confirm_left"}{/s}
					</a>
				</div>
			</div>
		{/if}
	{/if}
{/block}