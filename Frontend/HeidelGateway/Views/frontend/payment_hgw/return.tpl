{extends file='frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' append}
        {$sBreadcrumb = [['name'=>"{s name='PaymentTitle' namespace='frontend/payment_heidelpay/recurring'}{/s}"]]}
{/block}

{* Main content *}
{block name='frontend_index_content'}
<div id="center" class="grid_13">

{if !empty($PaypalResponse.ACK) && $PaypalResponse.ACK == 'Failure'
  && ($PaypalConfig.paypalSandbox || $PaypalConfig.paypalErrorMode)}
		<h2>{s name='PaymentError' namespace='frontend/payment_heidelpay/error'}{/s}</h2>
    {$i=0}{while isset($PaypalResponse["L_LONGMESSAGE{$i}"])}
        <h3>[{$PaypalResponse["L_ERRORCODE{$i}"]}] - {$PaypalResponse["L_SHORTMESSAGE{$i}"]|escape|nl2br} {$PaypalResponse["L_LONGMESSAGE{$i}"]|escape|nl2br}</h3>
    {$i=$i+1}{/while}
{else}
	<h2>{s name='PaymentError' namespace='frontend/payment_heidelpay/error'}{/s}</h2>
{/if}
<br />

<div class="actions">
		<a class="button-left large left" href="{url controller=checkout action=cart}" title="{s name='basket' namespace='frontend/payment_heidelpay/fail'}{/s}">
			{s name='basket' namespace='frontend/payment_heidelpay/fail'}{/s}
		</a>	
        <a class="button-right large right" href="{url controller=account action=payment sTarget=checkout sChange=1}" title="{s name=PaymentLinkChange namespace='frontend/payment_heidelpay/recurring'}{/s}">
			{s name=PaymentLinkChange namespace='frontend/payment_heidelpay/recurring'}{/s}
        </a>
</div>

</div>
{/block}

{block name='frontend_index_actions'}{/block}
