{block name="frontend_checkout_finishs_transaction_number"}
	{block name="payment_fieldset"}{/block}
{/block}

{block name="payment_fieldset"}
	{if $smarty.session.Shopware.sOrderVariables->sTransactionumber != ''}
		<div class='hp_finish_text'>		
			{s name='FinishInfoTransaction' namespace='frontend/checkout/finish'}{/s}
			{$smarty.session.Shopware.sOrderVariables->sTransactionumber}
			{if $smarty.session.Shopware.sOrderVariables->prepaymentText != ''}
				<br/><br/>
				{$smarty.session.Shopware.sOrderVariables->prepaymentText}
			{/if}
		</div>
	{/if}
	
	<br /><br />
	<div class='hp_account_ident'>
		{if $smarty.session.Shopware.sOrderVariables->accountIdent != ''}	
			{s name='accountIdent' namespace='frontend/checkout/finish'}{/s}<br />
			{if $smarty.session.Shopware.sOrderVariables->identCreditorId != ''}
				{s name='identCreditorId' namespace='frontend/checkout/finish'}{/s}<br />
			{/if}
			<br />{s name='accountFunds' namespace='frontend/checkout/finish'}{/s}
		{/if}
	</div>
	
	{if $smarty.session.Shopware.sOrderVariables->payType == 'WT'}	
		<div class='hp_wallet'>
			{if $smarty.session.Shopware.sOrderVariables->sPayment['name'] == 'hgw_mpa'}
				{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
				{assign var='prePath' value="{link file=''}"}
				<p>
					<img style="width: 145px;" src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />
				</p>
			{/if}
			<p>
				{$smarty.session.Shopware.sOrderVariables->contactMail}<br/>
				<br/>
				{$smarty.session.Shopware.sOrderVariables->accountNr} | {$smarty.session.Shopware.sOrderVariables->accountBrand}<br/>
				{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$smarty.session.Shopware.sOrderVariables->accountExpMon} / {$smarty.session.Shopware.sOrderVariables->accountExpYear}
			</p>
		</div>
	{/if}
{/block}