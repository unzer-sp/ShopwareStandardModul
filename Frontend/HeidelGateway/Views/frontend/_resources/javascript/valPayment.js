$(document).ready(function(){
	// SELECT PAYMENT
	if(window.location.pathname.indexOf('gateway') == '-1'){
		// save original form action
		var orgLink = jQuery('form.payment').attr('action');
		// show Form if clicked
		jQuery('.toggle_form').click(function(e){
			hideForm();
			showForm(this);
		});
		// change checked option
		jQuery('.payment_method').click(function(e){
			var elem = e.target;			
			if(jQuery(elem).children().find('input:radio').attr('id') == undefined){
				var pmElem = jQuery(elem).parents().find('input:radio').attr('id');
				jQuery('#'+pmElem).attr('checked', 'checked');
			}else{					
				var pmElem = jQuery(elem).children().find('input:radio').attr('id');
				jQuery('#'+pmElem).attr('checked', 'checked');
			}
			
			// change form action
			var checkedOpt = jQuery('.payment_method input:radio:checked').attr('class');
			if(checkedOpt != undefined && checkedOpt.indexOf('hgw_') >= 0){
				var prefix = 'hgw_';
				var checkedOptPos = checkedOpt.indexOf(prefix);

				if(checkedOptPos >= 0){
					var pm = checkedOpt.substr(checkedOptPos+prefix.length);
					if(pm == 'pay'){ pm = 'va'; }
					
					if(jQuery('.hgw_'+pm).parent('.show_form').length > 0){
						if(jQuery('.hgw_'+pm).parent('.show_form').is(":hidden")){
							hideForm();
						}
						showForm(jQuery('.hgw_'+pm).parent('.show_form').siblings('.toggle_form'));
					}else{
						hideForm();
					}
					
					if((jQuery('.reues_'+pm).length > 0) && !(jQuery('.reues_'+pm).is(':checked'))){
						var reuse = true;
					}else{
						var reuse = false;
					}
					
					// if(formUrl != null){
					// 	if((formUrl[pm] == undefined) || (formUrl[pm] == '') || (reuse) || (pm == 'cc') || (pm == 'dc')){
					// 		jQuery('form.payment').attr('action', orgLink);
					// 	}else{
					// 		jQuery('form.payment').attr('action', formUrl[pm]);
					// 	}
					// }
                    console.log(pm);
					if( (formUrl != null)&& (formUrl != undefined) ){

                        if((formUrl[pm] == undefined) || (formUrl[pm] == '') || (reuse) || (pm == 'cc') || (pm == 'dc')){
                            jQuery('form.payment').attr('action', orgLink);
                        }else{
                            jQuery('form.payment').attr('action', formUrl[pm]);
                        }
                    }
				}else{
					jQuery('form.payment').attr('action', orgLink);
					hideForm();
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
	
	//Function to set Birthdate in hidden field for Chrome on mac
	jQuery("input[type='submit'], .right").click(function(e){
	//jQuery(".content--wrapper").click(function(e){
		if(jQuery("input[type='submit'], .right").val() == "Weiter") {
			var birthDay =  null;
			var birthMonth = null;
			var birthYear = null;
			var pm = null;
			pm = jQuery("#payType").attr("class");
			pm = pm.substr(7);					
			
			if(jQuery(".newreg_"+pm) > 0) {
				birthDay = jQuery(".newreg_"+pm+" [name='Date_Day']").val();
				birthMonth = jQuery(".newreg_"+pm+" [name = 'Date_Month']").val();
				birthYear = jQuery(".newreg_"+pm+" [name = 'Date_Year']").val();
				jQuery("#birthdate_"+pm).val( birthYear+'-'+birthMonth+'-'+birthDay);
			} 
			
			if(birthYear == null) {
					jQuery(".newreg_"+pm+" [name = 'Date_Year']").val(jQuery(".newreg_"+pm+" [name = 'Date_Year']").next("div.js--fancy-select-text").text()) ;	
					var birthYear = jQuery(".newreg_"+pm+" [name = 'Date_Year']").next("div.js--fancy-select-text").text();
					var birthMonth = jQuery(".newreg_"+pm+" [name = 'Date_Month']").val();
					var birthDay = jQuery(".newreg_"+pm+" [name = 'Date_Day']").next("div.js--fancy-select-text").text();
					jQuery("#birthdate_"+pm).val(birthYear+'-'+birthMonth+'-'+birthDay);
			}
		}
	});
	
	jQuery('.newreg_dd').click(function(e){
		var birthday = jQuery(".newreg_dd [name='Date_Day']").val();
		var birthmonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
		var birthyear = jQuery(".newreg_dd [name = 'Date_Year']").val();
		jQuery('#birthdate').val(birthyear+'-'+birthmonth+'-'+birthday);
	});
	
	jQuery('.newreg_papg').click(function(e){

		var birthday = jQuery(".newreg_papg [name='Date_Day']").val();
		var birthmonth = jQuery(".newreg_papg [name = 'Date_Month']").val();
		var birthyear = jQuery(".newreg_papg [name = 'Date_Year']").val();
		
		jQuery('#birthdate_papg').val(birthyear+'-'+birthmonth+'-'+birthday);
			
		var formurlpapg = jQuery('.newreg_papg .formurl').val();
		jQuery('form.payment').attr('action', formurlpapg);
	});
			
	jQuery('.button-right, .large, .right').click(function(e) {
		if (jQuery('.radio.hgw_papg').is(':checked')) {
			var birthday = jQuery(".newreg_papg [name='Date_Day']").val();
			var birthmonth = jQuery(".newreg_papg [name = 'Date_Month']").val();
			var birthyear = jQuery(".newreg_papg [name = 'Date_Year']").val();
			
			jQuery('#birthdate_papg').val(birthyear+'-'+birthmonth+'-'+birthday);
		}
	});
	
	jQuery('.newreg_san').click(function(e){

		var birthday = jQuery(".newreg_san [name='Date_Day']").val();
		var birthmonth = jQuery(".newreg_san [name = 'Date_Month']").val();
		var birthyear = jQuery(".newreg_san [name = 'Date_Year']").val();
		
		jQuery('#birthdate_san').val(birthyear+'-'+birthmonth+'-'+birthday);
			
		var formurlsan = jQuery('.newreg_san .formurl').val();
		jQuery('form.payment').attr('action', formurlsan);
	});
			
	jQuery('.button-right.large.right').click(function(e) {
		if (jQuery('.radio.hgw_san').is(':checked')) {
			var birthday = jQuery(".newreg_san [name='Date_Day']").val();
			var birthmonth = jQuery(".newreg_san [name = 'Date_Month']").val();
			var birthyear = jQuery(".newreg_san [name = 'Date_Year']").val();
			
			jQuery('#birthdate_san').val(birthyear+'-'+birthmonth+'-'+birthday);
		}
	});

    //setting Checkbox for EasyCredit not Required
    jQuery('#hgw_cb_hpr').removeAttr("required");

    jQuery("[type=radio]").click(function(e){
        if( jQuery("[type=radio].hgw_hpr").is(":checked") ){
            jQuery('#hgw_cb_hpr').attr("required","required");
        } else {
            jQuery('#hgw_cb_hpr').removeAttr("required");
        }
    });

    if(jQuery('[name="ACTIVATEEASY"]')) {
        if(jQuery('[name="ACTIVATEEASY"]').val() == 'FALSE' || jQuery('[name="ACTIVATEEASY"]').val() == '') {
            jQuery('#easyText').hide();
            jQuery('[type=radio].hgw_hpr').attr('disabled','disabled');
            jQuery('[type=checkbox]#hgw_cb_hpr').attr('disabled','disabled');
            jQuery('.EasyPermission').attr('display','block');
        } else {
            jQuery('#easyText').show();
            jQuery('.hgw_hpr').removeAttr('disabled');
            jQuery('.EasyPermission').remove();
        }
    }

});

// VALIDATE FORM
function valForm(){
	if(jQuery('.payment_method input:radio:checked').length != 0){
		var checkedOpt = jQuery('.payment_method input:radio:checked').attr('class');
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
							// 	var errors = valInputDdAccount(jQuery('.newreg_'+pm+' #account').val(), jQuery('.newreg_'+pm+' #bankcode').val(), pm);
							// }
						}
						if(pm == 'papg'){
							var dob = new Date(jQuery('.hgw_papg select[name="Date_Year"]').val(), jQuery('.hgw_papg select[name="Date_Month"]').val()-1, jQuery('.hgw_papg select[name="Date_Day"]').val());
							var today = new Date();
							var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
							var errors = valBirthdate(age);
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
					jQuery('.payment_method input').attr('disabled', 'disabled');
					jQuery('.payment_method select').attr('disabled', 'disabled');
					jQuery('.payment_method input:radio:checked').parents('.grid_15').find('input').removeAttr('disabled');
					jQuery('.payment_method input:radio:checked').parents('.grid_15').find('select').removeAttr('disabled');
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
	checkedOpt = jQuery('#payment .payment_method').find('div').attr('class');
	var pm = checkedOpt.substr(checkedOpt.indexOf('_')+1);

	// set 'error' to empty inputs
	jQuery('.'+checkedOpt).find('input').each(function(){
		if(jQuery(this).val() == ''){
			jQuery(this).addClass('instyle_error');
		}else{
			jQuery(this).removeClass('instyle_error');
		}
	});

	if((pm == 'dd') || (pm == 'sue')){
		// if(jQuery('.'+checkedOpt+' #sepa_switch').find(":selected").val() == 'iban'){
			var errors = valInputDdIban(jQuery('.'+checkedOpt+' #iban').val(), pm);
		// }else{
		// 	var errors = valInputDdAccount(jQuery('.'+checkedOpt+' #account').val(), jQuery('.'+checkedOpt+' #bankcode').val(), pm);
		// }
	}else if(pm == 'gir'){
		var errors = valInputDdIban(jQuery('.'+checkedOpt+' #iban').val(), pm);
	}

	if((jQuery('.'+checkedOpt+' .instyle_error').length > 0)){
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


function valInputDdAccount(acc, bank, pm){
	var errors = {};
	var i = 0;

	acc = acc.trim();
	bank = bank.trim();
	
	var regexAcc	= new RegExp('^[0-9]{6,16}$');
	var regexBank	= new RegExp('^[0-9]{5,8}$');
	
	if(acc.search(regexAcc) == '-1'){
		jQuery('.newreg_'+pm+' #account').addClass('instyle_error');
		errors[i++] = '.msg_account';
	}else{
		jQuery('.newreg_'+pm+' #account').val(acc);
		jQuery('.newreg_'+pm+' #account').removeClass('instyle_error');
	}
	
	if(bank.search(regexBank) == '-1'){
		jQuery('.newreg_'+pm+' #bankcode').addClass('instyle_error');
		errors[i++] = '.msg_bank';
	}else{
		jQuery('.newreg_'+pm+' #bankcode').val(bank);
		jQuery('.newreg_'+pm+' #bankcode').removeClass('instyle_error');
	}
	
	jQuery('.newreg_'+pm+' #iban').removeClass('instyle_error');
	return errors;
}

function valBirthdate(age){
	var errors = {};
	var i = 0;

	if(age < 18){
		jQuery('.hgw_papg select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
		jQuery('.hgw_papg select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
		jQuery('.hgw_papg select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');
		
		errors[i++] = '.msg_dob';
	}else{
		jQuery('.hgw_papg select[name="Date_Year"]').parent('.outer-select').removeClass('instyle_error');
		jQuery('.hgw_papg select[name="Date_Month"]').parent('.outer-select').removeClass('instyle_error');
		jQuery('.hgw_papg select[name="Date_Day"]').parent('.outer-select').removeClass('instyle_error');
	}
	
	return errors;
}

// function to show iFrame form
function showForm(toggleForm){
	jQuery(toggleForm).slideUp();
	jQuery(toggleForm).siblings('.show_form').slideDown();
}

// function to hide iFrame form
function hideForm(){
	jQuery('.toggle_form:hidden').each(function(){
		jQuery(this).slideDown();
		jQuery(this).siblings('.show_form').slideUp();
	});
}