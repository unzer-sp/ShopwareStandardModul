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
                    {if !$sRegisterFinished}
						{if isset($regData)}
							<p class="payment--method-info">
								{if $sUserData.additional.payment.name == 'hgw_mpa'}
									{assign var='imgPath' value="{$tPath}/img/masterpass.png"}
									{assign var='prePath' value="{link file=''}"}
									<img src="{$prePath}{$imgPath|substr:1}" alt="MasterPass" />
								{/if}

								{$regData.email}<br/>
								<br/>
								{$regData.cardnr}<br/>
								{s name='hp_cardExpiry' namespace='frontend/register/hp_payment'}{/s}: {$regData.expMonth} / {$regData.expYear}
							</p>
						{else}
							{$smarty.block.parent}
						{/if}
                    {/if}
                {/block}
				{* remove shipping info *}
				{block name="frontend_checkout_confirm_left_shipping_method"}{/block}