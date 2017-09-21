{block name="frontend_index_header_javascript_jquery_lib" append}
	{if $swVersion >= "5.3"}
		<script type='text/javascript'>
            var mobile = "{$isMobile}";
            document.asyncReady(function() {
                jQuery('.wallet').click(function(){
                    jQuery('#lbOverlay').fadeIn(350);
                    $.loadingIndicator.open();

                    if(mobile){
                        // workaround for mobile devices
                        var href = $(this).attr('href');
                        setTimeout(function(){ window.location = href }, 500);
                    }
                });
            });
		</script>
	{else}
		<script type='text/javascript'>
            var mobile = "{$isMobile}";
            $(document).ready(function(){
                jQuery('.wallet').click(function(){
                    jQuery('#lbOverlay').fadeIn(350);
                    $.loadingIndicator.open();

                    if(mobile){
                        // workaround for mobile devices
                        var href = $(this).attr('href');
                        setTimeout(function(){ window.location = href }, 500);
                    }
                });
            });
		</script>
	{/if}

{/block}

{block name="masterpass_button"}
	<a style="clear: both; margin: 10px 0 0 0;" href="{url controller='PaymentHgw' action='wallet' wallet='masterpass'}" class="right wallet">
		<img src="https://www.mastercard.com/mc_us/wallet/img/{$lang}/{$langStu}/mcpp_wllt_btn_chk_290x068px.png" alt="MasterPass" />
	</a>
	<a href="https://www.mastercard.com/mc_us/wallet/learnmore/{$lang}/{$langStu}/" style="clear: both; margin: 10px 0 0 0;" class="right" target="_blank">
	{s name='hp_moreMpa' namespace='frontend/register/hp_payment'}{/s}</a>
{/block}

{block name="frontend_checkout_actions_confirm_bottom_checkout" append}
	{if (isset($avPayments.hgw_mpa) && $avPayments.hgw_mpa.active == '1')}
		{block name="masterpass_button"}{/block}
	{/if}
{/block}