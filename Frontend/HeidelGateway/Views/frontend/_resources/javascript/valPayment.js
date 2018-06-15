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

                    if( (typeof formUrl !== "undefined") && (formUrl != null)){
                        if((formUrl[pm] == undefined) || (formUrl[pm] == '') || (reuse) || (pm == 'cc') || (pm == 'dc')){
                            jQuery('form.payment').attr('action', orgLink);

                        }else{
                            jQuery('form.payment').attr('action', formUrl[pm]);
                            jQuery('form.frmRegister').attr('action', formUrl[pm]);
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
		var pm = $('input:radio:checked').attr('class');
        if(pm != undefined) {
            if(pm.indexOf("hgw_san") > 0)
            {
                // validation of Santander Inputs
                var errorsSan = valSantander();

                // adding failure-messages
                if((jQuery('.'+"hgw_san"+'  .instyle_error').length > 0)){
                    jQuery('.error ul li').remove();
                    jQuery('.error ul').append('<li>'+jQuery('.msg_fill').html()+'</li>');

                    jQuery.each(errorsSan, function(key, value){
                        jQuery('.error ul').append('<li>'+jQuery(value).html()+'</li>');
                    });

                    jQuery('.error').show();
                    jQuery('html, body').animate({ scrollTop: 0 }, 0);

                    return false;
                }

                var birthday = $(".hgw_san [name='Date_Day']").val();
                var birthmonth = $(".hgw_san [name = 'Date_Month']").val();
                var birthyear = $(".hgw_san [name = 'Date_Year']").val();
                var birthdate = birthyear + '-' + birthmonth + '-' + birthday;
                var salutation = $('.hgw_san #salutation').val();
                var adv_permission = $('#hgw_adv_san').val();
                var priv_policy = $('#hgw_privacyPolicy').val();

                if (adv_permission == 'on' || adv_permission == 'TRUE') {
                    adv_permission = "TRUE";
                } else {
                    adv_permission = "FALSE";
                }
                if (priv_policy == 'on' || priv_policy == 'TRUE') {
                    priv_policy = "TRUE";
                } else {
                    priv_policy = "FALSE";
                }

                $(".button-right.large").append('<input type="hidden" name="BRAND" id="handover_brand_san" value="SANTANDER">');
                $(".button-right.large").append('<input type="hidden" name="NAME.BIRTHDATE" value="' + birthdate + '">');
                $(".button-right.large").append('<input type="hidden" name="NAME.SALUTATION" value="' + salutation + '">');
                $(".button-right.large").append('<input type="hidden" name="CUSTOMER.OPTIN" value="' + adv_permission + '">');
                $(".button-right.large").append('<input type="hidden" name="CUSTOMER.OPTIN_2" value="' + priv_policy + '">');
            }

            if(pm.indexOf("hgw_ivpd") > 0)
            {
                var errorsPayolution = valPayolutionDirect();

                if((jQuery('.'+"hgw_ivpd"+'  .instyle_error').length > 0)){
                    jQuery('.error ul li').remove();
                    jQuery('.error ul').append('<li>'+jQuery('.msg_fill').html()+'</li>');

                    jQuery.each(errorsPayolution, function(key, value){
                        jQuery('.error ul').append('<li>'+jQuery(value).html()+'</li>');
                    });

                    jQuery('.error').show();
                    jQuery('html, body').animate({ scrollTop: 0 }, 0);

                    return false;

                }

                var birthday = $(".newreg_ivpd [name='Date_Day']").val();
                var birthmonth = $(".newreg_ivpd [name = 'Date_Month']").val();
                var birthyear = $(".newreg_ivpd [name = 'Date_Year']").val();
                var birthdate = birthyear+'-'+birthmonth+'-'+birthday;
                var salutation = $('.newreg_ivpd #salutation').val();

                $(".button-right.large").append('<input type="hidden" name="BRAND" id="handover_brand_ivpd" value="PAYOLUTION_DIRECT">');
                $(".button-right.large").append('<input type="hidden" name="NAME.BIRTHDATE" value="'+birthdate+'">');
                $(".button-right.large").append('<input type="hidden" name="NAME.SALUTATION" value="'+salutation+'">');
            }

            if(pm.indexOf("newreg_dd") > 0){
                jQuery('#iban').val(jQuery('#iban').val());
            }

        }

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

        // // disable all other input fields
        jQuery('.payment_method input').attr('disabled', 'disabled');
        jQuery('.payment_method select').attr('disabled', 'disabled');
        jQuery('.payment_method input:radio:checked').parents('.grid_15').find('input').removeAttr('disabled');
        jQuery('.payment_method input:radio:checked').parents('.grid_15').find('select').removeAttr('disabled');

        if(window.location.pathname.indexOf('gateway') >= '-1')
        {
            jQuery('#payType').find('input').removeAttr('disabled');
            jQuery('#payType').find('select').removeAttr('disabled');
        }
	});
	
	jQuery('.newreg_dd').click(function(e){
		var birthday = jQuery(".newreg_dd [name='Date_Day']").val();
		var birthmonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
		var birthyear = jQuery(".newreg_dd [name = 'Date_Year']").val();
		jQuery('#birthdate').val(birthyear+'-'+birthmonth+'-'+birthday);
		jQuery('#iban').val(jQuery('#iban').val());
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

    jQuery('.newreg_ivpd').click(function (e) {
        var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
        var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
        var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();

        jQuery('#birthdate_ivpd').val(birthYear + '-' + birthMonth + '-' + birthDay);
    });

    jQuery('.button-right.large.right').click(function(e) {
        if (jQuery('.radio.hgw_ivpd').is(':checked')) {
            var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();

            jQuery('#birthdate_ivpd').val(birthYear + '-' + birthMonth + '-' + birthDay);
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

    // Event before swiching payment method
    $.ajaxSetup({
        beforeSend: function(event, xhr, settings){
            // check chosen payment method
            var chosenPaymentMethod = $('input:radio:checked').attr('class');

            if(chosenPaymentMethod != undefined){
                var cut = parseInt(chosenPaymentMethod.indexOf("hgw_"))+4;
                chosenPaymentMethod = chosenPaymentMethod.substr(cut,4);

                //setting Payolution-checkbox to required if payolution is chosen
                if(chosenPaymentMethod == 'ivpd'){
                    $('#hgw_privpol_ivpd').attr("required","required");
                    $('#hgw_privpol_ivpd').prop("required","required");
                } else {
                    $('#hgw_privpol_ivpd').prop("required",null);
                    $('#hgw_privpol_ivpd').removeAttr("required");
                }
            } else {
                $('#hgw_privpol_ivpd').prop("required",null);
                $('#hgw_privpol_ivpd').removeAttr("required");
            }


        },
        complete: function(event, xhr, settings){
            var chosenPaymentMethod = $('input:radio:checked').attr('class');
            // check chosen payment method
            if(chosenPaymentMethod != undefined){
                var cut = parseInt(chosenPaymentMethod.indexOf("hgw_"))+4;
                chosenPaymentMethod = chosenPaymentMethod.substr(cut,4);

                //setting Payolution-checkbox to required if payolution is chosen
                if(chosenPaymentMethod == 'ivpd'){
                    $('#hgw_privpol_ivpd').attr("required","required");
                    $('#hgw_privpol_ivpd').prop("required","required");
                } else {
                    $('#hgw_privpol_ivpd').prop("required",null);
                    $('#hgw_privpol_ivpd').removeAttr("required");
                }
            }
        },
    });

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
                            jQuery('#iban').val(jQuery('.newreg_'+pm+' #iban').val());

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
                        if(pm == 'ivpd'){
                            var dob = new Date(jQuery('.hgw_ivpd select[name="Date_Year"]').val(), jQuery('.hgw_ivpd select[name="Date_Month"]').val()-1, jQuery('.hgw_ivpd select[name="Date_Day"]').val());
                            var today = new Date();
                            var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
                            var errors = valPayolutionDirect();

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
    if(pm == "papg"){
        var errors = valInvoiceSec();

        if((jQuery('.newreg_papg .has--error'))){
            if(jQuery.isEmptyObject(errors) == false){
                jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
                jQuery.each(errors, function(key, value){
                    jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                });

                jQuery('.alert').removeClass("is--hidden");
                jQuery('.alert').show();
                jQuery('html, body').animate({scrollTop: 0}, 0);
            }

        } else {
            jQuery('#payment .alert .is--error .is--rounded div').remove();
        }
    }

    if(pm == "dd"){
        jQuery('#iban').val(jQuery('.newreg_'+pm+' #iban').val());
    }
	if((jQuery('.'+checkedOpt+' .instyle_error').length > 0)){
		jQuery('.error ul li').remove();
		jQuery('.error ul').append('<li>'+jQuery('.msg_fill').html()+'</li>');

		jQuery.each(errors, function(key, value){
			jQuery('.error ul').append('<li>'+jQuery(value).html()+'</li>');
		});
		
		jQuery('#payment .error').show();
		jQuery('html, body').animate({ scrollTop: 0 }, 0);

        return  false;
	}
}

function valInputDdIban(iban, pm){
	var errors = {};
	var i = 0;
	
	iban = iban.trim();	
	var regexIban	= new RegExp('^[A-Z]{2}[0-9]{2}[a-zA-Z0-9]{11,30}$');
    jQuery('#iban').val(iban);
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

// function to show validate Santander Form
function valSantander() {
    var errors = {};
    var i = 0;

    // validation of salutation
    var salutation = $('.hgw_san select[name="NAME.SALUTATION"]').val();
    if(salutation == undefined || salutation == "-")
    {
        $('.hgw_san #salutation').parent('div').addClass('instyle_error');
        errors[i++] = '.msg_salut';
    } else {
        $('.hgw_san #salutation').parent('div').removeClass('instyle_error');
    }

    // validation of birthdate
    var birthdate = $('#birthdate_san').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {
        var dob = new Date(jQuery('.hgw_san select[name="Date_Year"]').val(), jQuery('.hgw_san select[name="Date_Month"]').val()-1, jQuery('.hgw_san select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
        if(age < 18){

            jQuery('.hgw_san select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
            jQuery('.hgw_san select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
            jQuery('.hgw_san select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');

            errors[i++] = '.msg_dob';
        }else{
            jQuery('.hgw_san select[name="Date_Year"]').parent('.outer-select').removeClass('instyle_error');
            jQuery('.hgw_san select[name="Date_Month"]').parent('.outer-select').removeClass('instyle_error');
            jQuery('.hgw_san select[name="Date_Day"]').parent('.outer-select').removeClass('instyle_error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.hgw_san select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
        jQuery('.hgw_san select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
        jQuery('.hgw_san select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');
        errors[i++] = '.msg_dob';
    }
    // validation of privacy policy
    if($("#hgw_privacyPolicy").is(':checked'))
    {
        $("#hgw_privacyPolicy").removeClass('instyle_error');
        $("#hgw_privacyPolicy").parent().next('p').removeClass('instyle_error');
    } else {
        $("#hgw_privacyPolicy").parent().next('p').addClass('instyle_error');
        $("#hgw_privacyPolicy").attr("required","required");

        errors[i++] = '.msg_cb';
        $('.hgw_san #hgw_privacyPolicy').attr("required","required");
    }
    return errors;
}

// function to show validate Payolution direct Form
function valPayolutionDirect() {
    var errors = {};
    var i = 0;

    // validation of salutation
    var salutation = $('.hgw_val_ivpd').val();
    if(salutation == undefined || salutation == "UNKNOWN")
    {
        $('.newreg_ivpd #salutation').parent('div').addClass("instyle_error");
        errors[i++] = '.msg_salut';
    } else {
        $('.newreg_ivpd #salutation').parent('div').removeClass('instyle_error');
    }

    // validation of birthdate
    var birthdate = $('#birthdate_ivpd').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {
        var dob = new Date(jQuery('.hgw_ivpd select[name="Date_Year"]').val(), jQuery('.hgw_ivpd select[name="Date_Month"]').val()-1, jQuery('.hgw_ivpd select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
        if(age < 18){

            jQuery('.hgw_ivpd select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
            jQuery('.hgw_ivpd select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
            jQuery('.hgw_ivpd select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');

            errors[i++] = '.msg_dob';
        }else{
            jQuery('.hgw_ivpd select[name="Date_Year"]').parent('.outer-select').removeClass('instyle_error');
            jQuery('.hgw_ivpd select[name="Date_Month"]').parent('.outer-select').removeClass('instyle_error');
            jQuery('.hgw_ivpd select[name="Date_Day"]').parent('.outer-select').removeClass('instyle_error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.hgw_ivpd select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
        jQuery('.hgw_ivpd select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
        jQuery('.hgw_ivpd select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');
        errors[i++] = '.msg_dob';
    }

    //validation of Checkbox
    if(document.getElementById("hgw_privpol_ivpd").checked){
        $('#hgw_privpol_ivpd').removeAttr("required");
        $("#payolutiontext").removeClass('instyle_error');
    } else {
        $("#payolutiontext").addClass('instyle_error');
        $('#hgw_privpol_ivpd').attr("required","required");
        errors[i++] = 'msg_cb';
    }

    //validation of Phonenumber for NL-Customers
    if($("#phone_ivpd").is(":visible"))
    {
        var phoneNumber = $("#phone_ivpd").val();
        if (valPhoneNumber(phoneNumber) != false) {
            $("#phone_ivpd").val(valPhoneNumber(phoneNumber));
            jQuery('.hgw_ivpd .register--phone').removeClass('has--error');
        } else {
            errors[i++] = '.msg_phone';
            jQuery('.hgw_ivpd #phone_ivpd').addClass('has--error');
        }
    }
    return errors;
}

function valInvoiceSec() {
    //var errors = [];
    var errors = new Array();
    var i = 0;
    // validation of salutation
    var salutation = $('.newreg_papg #salutation').val();
    if(salutation == undefined || salutation == "UNKNOWN")
    {
        $('.newreg_papg #salutation').parent('.outer-select').addClass("instyle_error");
        errors[i++] = '.msg_salut';
    } else {
        $('.newreg_papg #salutation').parent('.outer-select').removeClass('instyle_error');
    }

    // validation of birthdate
    var birthdate = $('#birthdate_papg').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {

        var dob = new Date(jQuery('.newreg_papg select[name="Date_Year"]').val(), jQuery('.newreg_papg select[name="Date_Month"]').val()-1, jQuery('.newreg_papg select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));

        if(age < 18){
            jQuery('.newreg_papg select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
            jQuery('.newreg_papg select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
            jQuery('.newreg_papg select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');
            errors[i++] = '.msg_dob';
        }else{
            jQuery('.newreg_papg select[name="Date_Year"]').parent('.outer-select').removeClass('instyle_error');
            jQuery('.newreg_papg select[name="Date_Month"]').parent('.outer-select').removeClass('instyle_error');
            jQuery('.newreg_papg select[name="Date_Day"]').parent('.outer-select').removeClass('instyle_error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.newreg_papg select[name="Date_Year"]').parent('.outer-select').addClass('instyle_error');
        jQuery('.newreg_papg select[name="Date_Month"]').parent('.outer-select').addClass('instyle_error');
        jQuery('.newreg_papg select[name="Date_Day"]').parent('.outer-select').addClass('instyle_error');
        errors[i++] = '.msg_dob';
    }

    jQuery('form.payment').find('input').removeAttr('disabled');
    jQuery('form.payment').find('select').removeAttr('disabled');

    if(errors.length > 0){
        return errors;
    }
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