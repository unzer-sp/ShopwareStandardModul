                {* Billing address *}
                {block name='frontend_checkout_confirm_left_billing_address'}
                    <div class="invoice-address">
                        <h3 class="underline">{s name="ConfirmHeaderBilling" namespace="frontend/checkout/confirm_left"}{/s}</h3>
						{if isset($billingAdd->NAME_COMPANY) && ($billingAdd->NAME_COMPANY != '')}
							<p>{$billingAdd->NAME_COMPANY}</p>
						{/if}
						<p>
							{if isset($billingAdd->NAME_SALUTATION)}
								{if ($billingAdd->NAME_SALUTATION|strtolower eq "mr") or ($billingAdd->NAME_SALUTATION|strtolower eq "herr")}
									{s name="ConfirmSalutationMr" namespace="frontend/checkout/confirm_left"}{/s}
								{else}
									{s name="ConfirmSalutationMs" namespace="frontend/checkout/confirm_left"}{/s}
								{/if}
							{/if}
							{$billingAdd->NAME_GIVEN} {$billingAdd->NAME_FAMILY}<br/>
							{$billingAdd->ADDRESS_STREET}<br/>
							{$billingAdd->ADDRESS_ZIP} {$billingAdd->ADDRESS_CITY}<br/>
							{$billingAdd->ADDRESS_COUNTRY}
                        </p>
						
                        {* Action buttons *}
                        <div class="actions">
                            <a href="{url controller=account action=billing sTarget=checkout}" class="button-middle small">
                                {s name="ConfirmLinkChangeBilling" namespace="frontend/checkout/confirm_left"}{/s}
                            </a>
                            <a href="{url controller=account action=selectBilling sTarget=checkout}" class="button-middle small">
                                {s name="ConfirmLinkSelectBilling" namespace="frontend/checkout/confirm_left"}{/s}
                            </a>
                        </div>
                    </div>
                {/block}

                {* Shipping address *}
                {block name='frontend_checkout_confirm_left_shipping_address'}
                    <div class="shipping-address">
                        <h3 class="underline">{s name="ConfirmHeaderShipping" namespace="frontend/checkout/confirm_left"}{/s}</h3>
						{if isset($billingAdd->NAME_COMPANY) && ($billingAdd->NAME_COMPANY != '')}
							<p>{$shippingAdd->NAME_COMPANY}</p>
						{/if}
						<p>
							{if isset($shippingAdd->NAME_SALUTATION)}							
								{if ($shippingAdd->NAME_SALUTATION|strtolower eq "mr") or ($shippingAdd->NAME_SALUTATION|strtolower eq "herr")}
									{s name="ConfirmSalutationMr" namespace="frontend/checkout/confirm_left"}{/s}
								{else}
									{s name="ConfirmSalutationMs" namespace="frontend/checkout/confirm_left"}{/s}
								{/if}
							{/if}
							{$shippingAdd->NAME_GIVEN} {$shippingAdd->NAME_FAMILY}<br/>
							{$shippingAdd->ADDRESS_STREET}<br/>
							{$shippingAdd->ADDRESS_ZIP} {$shippingAdd->ADDRESS_CITY}<br/>
							{$shippingAdd->ADDRESS_COUNTRY}<br/>
                        </p>
                    </div>
                {/block}

                {block name='frontend_checkout_confirm_left_payment_method'}
                    {if !$sRegisterFinished}
                        <div class="payment-display">
                            <h3 class="underline">{s name="ConfirmHeaderPayment" namespace="frontend/checkout/confirm_left"}{/s}</h3>
							{if isset($regData)}
								{if $sUserData.additional.payment.name == 'hgw_mpa'}
									{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
									{assign var='prePath' value="{link file=''}"}
									<p>
										<img style="width: 145px;" src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />
									</p>
								{/if}
								<p>
									{$regData.email}<br/>
									<br/>
									{$regData.cardnr}<br/>
									{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$regData.expMonth} / {$regData.expYear}
								</p>
							{else}
								{$smarty.block.parent}
							{/if}

                            {* Action buttons *}
                            <div class="actions">
                                <a href="{url controller=account action=payment sTarget=checkout}" class="button-middle small">
                                    {s name="ConfirmLinkChangePayment" namespace="frontend/checkout/confirm_left"}{/s}
                                </a>
                            </div>
                        </div>
                    {/if}
                {/block}