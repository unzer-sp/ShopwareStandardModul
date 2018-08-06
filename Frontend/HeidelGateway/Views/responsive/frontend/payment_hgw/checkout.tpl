{extends file='parent:frontend/checkout/confirm.tpl'}
{block name='frontend_checkout_confirm_tos_panel' append}
	{if $heidelHpBrand == "EASYCREDIDIT"}
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
	{elseif $heidelHpBrand == "SANTANDER_HP"}

		{if !empty($linkPrecontactInfos)}
			<div class="panel has--border dispatch-methods">
				<div class="panel--title primary is--underline">
					<h3 class="underline">Vorvertragliche Informationen:</h3>
				</div>
				<div class="panel--body is--wide">
					<a href="{$linkPrecontactInfos}" target="_blank">Vorvertragliche Informationen zum Ratenkauf hier abrufen</a>
				</div>
			</div>
		{/if}
	{/if}
{/block}
