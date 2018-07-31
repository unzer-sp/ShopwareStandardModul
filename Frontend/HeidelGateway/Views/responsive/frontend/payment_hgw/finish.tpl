{if $smarty.is_block_defined.frontend_checkout_finish_information_wrapper}
	{block name='frontend_checkout_finish_information_wrapper' append}
		{if $smarty.session.Shopware.sOrderVariables->accountIdent != ''}
			<div class='hp_account_ident finish--table'>
				<div class='panel--body is--rounded'>
					{s name='accountIdent' namespace='frontend/checkout/finish'}{/s}<br />
					{if $smarty.session.Shopware.sOrderVariables->identCreditorId != ''}
						{s name='identCreditorId' namespace='frontend/checkout/finish'}{/s}<br />
					{/if}
					<br />{s name='accountFunds' namespace='frontend/checkout/finish'}{/s}
				</div>
			</div>
		{/if}

		{if $smarty.session.Shopware.sOrderVariables->prepaymentText != ''}
			<div class='hp_account_ident finish--table'>
				<div class='panel--body is--rounded'>
					{$smarty.session.Shopware.sOrderVariables->prepaymentText}
				</div>
			</div>
		{/if}

		{if $smarty.session.Shopware.sOrderVariables->payType == 'WT'}
			<div class='hp_wallet finish--table'>
				<div class='panel--body is--rounded'>
					<strong>{s name='AccountHeaderPayment' namespace='frontend/account/index'}{/s}:</strong><br />
					{if $smarty.session.Shopware.sOrderVariables->sPayment['name'] == 'hgw_mpa'}
						{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
						{assign var='prePath' value="{link file=''}"}
						<img style="width: 145px;" src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />
					{/if}

					{$smarty.session.Shopware.sOrderVariables->contactMail}<br/>
					<br/>
					{$smarty.session.Shopware.sOrderVariables->accountNr} | {$smarty.session.Shopware.sOrderVariables->accountBrand}<br/>
					{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$smarty.session.Shopware.sOrderVariables->accountExpMon} / {$smarty.session.Shopware.sOrderVariables->accountExpYear}
				</div>
			</div>
		{/if}
	{/block}
{else}
	{block name='frontend_checkout_finish_info' append}
		{if $smarty.session.Shopware.sOrderVariables->accountIdent != ''}
			<div class='hp_account_ident finish--table'>
				<div class='panel--body is--rounded'>
					{s name='accountIdent' namespace='frontend/checkout/finish'}{/s}<br />
					{if $smarty.session.Shopware.sOrderVariables->identCreditorId != ''}
						{s name='identCreditorId' namespace='frontend/checkout/finish'}{/s}<br />
					{/if}
					<br />{s name='accountFunds' namespace='frontend/checkout/finish'}{/s}
				</div>
			</div>
		{/if}

		{if $smarty.session.Shopware.sOrderVariables->prepaymentText != ''}
			<div class='hp_account_ident finish--table'>
				<div class='panel--body is--rounded'>
					{$smarty.session.Shopware.sOrderVariables->prepaymentText}
				</div>
			</div>
		{/if}

		{if $smarty.session.Shopware.sOrderVariables->payType == 'WT'}
			<div class='hp_wallet finish--table'>
				<div class='panel--body is--rounded'>
					<strong>{s name='AccountHeaderPayment' namespace='frontend/account/index'}{/s}:</strong><br />
					{if $smarty.session.Shopware.sOrderVariables->sPayment['name'] == 'hgw_mpa'}
						{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
						{assign var='prePath' value="{link file=''}"}
						<img style="width: 145px;" src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />
					{/if}

					{$smarty.session.Shopware.sOrderVariables->contactMail}<br/>
					<br/>
					{$smarty.session.Shopware.sOrderVariables->accountNr} | {$smarty.session.Shopware.sOrderVariables->accountBrand}<br/>
					{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$smarty.session.Shopware.sOrderVariables->accountExpMon} / {$smarty.session.Shopware.sOrderVariables->accountExpYear}
				</div>
			</div>
		{/if}
	{/block}
{/if}