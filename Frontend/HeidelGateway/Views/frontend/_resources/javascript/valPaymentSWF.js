$(document).ready(function(){
	// SELECT PAYMENT
	if(window.location.pathname.indexOf('gateway') == '-1'){
		// save original form action
		var orgLink = jQuery('form.payment').attr('action');
		// change checked option
		jQuery('.payment fieldset').click(function(e){
			// change form action
			var checkedOpt = jQuery('.payment fieldset input:radio:checked').attr('class');			
			if(checkedOpt != undefined){
				var prefix = 'hgw_';
				var checkedOptPos = checkedOpt.indexOf(prefix);

				if(checkedOptPos >= 0){
					var pm = checkedOpt.substr(checkedOptPos+prefix.length);
					if(pm == 'pay'){ pm = 'va'; }
					
					if((jQuery('.reues_'+pm).length > 0) && !(jQuery('.reues_'+pm).is(':checked'))){
						var reuse = true;
					}else{
						var reuse = false;
					}
					if(formUrl != null){
						if((formUrl[pm] == undefined) || (formUrl[pm] == '') || (reuse)){ jQuery('form.payment').attr('action', orgLink); }
						else{ jQuery('form.payment').attr('action', formUrl[pm]); }
					}
				}else{
					jQuery('form.payment').attr('action', orgLink);
				}
			}
		});

		// REUSE PAYMENT
		jQuery('.payment input:checkbox').click(function(){
			var pm = jQuery(this).attr('class').substring(jQuery(this).attr('class').indexOf('_'));
			jQuery('.reuse'+pm).toggle(500);
			jQuery('.newreg'+pm).toggle(500);
		});
	}
});

// VALIDATE FORM
function valForm(){
	if(jQuery('.payment fieldset input:radio:checked').length != 0){
		var checkedOpt = jQuery('.payment fieldset input:radio:checked').attr('class');
		if(checkedOpt != undefined){
			// remove check vor cc and dc
			if((checkedOpt.indexOf('hgw_cc') == -1) && (checkedOpt.indexOf('hgw_dc') == -1)){
				if(checkedOpt.indexOf('hgw_') >= 0){
					// remove all 'errors'
					jQuery('.instyle_error').removeClass('instyle_error');
					checkedOpt = checkedOpt.substr(checkedOpt.indexOf('hgw_'));
					var pm = checkedOpt.substr(checkedOpt.indexOf('_')+1);

					// check if 'newreg' is shown
					if(jQuery('.newreg_'+pm).is(':visible')){
						// set 'error' to empty inputs
						jQuery('div .'+checkedOpt).find('input').each(function(){
							if(jQuery(this).val() == ''){
								jQuery(this).addClass('instyle_error');
							}else{
								jQuery(this).removeClass('instyle_error');
							}
						});

						if(pm == 'dd'){
							// if(jQuery('.newreg_'+pm+' #sepa_switch').find(":selected").val() == 'iban'){
								var errors = valInputDdIban(jQuery('.newreg_'+pm+' #iban').val(), pm);
							// }else{
							// 	var errors = valInputDdAccount(jQuery('.newreg_'+pm+' #account').val(), jQuery('.newreg_'+pm+' #bankcode').val());
							// }
						}
					}
				}else{			
					checkedOpt = checkedOpt.replace('radio','').trim();
				}

				if((jQuery('div.'+checkedOpt+' .instyle_error').length > 0)){
					jQuery('.error ul li').remove();
					jQuery('.error ul').append('<li>'+jQuery('.msg_fill').html()+'</li>');
					
					jQuery.each(errors, function(key, value){
						jQuery('.error ul').append('<li>'+jQuery(value).html()+'</li>');
					});
					
					jQuery('#center .error').show();
					jQuery('html, body').animate({ scrollTop: 0 }, 0);
					
					return false;
				}else{
					// disable all other input fields
					jQuery('.payment fieldset input').attr('disabled', 'disabled');
					jQuery('.payment fieldset select').attr('disabled', 'disabled');
					jQuery('.grid_5 .radio.'+checkedOpt).removeAttr('disabled');
					jQuery('.grid_8.'+checkedOpt).find('input').removeAttr('disabled');
					jQuery('.grid_8.'+checkedOpt).find('select').removeAttr('disabled');
				}
			}
		}
	}else{
		jQuery('.error ul li').remove();
		jQuery('.error ul').append('<li>'+jQuery('.msg_checkPymnt').html()+'</li>');
		jQuery('#center .error').show();
		jQuery('html, body').animate({scrollTop: 0}, 0);

		return false;
	}
}

// VALIDATE FORM ON GATEWAY
function valGatewayForm(){
	checkedOpt = jQuery('#payment .payment fieldset').find('div').attr('class');
	var pm = checkedOpt.substr(checkedOpt.indexOf('_')+1);

	if(pm == 'dd'){
		// if(jQuery('.newreg_'+pm+' #sepa_switch').find(":selected").val() == 'iban'){
			var errors = valInputDdIban(jQuery('.'+checkedOpt+' #iban').val(), pm);
		// }else{
		// 	var errors = valInputDdAccount(jQuery('.newreg_'+pm+' #account').val(), jQuery('.newreg_'+pm+' #bankcode').val());
		// }
	}else if((pm == 'sue') || (pm == 'gir')){
		var errors = {};
		var i = 0;
		
		if(jQuery('.newreg_'+pm+' #cardHolder').val() == ''){
			jQuery('.newreg_'+pm+' #cardHolder').addClass('instyle_error');
		}else{
			jQuery('.newreg_'+pm+' #cardHolder').removeClass('instyle_error');
		}
		
		if(jQuery('.newreg_'+pm+' #sepa_switch').find(":selected").val() == 'iban'){
			if(jQuery('.newreg_'+pm+' #iban').val() == ''){
				jQuery('.newreg_'+pm+' #iban').addClass('instyle_error');
				errors[i++] = '.msg_iban';				
			}else{
				jQuery('.newreg_'+pm+' #iban').removeClass('instyle_error');
			}
			if(jQuery('.newreg_'+pm+' #bic').val() == ''){
				jQuery('.newreg_'+pm+' #bic').addClass('instyle_error');
				errors[i++] = '.msg_bic';
			}else{
				jQuery('.newreg_'+pm+' #bic').removeClass('instyle_error');
			}
		}else{
			if(jQuery('.newreg_'+pm+' #account').val() == ''){ 
				jQuery('.newreg_'+pm+' #account').addClass('instyle_error');
				errors[i++] = '.msg_account';				
			}else{
				jQuery('.newreg_'+pm+' #account').removeClass('instyle_error');
			}			
			if(jQuery('.newreg_'+pm+' #bankcode').val() == ''){
				jQuery('.newreg_'+pm+' #bankcode').addClass('instyle_error');
				errors[i++] = '.msg_bank';
			}else{
				jQuery('.newreg_'+pm+' #bankcode').removeClass('instyle_error');
			}
		}
	}
	
	if((jQuery('.newreg_'+pm+' .instyle_error').length > 0)){
		jQuery('.error ul li').remove();
		jQuery('.error ul').append('<li>'+jQuery('.msg_fill').html()+'</li>');
		
		jQuery.each(errors, function(key, value){
			jQuery('.error ul').append('<li>'+jQuery(value).html()+'</li>');
		});
		
		jQuery('#payment .error').show();
		jQuery('html, body').animate({ scrollTop: 0 }, 0);
		
		return false;
	}
}

function valInputDdIban(iban, pm){
	var errors = {};
	var i = 0;
	
	iban = iban.trim();
	
	var regexIban	= new RegExp('^[A-Z]{2}[0-9]{2}[a-zA-Z0-9]{11,30}$');
	var regexBic	= new RegExp('^[a-zA-Z]{6}[a-zA-Z0-9]{2,5}$');
	
	if(iban.search(regexIban) == '-1'){
		jQuery('.newreg_'+pm+' #iban').addClass('instyle_error');
		errors[i++] = '.msg_iban';
	}else{
		jQuery('.newreg_'+pm+' #iban').val(iban);
		jQuery('.newreg_'+pm+' #iban').removeClass('instyle_error');
	}

	jQuery('.newreg_'+pm+' #account').removeClass('instyle_error');
	jQuery('.newreg_'+pm+' #bankcode').removeClass('instyle_error');

	return errors;
}

function valInputDdAccount(acc, bank){
	var errors = {};
	var i = 0;
	
	acc = acc.trim();
	bank = bank.trim();
	
	var regexAcc		= new RegExp('^[0-9]{6,16}$');
	var regexBank	= new RegExp('^[0-9]{5,8}$');
	
	if(acc.search(regexAcc) == '-1'){
		jQuery('.newreg_dd #account').addClass('instyle_error');
		errors[i++] = '.msg_account';
	}else{
		jQuery('.newreg_dd #account').removeClass('instyle_error');
	}
	
	if(bank.search(regexBank) == '-1'){
		jQuery('.newreg_dd #bankcode').addClass('instyle_error');
		errors[i++] = '.msg_bank';
	}else{
		jQuery('.newreg_dd #bankcode').removeClass('instyle_error');
	}
	
	jQuery('.newreg_dd #iban').removeClass('instyle_error');
	return errors;
}