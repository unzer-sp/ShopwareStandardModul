{block name="hp_payment_papg"}
	{*{debug}*}
	<div class="{$grid} {$classname}" style='background-color: transparent;'>
		<div class="newreg_{$pm}" id="payType" style="width: 30rem;">
			<div>
                <label class="is--strong"><label class="is--strong">{s name='B2bHeaderFirm' namespace='frontend/payment_heidelpay/gateway'}{/s}</label><br />
				</label><br />

				{* Company Name *}
				<label for="heidelb2bCompanyName">{s name='B2bCompanyName' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				<input id="heidelb2bCompanyName" type="text" value="{$b2bCompanyName}" name="COMPANY.COMPANYNAME" required><br />

				{* Company adress pobox *}
				<label for="heidelb2bCompanyPobox">{s name='B2bCompanyPobox' namespace='frontend/payment_heidelpay/gateway'}{/s}:</label></br>
				<input id="heidelb2bCompanyPobox" type="text" value="" name="COMPANY.LOCATION.POBOX"><br />

				{* Company adress street *}
				<label for="heidelb2bCompanyStreet">{s name='B2bCompanyStreet' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				<input id="heidelb2bCompanyStreet" type="text" value="{$b2bCompanyStreet}" name="COMPANY.LOCATION.STREET" required><br />

				{* Company adress zip *}
				<label for="heidelb2bCompanyZip">{s name='B2bCompanyZip' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				<input id="heidelb2bCompanyZip" type="text" value="{$b2bCompanyZip}" name="COMPANY.LOCATION.ZIP" required><br />

				{* Company adress city *}
				<label for="heidelb2bCompanyCity">{s name='B2bCompanyCity' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				<input id="heidelb2bCompanyCity" type="text" value="{$b2bCompanyCity}" name="COMPANY.LOCATION.CITY" required><br />

				{* Dropdown Company adress country *}
				<label for="heidelb2bCompanyCountry">{s name='B2bCompanyCountry' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				{html_options id="heidelb2bCompanyCountry" options=$companyCountry selected="DE" name="COMPANY.LOCATION.COUNTRY"}<br />

				{* Dropdown Company commercial sector *}
				<label for="heidelb2bCompanyIndustry">{s name='B2bCompanyIndustry' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				{html_options id="heidelb2bCompanyIndustry" options=$companyIndustry selected="OTHERS_COMMERCIAL_SECTORS" name="COMPANY.COMMERCIALSECTOR"}<br />

				{* Options company registered *}
				<label for="heidelB2bCompanyRegistered">{s name='B2bCompanyRegistered' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
				{html_radios id="heidelB2bCompanyRegistered" values=$B2bCompanyRegisteredVal output=$B2bCompanyRegisteredOut selected="REGISTERED" separator=' ' name="COMPANY.REGISTRATIONTYPE"}</br>

				<div class="heidelB2bRegistered" style="margin-top: 10px">
					{* Company CommercialRegisterNumber *}
					<label for="heidelb2bCompanyRegisterNr">{s name='B2bCompanyRegisterNr' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
					<input id="heidelb2bCompanyRegisterNr" type="text" value="{$b2bCompanyRegisterNr}" name="COMPANY.COMMERCIALREGISTERNUMBER" required><br />

					{* Company VatId /UstId *}
					<label for="heidelb2bCompanyUstNr">{s name='B2bCompanyUstNr' namespace='frontend/payment_heidelpay/gateway'}{/s}:</label></br>
					<input id="heidelb2bCompanyUstNr" type="text" value="{$B2bCompanyUstNr}" name="COMPANY.VATID"><br />

                    <p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
				</div>
				<div class="heidelB2bNotRegistered" style="margin-top: 10px; display: none">
					<label class="is--strong">{s name='B2bHeaderPersonal' namespace='frontend/payment_heidelpay/gateway'}{/s}</label><br />

                    {* Dropdown Executive function *}
                    <label for="heidelb2bExecutiveFunction">{s name='B2bFunction' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
                    {*{html_options id="heidelb2bExecutiveFunction" options=$heidelB2bFunction selected="OWNER" name="COMPANY.EXECUTIVE.1.FUNCTION" required="required"}<br />*}
					<input id="heidelb2bExecutiveFunction" type="text" value="OWNER" name="COMPANY.EXECUTIVE.1.FUNCTION" required disabled><br />

                    {* Company Executive Salutation *}
					<label for="B2Bsalutation">{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}:</label><br />
					{html_options id="B2Bsalutation" options=$B2Bsalutation selected="{$b2bSelectedSalutation}" name="COMPANY.EXECUTIVE.1.SALUTATION"}<br />

					{* Company Executive Preame *}
					<label for="heidelb2bPreName">{s name='B2bPreName' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
					<input id="heidelb2bPreName" type="text" value="{$b2bCompanyPreName}" name="COMPANY.EXECUTIVE.1.GIVEN" required><br />

					{* Company Executive Surname *}
					<label for="heidelb2bLastName">{s name='B2bLastName' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
					<input id="heidelb2bLastName" type="text" value="{$b2bCompanySurName}" name="COMPANY.EXECUTIVE.1.FAMILY" required><br />

					{* Company Executive Birthdate *}
					<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />
					{if isset($b2bBirthdate) && $b2bBirthdate != '0000-00-00'}
						{html_select_date|utf8_encode time=$b2bBirthdate start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}</br>
					{else}
						{html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY' all_empty="bitte angeben" required="required"}</br>
					{/if}
					<input type="hidden" name="COMPANY.EXECUTIVE.1.BIRTHDATE" id="birthdate_ivb2b" value="{$b2bBirthdate}">

					{* Company Executive Email *}
					<label for="heidelb2bEmail">{s name='B2bEmail' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
					<input id="heidelb2bEmail" type="text" value="{$b2bCompanyEmail}" name="COMPANY.EXECUTIVE.1.EMAIL"><br />

					{* Company Executive Phone *}
					<label for="heidelb2bExePhone">{s name='B2bExePhone' namespace='frontend/payment_heidelpay/gateway'}{/s}:</label></br>
					<input id="heidelb2bExePhone" type="text" value="{$b2bCompanyExePhone}" name="COMPANY.EXECUTIVE.1.PHONE"><br />

                    {* Company Executive Street *}
                    <label for="heidelb2bExeStreet">{s name='B2bExeStreet' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
                    <input id="heidelb2bExeStreet" type="text" value="{$b2bCompanyExeStreet}" name="COMPANY.EXECUTIVE.1.HOMESTREET"><br />

                    {* Company Executive Zip *}
                    <label for="heidelb2bExeZip">{s name='B2bExeZip' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
                    <input id="heidelb2bExeZip" type="text" value="{$b2bCompanyExeZip}" name="COMPANY.EXECUTIVE.1.HOME.ZIP"><br />

                    {* Company Executive Zip *}
                    <label for="heidelb2bExeCity">{s name='B2bExeCity' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
                    <input id="heidelb2bExeCity" type="text" value="{$b2bCompanyExeCity}" name="COMPANY.EXECUTIVE.1.HOME.CITY"><br />

                    {* Company Executive Country *}
                    <label for="heidelb2bExeCountry">{s name='B2bExeCountry' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>
					{html_options id="heidelb2bExeCountry" options=$b2bCompanyExeCountry selected="DE" name="COMPANY.EXECUTIVE.1.HOME.COUNTRY"}<br />

                    <p class="description">{s name='PaymentDebitInfoFields' namespace='frontend/plugins/payment/debit'}{/s}</p>
			</div>
				{* Company UstId *}
				{*<label for="heidelb2bCompanyUstNr">{s name='B2bCompanyUstNr' namespace='frontend/payment_heidelpay/gateway'}{/s}*:</label></br>*}
				{*<input id="heidelb2bCompanyUstNr" type="text" value="{$B2bCompanyUstNr}" name=""><br />*}






			</div>
			</br>
			</br>
			</br>
			<br />
		<h1>Testbereich</h1>
			{*<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />*}
			{*{assign var=payment_data value=$birthdate_papg}*}
			{*{if isset($birthdate_papg)}*}
				{*{html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}*}
			{*{else}*}
				{*{html_select_date|utf8_encode time=$payment_data start_year='-14' end_year='-100' reverse_years='true'*}
				{*day_value_format='%02d' field_order='DMY'*}
				{*day_empty="{s name='hp_valueDay' namespace='frontend/register/hp_payment'}{/s}"*}
				{*month_empty="{s name='hp_valueMonth' namespace='frontend/register/hp_payment'}{/s}"*}
				{*year_empty="{s name='hp_valueYear' namespace='frontend/register/hp_payment'}{/s}"}*}
			{*{/if}*}

		</div>
	</div>
{/block}