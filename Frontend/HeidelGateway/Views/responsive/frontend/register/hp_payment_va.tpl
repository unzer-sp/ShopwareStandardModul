{block name="hp_payment_va"}
	<div class="{$classname}" style='background-color: transparent; border: 0;'>
		{if isset($regData.$pm)}
			{assign var="hasReg{$pm}" value=true}			
			<div class="reuse_{$pm}" {if {$hasReg{$pm}}} style="display: block;" {/if}>
				{s name='hp_reuse' namespace='frontend/register/hp_payment'}{/s}
				<table {if $isMobile} style="table-layout: fixed;"{else}style="table-layout: inherit;"{/if}>
					<tr><td {if $isMobile} style="word-wrap: break-word;"{/if}>{s name='hp_mail' namespace='frontend/register/hp_payment'}{/s}:</td><td {if $isMobile} style="word-wrap: break-word;"{/if}>{$regData.$pm.email}</td></tr>
				</table>
			</div>
		{/if}

		<div class="newreg_{$pm}" {if {$hasReg{$pm}}} style="display: none;" {/if}>

			<label>{s name='hp_mail' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
			<input type="text" class="text" value="{* $form_data.hpcc_number|escape *}" id="contactMail" name="CONTACT.EMAIL"><br />

			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
			{if $heidel_bm_va}
				<div class="space">&nbsp;</div>
				<p class="description">{s name='hp_paypalInfo' namespace='frontend/register/hp_payment'}{/s}</p>
			{/if}
		</div>
		
		{if isset($regData.$pm)}
		<div class="space">&nbsp;</div>
		<div><input class="reues_{$pm}" type='checkbox'>{s name='hp_reenter' namespace='frontend/register/hp_payment'}{/s}</div>{/if}
	</div>
{/block}