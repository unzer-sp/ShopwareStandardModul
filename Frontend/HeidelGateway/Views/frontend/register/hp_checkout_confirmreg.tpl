                {* remove action buttons *}
				{block name="frontend_checkout_confirm_left_shipping_address_actions"}{/block}

                {block name='frontend_checkout_confirm_left_payment_method'}

					{if !$sRegisterFinished}
						{if isset($regData)}
							{if ($sUserData.additional.payment.name == 'hgw_cc') || ($sUserData.additional.payment.name == 'hgw_dc')}
								{if !empty($regData)}
									{*<p class="payment--method-info">*}
										{*<strong class="payment--title">{s name="ConfirmInfoPaymentMethod" namespace="frontend/checkout/confirm"}{/s}</strong>*}
										{*<span class="payment--description">{$sUserData.additional.payment.description}</span>*}
									{*</p>*}


									<h3 class="underline">{s name="ConfirmHeaderPayment" namespace='frontend/checkout/confirm_left'}{/s}</h3>
									<div class="payment_method">
										<div class="inner_container">
											<p>
												<strong>{$sUserData.additional.payment.description}</strong><br />

												{if !$sUserData.additional.payment.esdactive}
													{s name="ConfirmInfoInstantDownload" namespace='frontend/checkout/confirm_left'}{/s}
												{/if}
											</p>
											<p>
												{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}: {$regData.owner}<br/>
												{s name='hp_cardNumber' namespace='frontend/register/hp_payment'}{/s}: {$regData.cardnr}<br/>
												{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$regData.expMonth} / {$regData.expYear}<br/>
												<a href="{url controller=account action=payment sTarget=checkout}" class="button-middle small">
													{s name="ConfirmLinkChangePayment" namespace='frontend/checkout/confirm_left'}{/s}
												</a>
											</p>


										</div>
									</div>
								{/if}
							{/if}

							{if ($sUserData.additional.payment.name == 'hgw_dd')}
								{if !empty($regData)}
									<h3 class="underline">{s name="ConfirmHeaderPayment" namespace='frontend/checkout/confirm_left'}{/s}</h3>
									<div class="payment_method">
										<div class="inner_container">
											<p>
												<strong>{$sUserData.additional.payment.description}</strong><br />

												{if !$sUserData.additional.payment.esdactive}
													{s name="ConfirmInfoInstantDownload" namespace='frontend/checkout/confirm_left'}{/s}
												{/if}
											</p>
											<p>
												{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}: {$regData.owner}<br/>
												{s name='hp_iban' namespace='frontend/register/hp_payment'}{/s}: {$regData.kto}<br/>
												<br/>
												<a href="{url controller=account action=payment sTarget=checkout}" class="button-middle small">
													{s name="ConfirmLinkChangePayment" namespace='frontend/checkout/confirm_left'}{/s}
												</a>
											</p>


										</div>
									</div>
								{/if}
							{/if}
						{else}
							{$smarty.block.parent}
						{/if}
                    {/if}
                {/block}
