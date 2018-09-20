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
                <input type="hidden" value="true" id="easyHpr_preContract" disabled>
	    	</div>
	    </div>
    {/if}
    {if !empty($linkPrecontactInfos) && $heidelHpBrand == "SANTANDER_HP"}
        <div class="panel has--border dispatch-methods">
            <div class="panel--title primary is--underline">
                <h3 class="underline">Santander Tilgungsplan</h3>
            </div>
            <div class="panel--body is--wide">
                <a href="{$linkPrecontactInfos}" target="_blank">Vorvertragliche Informationen zum Ratenkauf hier abrufen</a>
                <input type="hidden" value="true" id="sanHps_preContract" disabled>
            </div>
        </div>
    {/if}
{/block}

{block name='frontend_checkout_payment_content'}<h1>TEST</h1>{/block}