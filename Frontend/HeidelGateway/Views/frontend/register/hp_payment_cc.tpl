{block name="hp_payment_cc"}
	<div class="{$grid} {$classname}" style='background-color: transparent; border: 0;'>
		{if isset($regData.$pm)}
			{assign var="hasReg{$pm}" value=true}
			<div class="reuse_{$pm}" {if {$hasReg{$pm}}} style="display: block;" {/if}>
				{s name='hp_reuse' namespace='frontend/register/hp_payment'}{/s}
				<table>
					<tr><td>{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.owner}</td></tr>
					<tr><td>{s name='hp_cardBrand' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.brand}</td></tr>
					<tr><td>{s name='hp_cardNumber' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.cardnr}</td></tr>
					<tr><td>{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}:</td><td>{$regData.$pm.expMonth} / {$regData.$pm.expYear}</td></tr>
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