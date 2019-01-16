                {* Billing address *}
                {block name='frontend_checkout_confirm_left_billing_address'}
					{if isset($billingAdd->NAME_COMPANY) && ($billingAdd->NAME_COMPANY != '')}
						<strong>{$billingAdd->NAME_COMPANY}</strong></br>
					{/if}
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
                {/block}
				{* remove action buttons *}
				
				{* block name="frontend_checkout_confirm_left_billing_address_actions"}{/block *}

                {* Shipping address *}
                {block name='frontend_checkout_confirm_left_shipping_address'}
					{if isset($billingAdd->NAME_COMPANY) && ($billingAdd->NAME_COMPANY != '')}
						<strong>{$shippingAdd->NAME_COMPANY}</strong></br>
					{/if}

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
                {/block}
				{* remove action buttons *}
				{block name="frontend_checkout_confirm_left_shipping_address_actions"}{/block}

                {block name='frontend_checkout_confirm_left_payment_method'}
					<p class="payment--method-info">
						<strong class="payment--title">{s name="ConfirmInfoPaymentMethod" namespace="frontend/checkout/confirm"}{/s}</strong>
						<span class="payment--description">{$sUserData.additional.payment.description}</span>
					</p>
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
									{s name='hp_cardHolder' namespace='frontend/register/hp_payment'}{/s}: {$regData.owner}<br/>
									{s name='hp_cardNumber' namespace='frontend/register/hp_payment'}{/s}: {$regData.cardnr}<br/>
									{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$regData.expMonth} / {$regData.expYear}
								{/if}

							{/if}

						{else}
							{$smarty.block.parent}

						{/if}
                    {/if}
                {/block}
