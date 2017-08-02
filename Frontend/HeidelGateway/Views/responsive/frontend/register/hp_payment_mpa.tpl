{block name="hp_payment_mpa"}
	{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
	{assign var='prePath' value="{link file=''}"}
	
	{if ($lang == 'de') || ($lang == 'at') || ($lang == 'ch') }
		{assign var='langCode' value="de/DE"}
	{elseif ($lang == 'fr')}
		{assign var='langCode' value="fr/FR"}
	{else}
		{assign var='langCode' value="en/US"}
	{/if}

	<a href="https://www.mastercard.com/mc_us/wallet/learnmore/{$langCode}" target="_blank" rel="nofollow">
		<img style="width: 145px;" src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />
	</a>
{/block}