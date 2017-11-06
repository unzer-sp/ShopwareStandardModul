{block name="hp_payment_san"}
	<div class="{$grid} {$classname}" style='background-color: transparent;'>
		<!--<div class="newreg_{$pm}" style="width: 22rem;">-->
		<!--<div class="newreg_{$pm}" id="payType" style="width: 30rem;">-->
		<!--<img src="{$logoLink}" alt="Santander-Logo">-->
		<img src="https://www.santander.de/media/bilder/logos/logos_privatkunden/logo.gif" alt="Santander-Logo">
		<div class="newreg_{$pm}" id="payType">
			<div>
				<label>{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
                {if isset($salutation)}
                    {if ($salutation == "MR")}
						<select id="salutation" name="NAME.SALUTATION">
							<option value="MR" selected="selected">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
							<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
						</select><br />
                    {else}
						<select id="salutation" name="NAME.SALUTATION">
							<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
							<option value="MRS" selected="selected">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
						</select><br />
                    {/if}	<!-- salutation == mr -->
                {else}
					<select id="salutation" class="hgw_required" name="NAME.SALUTATION">
						<option value="-">{s name='hp_accSal_gender' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MR">{s name='hp_accSal_mr' namespace='frontend/register/hp_payment'}{/s}</option>
						<option value="MRS">{s name='hp_accSal_ms' namespace='frontend/register/hp_payment'}{/s}</option>
					</select><br />
                {/if} <!-- if isset salutation -->
                {if isset($accountHolder)}
					<input type="text" value="{$accountHolder}" disabled><br />
                {/if}

			</div>
			<br />
			<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />

            {*{if isset($birthdate)}*}
            {*{assign var=payment_data value=$birthdate}*}
            {*{html_select_date|utf8_encode time=$payment_data start_year='-18' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}*}
            {*{else}*}
            {if isset($birthdate)}
                {assign var=payment_data value=$birthdate}
                {html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
            {else}
                {html_select_date|utf8_encode start_year='-10' end_year='-100' display_days="false" reverse_years='true' day_value_format='%02d' field_order='DMY'}
            {/if}
            {if isset($birthdate)}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_san" value="{$birthdate}">
            {else}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_san" value="-">
            {/if}
            {if isset($optin)}
				<div>
					<p>
						<label for="hgw_adv_san">{s name='hp_sanAdvPermission' namespace='frontend/register/hp_payment'}{/s}:</label></br>
                        {if $checkOptin == "TRUE"}
							<input type="checkbox" id="hgw_adv_san" name="CUSTOMER.OPTIN" value="TRUE" class="checkbox" checked="checked">
                        {else}
							<input type="checkbox" id="hgw_adv_san" name="CUSTOMER.OPTIN" value="TRUE" class="checkbox">
                        {/if}
                        {* $optinText *}
						<strong>Ja, ich bin damit einverstanden, dass meine Daten an die Santander Consumer Bank AG („Santander“)
							weitergegeben werden. Die Santander darf diese Daten gerne dazu nutzen, um mich über Produkte der
							Santander zu informieren. Natürlich kann ich meine Einwilligung jederzeit mit Wirkung für die Zukunft
							widerrufen. Ausführliche Informationen zu dieser Einwilligung sowie die Möglichkeit zum Widerruf
							finde ich <!--<a href="{$optinLink}" target="_blank">hier</a>.</strong>--><a href="https://www.santander.de/applications/rechnungskauf/werbewiderspruch/" target="_blank">hier</a>.</strong>
						</br>

					</p>
					<label for="hgw_privacyPolicy">{s name='hp_sanPrivacyPolicy' namespace='frontend/register/hp_payment'}{/s}*:</label>
					<!--<div id="hgw_privacyPolicy" style="height:160px;width:30 rem;overflow:auto;">-->
					<!--<div id="hgw_privacyPolicy">-->
					<p id="hgw_ParaPrivacyPolicy">
                        {if $checkPrivacyPolicy == "TRUE" }
							<input type="checkbox" id="hgw_privacyPolicy" class="hgw_required" name="CUSTOMER.ACCEPT_PRIVACY_POLICY" value="TRUE" class="checkbox" checked="checked">
                        {else}
							<input type="checkbox" id="hgw_privacyPolicy" class="hgw_required" name="CUSTOMER.ACCEPT_PRIVACY_POLICY" value="TRUE" class="checkbox">
                        {/if}

                        {* $privacy_policy_text *}
						<strong>Ich willige in die Übermittlung meiner personenbezogenen Daten an die Santander Consumer Bank AG
							gemäß den näheren Bestimmungen des beigefügten <a href="https://www.santander.de/applications/rechnungskauf/datenschutzbestimmungen" target="_blank">Einwilligungserklärungstextes</a> sowie an die darin
							genannten Auskunfteien und in die Durchführung einer automatisierten Entscheidung ein.</strong>
						</br>
						Nähere Informationen finden Sie in den <a href="https://www.santander.de/applications/rechnungskauf/datenschutzbestimmungen" target="_blank">Datenschutzhinweisen</a> der Santander für den Rechnungs-/Ratenkauf.
						<!--<a href="{$privacy_policy_link}" target="_blank">Weitere Informationen zum Datenschutz</a>-->
					</p>
					<!--</div>-->

				</div>
            {/if}
			<p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
		</div>
	</div>
{/block}
