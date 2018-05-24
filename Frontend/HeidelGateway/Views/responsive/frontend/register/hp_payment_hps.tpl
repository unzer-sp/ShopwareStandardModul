{block name="hp_payment_hps"}
{debug}
	<div>
		<div>
			<label for="sanGenders">{s name='hp_accSalutation' namespace='frontend/register/hp_payment'}{/s}*:</label></br>
			{html_radios id="sanGenders" name='NAME.SALUTATION' values=$sanGenderVal output=$sanGenderOut selected=$genderShop_HpSan separator=' '}</br>
		</div>
		<div>
			<input type="text" value="{$accountHolder_HpSan}" disabled><br />
		</div>
		<div>
			{html_select_date|utf8_encode time=$payment_data start_year='-10' end_year='-100' reverse_years='true' day_value_format='%02d' field_order='DMY'}
		</div>
	</div>
	
{/block}