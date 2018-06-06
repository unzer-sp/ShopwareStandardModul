{block name="hp_payment_hpr"}
	<img style="display:inline;" alt="easycredit Logo" src="https://static.easycredit.de/content/image/logo/ratenkauf_42_55.png" id="rk_easycredit_logo">
	<a rel="nofollow" style="color:#000000;" class="easycredit-tooltip" onclick="javascript:window.open('https://www.easycredit.de/Ratenkauf.htm','whatiseasycredit','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, ,left=0, top=0, width=800, height=600'); return false;" href="https://www.easycredit.de/Ratenkauf.htm">
		Was ist Ratenkauf by easyCredit?
	</a>
	<input type="hidden" name='ACTIVATEEASY' value='{$activeEasy}' />
	<div id="easyText">
		{if $activeEasy == "TRUE"}
			{if !empty($configOptInText)}
				<div>
					<p>Datenübermittlung Ratenkauf by easyCredit</p>
				</div>
				<div>
					<div>
						<input type="checkbox" id="hgw_cb_hpr" name="CONFIG.OPTIN.TEXT" value="TRUE" class="checkbox" required="required">*
						<span>{$configOptInText}</span>

					</div>
				</div>
			{/if}

		{else}
			<div class="EasyPermission">
				<p>Der Finanzierungsbetrag liegt außerhalb der zulässigen Beträge ({$HGW_EASYMINAMOUNT} - {$HGW_EASYMAXAMOUNT} EUR). </p>
			</div>
		{/if}
	</div>
{/block}