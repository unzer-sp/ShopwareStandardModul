                {* remove action buttons *}
				{*{block name="frontend_checkout_confirm_left_shipping_address_actions"}{/block}*}

                {block name='frontend_checkout_confirm_left_payment_method'}
					{$smarty.block.parent}
                    {if !$sRegisterFinished}
						{if isset($regData)}
							{if $sUserData.additional.payment.name == 'hgw_mpa'}
								<p class="payment--method-info">
									{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
									{assign var='prePath' value="{link file=''}"}
									<img src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />


								{$regData.email}<br/>
								<br/>
								{$regData.cardnr}<br/>
								{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$regData.expMonth} / {$regData.expYear}
								</p>
								{* remove shipping info *}
								{block name="frontend_checkout_confirm_left_shipping_method"}{/block}
							{/if}

							{if ($sUserData.additional.payment.name == 'hgw_cc') || ($sUserData.additional.payment.name == 'hgw_dc')}
								{if !empty($regData)}
									{*<p class="payment--method-info">*}
										{*<strong class="payment--title">{s name="ConfirmInfoPaymentMethod" namespace="frontend/checkout/confirm"}{/s}</strong>*}
										{*<span class="payment--description">{$sUserData.additional.payment.description}</span>*}
									{*</p>*}
									{*{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}: {$regData.owner}<br/>*}
									{s name='hp_cardNumber' namespace='frontend/register/hp_payment'}{/s}: {$regData.cardnr}<br/>
									{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$regData.expMonth} / {$regData.expYear}
								{/if}

							{/if}

							{if ($sUserData.additional.payment.name == 'hgw_dd')}
								{if !empty($regData)}
									{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}: {$regData.owner}<br/>
									{s name='hp_iban' namespace='frontend/register/hp_payment'}{/s}: {$regData.kto}<br/>
								{/if}
							{/if}

						{else}
							{$smarty.block.parent}
						{/if}
                    {/if}
                {/block}
