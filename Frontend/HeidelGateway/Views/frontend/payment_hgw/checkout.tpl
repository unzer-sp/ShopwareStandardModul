{extends file='parent:frontend/checkout/confirm.tpl'}

{block name='frontend_checkout_confirm_shipping' append}
	{if !empty($amortisationText)}
	    <div class="panel has--border dispatch-methods">
	    	<div class="panel--title primary is--underline">
	    		<h3 class="underline">easyCredit Tilgungsplan</h3>
	    	</div>
	    	<div class="panel--body is--wide">
		    	{$amortisationText}
		    	</br>
		    	</br>
		    	<a href="{$linkPrecontactInfos}" target="_blank">Vorvertragliche Informationen zum Ratenkauf hier abrufen</a>
	    	</div>
	    </div>
    {/if}
{/block}
