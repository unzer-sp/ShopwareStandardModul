{block name="hp_payment_cc"}
	<div class="{$classname}" style="background-color: transparent; ">
		{if isset($regData.$pm)}
			{assign var="hasReg{$pm}" value=true}
			<div class="reuse_{$pm}" {if {$hasReg{$pm}}} style="display: block;" {/if}>
				{s name='hp_reuse' namespace='frontend/register/hp_payment'}{/s}
				<table {if $isMobile} style="table-layout: fixed;"{else}style="table-layout: inherit;"{/if}>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.owner}</td></tr>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_cardBrand' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.brand}</td></tr>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_cardNumber' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.cardnr}</td></tr>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.expMonth} / {$regData.$pm.expYear}</td></tr>
				</table>
			</div>
		{/if}

		<div class="newreg_{$pm}" {if {$hasReg{$pm}}} style="display: none;" {/if}>
			{if $frame.$pm}
				<iframe id="hp_frame_{$pm}" src="{$formUrl.$pm}">your browser doesn't support iframes</iframe>
			{/if}
		</div>
		
		{if isset($regData.$pm)}
		<div class="space">&nbsp;</div>
		<div><input class="reues_{$pm}" type='checkbox'>{s name='hp_reenter' namespace='frontend/register/hp_payment'}{/s}</div>{/if}
	</div>
{/block}