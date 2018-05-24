{block name="hp_payment_hps"}
{debug}
	<div class="newreg_hps" style="margin-top: 10px">
		<div id="hps_genderData">
			<div>
				<label for="sanGenders">{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label></br>
				{html_radios id="sanGenders" name='NAME.SALUTATION' values=$sanGenderVal output=$sanGenderOut selected=$genderShop_HpSan separator=' '}</br>
			</div>
			<div>
				<input type="text" value="{$accountHolder_HpSan}" disabled><br />
			</div>
		</div>
		<div id="hps_customerBirthdate" style="margin-top: 10px">
			<div>
				<h3>Übermitteltes GebDat: {$birthdate_hps}</h3>
				<label>{s name='hp_RegisterLabelBirthday' namespace='frontend/register/hp_payment'}{/s}*:</label><br />

				{if isset($birthdate_hps) && $birthdate_hps != '0000-00-00'}
					{html_select_date|utf8_encode time=$birthdate_hps start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
				{else}
					{html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY' all_empty="bitte angeben"}
				{/if}
				<input type="hidden" name="NAME.BIRTHDATE" id="birthdate_san" value="{$birthdate_hps}">
			</div>

		</div>
	</div>
	
{/block}