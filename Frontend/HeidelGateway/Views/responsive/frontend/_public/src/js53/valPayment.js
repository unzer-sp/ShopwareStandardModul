document.asyncReady(function () {
    if (swVersion.substring(0, 3) >= '5.2') {
        var orgLink = jQuery('form.payment').attr('action');
        if (orgLink == 'undefined') {
            var orglink = jQuery('form[name="shippingPaymentForm"]').attr('action');
        }
        // SELECT PAYMENT
        if (window.location.pathname.indexOf('gateway') == '-1') {
            // save original form action
            var orgLink = jQuery('form.payment').attr('action');
            if (orgLink == 'undefined') {
                var orglink = jQuery('form[name="shippingPaymentForm"]').attr('action');
            }
            if (
                (window.location.pathname.toLowerCase().indexOf('shippingpayment') == '-1')
                ||(window.location.pathname.toLowerCase().indexOf('zahlungsart-und-versand') == '-1')
                ) {
                // change checked option
                jQuery('.register--payment').click(function () {
                    // change form action
                    var checkedOpt = jQuery('.register--payment input:radio:checked').attr('class');
                    changeUrl(checkedOpt, orgLink);
                });
                jQuery('.hgw_dd').click(function () {
                    // change form action
                    var checkedOpt = "hgw_dd";
                    changeUrl(checkedOpt, orgLink);
                });

            } else {
                var clicked = '';
                $(this).click(function (e) {
                    clicked = e.target.className;
                });
                jQuery('.payment--method-list').click(function () {
                    // change form action
                    var checkedOpt = jQuery('.payment--method input:radio:checked').attr('class');
                    changeUrl(checkedOpt, orgLink);

                });
                // add validation for form
                jQuery('form.payment').attr('onSubmit', 'return valShippingPaymentForm();');

                // set original form action (before AJAX is sent)
                $.ajaxSetup({
                    beforeSend: function (event, xhr, settings) {
                        // check for right ajax request
                        if (xhr.data != undefined) {
                            // just execute if hgw pay. method is selected
                            if (clicked.indexOf('hgw_') != -1) {
                                xhr.data += '&hgw=1';

                                if (this.url != orgLink) {
                                    this.url = orgLink;
                                    jQuery('form.payment').attr('action', orgLink);
                                }
                            }
                        }
                    },
                });

                $(document).ajaxComplete(function (event, xhr, settings) {
                    // function set birthdate for santander
                    if(jQuery('.newreg_san').is(":visible")) {
                        $(".hgw_required").attr("required","required");
                        var birthDay = jQuery(".newreg_san [name='Date_Day']").val();
                        var birthMonth = jQuery(".newreg_san [name = 'Date_Month']").val();
                        var birthYear = jQuery(".newreg_san [name = 'Date_Year']").val();

                        jQuery('#birthdate_san').val(birthYear+'-'+birthMonth+'-'+birthDay);
                    } else {
                        $(".hgw_required").removeAttr("required");
                    }

                    if(jQuery('.newreg_dd').is(":visible") && jQuery(".newreg_dd [name='Date_Day']").is(":visible")) {
                        var birthDay = jQuery(".newreg_dd [name='Date_Day']").val();
                        var birthMonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
                        var birthYear = jQuery(".newreg_dd [name = 'Date_Year']").val();

                        jQuery('#birthdate_dd').val(birthYear+'-'+birthMonth+'-'+birthDay);
                    }

                    if (((settings.data != undefined) && (settings.data.indexOf('hgw=1') != -1)) || ($('.payment--method-list input:radio:checked').attr('class').indexOf('hgw_') != -1)) {
                        // load fancy-js for select boxes
                        if (swVersion >= '5.1') {
                            jQuery('select:not([data-no-fancy-select="true"])').swSelectboxReplacement();
                        } else {
                            jQuery('select:not([data-no-fancy-select="true"])').selectboxReplacement();
                        }

                        // set width for XS-State (with SW-Statemanger)
                        StateManager.registerListener({
                            state: 'xs',
                            enter: function () {
                                jQuery('.js--fancy-select').attr('style', 'width:100%;');
                            },
                            exit: function () {
                                jQuery('.js--fancy-select').removeAttr('style');
                            }
                        });
                        // add validation for form
                        jQuery('form.payment').attr('onSubmit', 'return valShippingPaymentForm();');

                        // just call changeUrl() after all animations are done
                        $(document).promise().done(function () {
                            document.asyncReady(function () {
                                // $(document).reuse();
                                // $(document).ibanCheck();
                                var checkedOpt = jQuery('.payment--method-list input:radio:checked').attr('class');
                                $('input[class*="reues"]:checkbox, input[name*="ACCOUNT"], select[name*="ACCOUNT"], input[name*="CONTACT"]').click(function () {
                                    // change form action
                                    changeUrl(checkedOpt, orgLink);
                                });
                                if (checkedOpt.indexOf('papg') != '-1') {
                                    // change form action
                                    changeUrl(checkedOpt, orgLink);
                                }

                                if (checkedOpt.indexOf('san') != '-1') {
                                    // change form action
                                    changeUrl(checkedOpt, orgLink);
                                }
                            });
                        });
                    }
                });
            }
            // case to set or remove required attribute for payolution checkbos
            var paymentMethod = $('input:radio:checked').attr('class');
            if(paymentMethod != undefined) {
                if(paymentMethod.indexOf("hgw_") != -1){
                    // payment is payolution
                    $('#hgw_privpol_ivpd').attr("required","required");
                } else {
                    // payment is from heidelpay but not payolution
                    $('#hgw_privpol_ivpd').removeAttr("required");
                }
            } else {
                //payment is not one of heidelpay's
                $('#hgw_privpol_ivpd').removeAttr("required");
            }
        }

        // jQuery('#heidelB2bCompanyRegistered').change(function (e) {
        jQuery('[name="COMPANY.REGISTRATIONTYPE"]').change(function (e) {
           if(jQuery('.heidelB2bRegistered').is(":visible")){
               // company is registered
                jQuery('.heidelB2bRegistered').toggle(500);
                jQuery('.heidelB2bNotRegistered').toggle(500);

                jQuery('.heidelB2bRegistered :input').attr('disabled', 'disabled');
                jQuery('.heidelB2bNotRegistered :input').removeAttr("disabled");
           } else {
               // company NOT registered
               jQuery('.heidelB2bNotRegistered').toggle(500);
               jQuery('.heidelB2bRegistered').toggle(500);

               jQuery('.heidelB2bRegistered :input').removeAttr("disabled");
               jQuery('.heidelB2bNotRegistered :input').attr('disabled', 'disabled');
           }
        });

        // Function to set Birthdate in hidden field for Chrome on mac
        jQuery("button[type='submit'], .right").click(function (e) {
            var pm = $('input:radio:checked').attr('class');
            if(pm != undefined) {
                if(pm.indexOf("hgw_san") != -1)
                {
                    var errorsSan = valSantander();
                    if((jQuery(".hgw_san"+'  .has--error').length > 0)){
                        jQuery('#payment .alert .alert--content ul li').remove();

                        jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
                        jQuery.each(errorsSan, function(key, value){
                            jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                        });

                        jQuery('.alert').removeClass("is--hidden");
                        jQuery('html, body').animate({scrollTop: 0}, 0);

                        return false;
                    }
                } else {

                    $(".hgw_required").removeAttr("required");
                    $(".newreg_san #handover_brand_san").attr('disabled', 'disabled');
                    $(".newreg_san .js--fancy-select").attr('disabled', 'disabled');
                    $("#birthdate_san").attr('disabled', 'disabled');
                    $(".hgw_val_san #salutation").attr('disabled', 'disabled');
                }

                if(pm.indexOf("hgw_ivpd") != -1)
                {
                    //setting Birthdate to hidden input
                    if(jQuery('.newreg_ivpd').is(":visible")) {
                        var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
                        var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
                        var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();
                        jQuery('#birthdate_ivpd').val(birthYear+'-'+birthMonth+'-'+birthDay);
                    }

                    //validation of inputs
                    var errorsIvpd = valPayolutionDirect();

                    if(errorsIvpd.length >0)
                    {
                        return false;
                    }

                    if((jQuery('.'+"hgw_ivpd"+'  .has--error').length > 0)){
                        jQuery('#payment .alert .alert--content ul li').remove();

                        jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
                        jQuery.each(errorsIvpd, function(key, value){
                            jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                        });

                        jQuery('.alert').removeClass("is--hidden");
                        jQuery('html, body').animate({scrollTop: 0}, 0);

                        return false;
                    }
                }else {
                    $('#hgw_privpol_ivpd').removeAttr("required");
                    $(".newreg_ivpd #handover_brand_ivpd").attr('disabled', 'disabled');
                    $(".newreg_ivpd .js--fancy-select").attr('disabled', 'disabled');
                    $("#birthdate_ivpd").attr('disabled', 'disabled');
                    $(".hgw_val_ivpd #salutation").attr('disabled', 'disabled');
                }

                if(pm.indexOf("hgw_dd") != -1){
                    var errorsDD = new Array();
                    //validation of Iban
                    var errorsDD = valInputDdIban(jQuery('.newreg_dd #iban').val(), pm);

                    // direct debit secured
                    if(jQuery('.newreg_dd #salutation').is(':visible')){
                        // getting Values from input fields
                        // var salutation = jQuery('.newreg_' + pm + ' #salutation').val();
                        var birthDay = jQuery('.newreg_dd select[name=Date_Day]').val();
                        var birthMonth = jQuery('.newreg_dd select[name=Date_Month]').val();
                        var birthYear = jQuery('.newreg_dd select[name=Date_Year]').val();

                        jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
                        // validation of birthdate and salutation
                        errorsDD = valDirectDebitSecured(errorsDD);
                    }

                    if((jQuery('.'+"hgw_dd"+'  .has--error').length > 0)){
                        jQuery('#payment .alert .alert--content ul li').remove();

                        jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
                        jQuery.each(errorsDD, function(key, value){
                            jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                        });

                        jQuery('.alert').removeClass("is--hidden");
                        jQuery('html, body').animate({scrollTop: 0}, 0);

                        return false;
                    }
                }

                if (pm.indexOf("hgw_hps") != -1){
                    var errorsHps = new Array();
                    errorsHps = valSantanderHP();
                    if(errorsHps.length > 0){

                        jQuery('#payment .alert .alert--content ul li').remove();

                        jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
                        jQuery.each(errorsHps, function(key, value){
                            jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                        });

                        jQuery('.alert').removeClass("is--hidden");
                        jQuery('html, body').animate({scrollTop: 0}, 0);
                        return false;
                    }
                }
            } else {
                // case for other payment methods than heidelpay's on account/payment
                $('#hgw_privpol_ivpd').removeAttr("required");
            }

            if (jQuery("input[type='submit'], .right").val() == "Weiter") {
                var birthDay = null;
                var birthMonth = null;
                var birthYear = null;
                var pm = null;

                if(jQuery("#payType").attr("class") == undefined){
                    var classes = jQuery(".payment_method.debit").attr("class");
                    var indexOfHgw = jQuery(".payment_method.debit").attr("class").indexOf("hgw_");
                    pm = classes.substring(indexOfHgw+4);
                } else {
                    pm = jQuery("#payType").attr("class");
                    pm = pm.substr(7);
                }

                if(pm != undefined)
                {
                    if (jQuery(".newreg_" + pm).is(":visible")) {
                        birthDay = jQuery(".newreg_" + pm + " [name='Date_Day']").val();
                        birthMonth = jQuery(".newreg_" + pm + " [name = 'Date_Month']").val();
                        birthYear = jQuery(".newreg_" + pm + " [name = 'Date_Year']").val();
                        jQuery("#birthdate_" + pm).val(birthYear + '-' + birthMonth + '-' + birthDay);
                    }

                    if (birthYear == null) {
                        jQuery(".newreg_" + pm + " [name = 'Date_Year']").val(jQuery(".newreg_" + pm + " [name = 'Date_Year']").next("div.js--fancy-select-text").text());
                        var birthYear = jQuery(".newreg_" + pm + " [name = 'Date_Year']").next("div.js--fancy-select-text").text();
                        var birthMonth = jQuery(".newreg_" + pm + " [name = 'Date_Month']").val();
                        var birthDay = jQuery(".newreg_" + pm + " [name = 'Date_Day']").next("div.js--fancy-select-text").text();
                        jQuery("#birthdate_" + pm).val(birthYear + '-' + birthMonth + '-' + birthDay);
                    }

                    if (pm != "san") {
                        $(".hgw_required").removeAttr("required");
                    } else {
                        $(".hgw_required").attr("required", "required");
                    }
                }
                // to disable unused inputs for IVB2B
                if(pm == "ivb2b"){
                    if(jQuery('.heidelB2bRegistered').is(':visible')){
                        // jQuery('.heidelB2bNotRegistered').remove();
                        jQuery('.heidelB2bNotRegistered :input').attr("disabled","disabled");
                        jQuery('.heidelB2bRegistered :input').removeAttr("disabled");
                        $('.newreg_ivb2b [name="Date_Day"]').prop('required','');
                        $('.newreg_ivb2b [name="Date_Month"]').prop('required','');
                        $('.newreg_ivb2b [name="Date_Year"]').prop('required','');
                        $('.newreg_ivb2b [name="COMPANY.COMMERCIALREGISTERNUMBER"]').prop('required','required');
                    } else {
                        // jQuery('.heidelB2bRegistered').remove();
                        jQuery('.heidelB2bRegistered :input').attr("disabled","disabled");
                        jQuery('.heidelB2bNotRegistered :input').removeAttr("disabled");
                        $('.newreg_ivb2b [name="Date_Day"]').prop('required','required');
                        $('.newreg_ivb2b [name="Date_Month"]').prop('required','required');
                        $('.newreg_ivb2b [name="Date_Year"]').prop('required','required');
                        $('.newreg_ivb2b [name="COMPANY.COMMERCIALREGISTERNUMBER"]').prop('required','');
                    }
                }


            } else {
                var cssClasses = jQuery('input:radio:checked').attr('class');
                if (cssClasses != undefined)
                {
                    var indexOfHgw = jQuery('input:radio:checked').attr('class').indexOf("hgw_");
                    pm = cssClasses.substring(indexOfHgw+4);

                    if (pm != undefined) {
                        //pm = pm.substr(7);
                        if (pm != "san") {
                            $(".hgw_required").removeAttr("required");
                            $("#handover_brand_san").attr('disabled', 'disabled');
                        } else {
                            $(".hgw_required").attr("required", "required");
                        }

                        if(pm != "ivpd"){
                            $(".newreg_ivpd #handover_brand_ivpd").attr('disabled', 'disabled');
                            $(".newreg_ivpd .js--fancy-select").attr('disabled', 'disabled');
                            $(".newreg_ivpd .hgw_val_ivpd").attr('disabled', 'disabled');
                            $(".newreg_ivpd #birthdate_ivpd").attr('disabled', 'disabled');
                        }
                    }
                }
            }

            // disable all other input fields
            jQuery('.payment--method input').attr('disabled', 'disabled');
            jQuery('.payment--method select').attr('disabled', 'disabled');
            // jQuery(".hgw_"+pm).parents('.payment--method').find('input').removeAttr('disabled');
            // jQuery(".hgw_"+pm).parents('.payment--method').find('select').removeAttr('disabled');
            jQuery("input:radio").removeAttr('disabled');
            jQuery("input:radio:checked").parent().parent().find('input').removeAttr('disabled');
            jQuery("input:radio:checked").parent().parent().find('select').removeAttr('disabled');
        }); // Ende input:submitt:right

        jQuery('.newreg_papg').click(function (e) {

            var birthDay = jQuery(".newreg_papg [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_papg [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_papg [name = 'Date_Year']").val();

            jQuery('#birthdate_papg').val(birthYear + '-' + birthMonth + '-' + birthDay);
        });

        if (jQuery('.newreg_papg')) {
            var birthDay = jQuery(".newreg_papg [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_papg [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_papg [name = 'Date_Year']").val();

            jQuery('#birthdate_papg').val(birthYear + '-' + birthMonth + '-' + birthDay);
        }

        jQuery('.newreg_ivpd').click(function (e) {
            var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();

            jQuery('#birthdate_papd').val(birthYear + '-' + birthMonth + '-' + birthDay);
        });

        if(jQuery('.newreg_ivpd').is(":visible")) {

            var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();

            jQuery('#birthdate_ivpd').val(birthYear+'-'+birthMonth+'-'+birthDay);
        }

        jQuery('.newreg_san').click(function (e) {
            $(".hgw_required").attr("required","required");
            var birthDay = jQuery(".newreg_san [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_san [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_san [name = 'Date_Year']").val();

            jQuery('#birthdate_san').val(birthYear + '-' + birthMonth + '-' + birthDay);
        });

        if(jQuery('.newreg_san').is(":visible")) {
            $(".hgw_required").attr("required","required");
            var birthDay = jQuery(".newreg_san [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_san [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_san [name = 'Date_Year']").val();

            jQuery('#birthdate_san').val(birthYear+'-'+birthMonth+'-'+birthDay);
        } else {
            $(".hgw_required").removeAttr("required");
        }

        jQuery('.newreg_dd').click(function (e) {
            var birthDay = jQuery(".newreg_dd [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_dd [name = 'Date_Year']").val();
            jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
        });


        if (jQuery('.newreg_dd')) {
            var birthDay = jQuery(".newreg_dd [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_dd [name = 'Date_Year']").val();

            jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
        }

        jQuery('.newreg_hps').change(function (e) {
            var birthDay = jQuery(".newreg_hps [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_hps [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_hps [name = 'Date_Year']").val();
            jQuery('#birthdate_sanHps').val(birthYear + '-' + birthMonth + '-' + birthDay);
        });

        if (jQuery('.newreg_hps')) {
            var birthDay = jQuery(".newreg_hps [name='Date_Day']").val();
            var birthMonth = jQuery(".newreg_hps [name = 'Date_Month']").val();
            var birthYear = jQuery(".newreg_hps [name = 'Date_Year']").val();

            jQuery('#birthdate_sanHps').val(birthYear + '-' + birthMonth + '-' + birthDay);
        }

        $( document ).ajaxComplete(function() {
            jQuery('.newreg_dd').click(function (e) {
                var birthDay = jQuery(".newreg_dd [name='Date_Day']").val();
                var birthMonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
                var birthYear = jQuery(".newreg_dd [name = 'Date_Year']").val();
                jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
                // change form action
                changeUrl('hgw_dd', orgLink);
            });


            if (jQuery('.newreg_dd')) {
                var birthDay = jQuery(".newreg_dd [name='Date_Day']").val();
                var birthMonth = jQuery(".newreg_dd [name = 'Date_Month']").val();
                var birthYear = jQuery(".newreg_dd [name = 'Date_Year']").val();

                jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
            }

            jQuery('.newreg_ivpd').click(function (e) {
                var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
                var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
                var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();

                jQuery('#birthdate_ivpd').val(birthYear + '-' + birthMonth + '-' + birthDay);
            });

            if (jQuery('.newreg_ivpd')) {
                var birthDay = jQuery(".newreg_ivpd [name='Date_Day']").val();
                var birthMonth = jQuery(".newreg_ivpd [name = 'Date_Month']").val();
                var birthYear = jQuery(".newreg_ivpd [name = 'Date_Year']").val();

                jQuery('#birthdate_ivpd').val(birthYear + '-' + birthMonth + '-' + birthDay);
            }
            // getting PaymentMetod
            var cssClasses = jQuery('input:radio:checked').attr('class');

            if (cssClasses != undefined) {
                if(cssClasses.indexOf("hgw_")){
                    var indexOfHgw = jQuery('input:radio:checked').attr('class').indexOf("hgw_");
                    pm = cssClasses.substring(indexOfHgw + 4);

                    // disable all other input fields
                    jQuery('.payment--method .method--bankdata input').attr('disabled', 'disabled');
                    jQuery('.payment--method .method--bankdata select').attr('disabled', 'disabled');
                    // jQuery(".hgw_"+pm).parents('.payment--method').find('input').removeAttr('disabled');
                    // jQuery(".hgw_"+pm).parents('.payment--method').find('select').removeAttr('disabled');
                    jQuery("input:radio").removeAttr('disabled');
                    jQuery("input:radio:checked").parent().parent().find('input').removeAttr('disabled');
                    jQuery("input:radio:checked").parent().parent().find('select').removeAttr('disabled');
                }
            }
        }); // ende ajaxComplete
    }

    // Event before swiching payment method
    $.ajaxSetup({
        beforeSend: function(event, xhr, settings){
            // check chosen payment method
            if(window.location.pathname.indexOf('shippingPayment') >= 0){
                var chosenPaymentMethod = $('input:radio:checked').attr('class');
                var cut = parseInt(chosenPaymentMethod.indexOf("hgw_")) + 4;
                chosenPaymentMethod = chosenPaymentMethod.substr(cut, 4);
                //setting Payolution-checkbox to required if payolution is chosen
                if (chosenPaymentMethod == 'ivpd') {
                    $('#hgw_privpol_ivpd').attr("required", "required");
                    $('#hgw_privpol_ivpd').prop("required", "required");
                } else {
                    $('#hgw_privpol_ivpd').prop("required", null);
                    $('#hgw_privpol_ivpd').removeAttr("required");
                }

                jQuery('form.payment').attr('onSubmit', 'return valShippingPaymentForm();');
            }
        },
        complete: function(event, xhr, settings){
            if(window.location.pathname.indexOf('shippingPayment') >=0) {
                var chosenPaymentMethod = $('input:radio:checked').attr('class');
                var cut = parseInt(chosenPaymentMethod.indexOf("hgw_")) + 4;
                chosenPaymentMethod = chosenPaymentMethod.substr(cut, 4);

                //setting Payolution-checkbox to required if payolution is chosen
                if (chosenPaymentMethod == 'ivpd') {
                    $('#hgw_privpol_ivpd').attr("required", "required");
                    $('#hgw_privpol_ivpd').prop("required", "required");
                } else {
                    $('#hgw_privpol_ivpd').prop("required", null);
                    $('#hgw_privpol_ivpd').removeAttr("required");
                }

                jQuery('form.payment').attr('onSubmit', 'return valShippingPaymentForm();');
            }
        },
    });
});

// function to toggle between registrated payment data and enter new paymentdata
function hgwToggleReuse (pm)
{
    jQuery('.reuse' + pm).toggle(500);
    jQuery('.newreg' + pm).toggle(500);
}

// CHANGE FORM URL
function changeUrl(checkedOpt, orgLink) {
    if (checkedOpt != undefined) {
        var prefix = 'hgw_';
        var checkedOptPos = checkedOpt.indexOf(prefix);

        if (checkedOptPos >= 0) {
            var pm = checkedOpt.substr(checkedOptPos + prefix.length);

            if (pm == 'pay') { pm = 'va';}

            if ((jQuery('.reues_' + pm).length > 0) && !(jQuery('.reues_' + pm).is(':checked'))) {
                var reuse = true;
            } else {
                var reuse = false;
            }
            if (formUrl != null) {
                if ((formUrl[pm] == undefined) || (formUrl[pm] == '') || (reuse) || (pm == 'cc') || (pm == 'dc')) {
                    jQuery('form.payment').attr('action', orgLink);
                } else {
                    jQuery('form.payment').attr('action', formUrl[pm]);
                }
            }
        } else {
            jQuery('form.payment').attr('action', orgLink);
        }
    } else {
        jQuery('form.payment').attr('action', orgLink);
    }

}

// VALIDATE FORM
function valForm() {
    if (jQuery('.register--payment input:radio:checked').length != 0) {
        var checkedOpt = jQuery('.register--payment input:radio:checked').attr('class');
        if (checkedOpt != undefined) {
            // remove check vor cc and dc
            if ((checkedOpt.indexOf('hgw_cc') == -1) && (checkedOpt.indexOf('hgw_dc') == -1)) {
                if (checkedOpt.indexOf('hgw_') >= 0) {
                    // remove all 'errors'
                    jQuery('.has--error').removeClass('has--error');
                    checkedOpt = checkedOpt.substr(checkedOpt.indexOf('hgw_'));
                    var pm = checkedOpt.substr(checkedOpt.indexOf('_') + 1);

                    // check if 'newreg' is shown
                    if (jQuery('.newreg_' + pm).is(':visible')) {
                        // set 'error' to empty inputs
                        jQuery('div .' + checkedOpt).find('input').each(function () {
                            if (jQuery(this).val() == '') {
                                jQuery(this).addClass('has--error');
                            } else {
                                jQuery(this).removeClass('has--error');
                            }
                        });
                        if (pm == 'dd') {
                            var errors = new Array();

                            // direct debit
                            var errors = valInputDdIban(jQuery('.newreg_' + pm + ' #iban').val(), pm);

                            // direct debit secured
                            if(jQuery('.newreg_' + pm + ' #salutation').is(':visible')){
                                // getting Values from input fields
                                // var salutation = jQuery('.newreg_' + pm + ' #salutation').val();
                                var birthDay = jQuery('.newreg_' + pm + ' select[name=Date_Day]').val();
                                var birthMonth = jQuery('.newreg_' + pm + ' select[name=Date_Month]').val();
                                var birthYear = jQuery('.newreg_' + pm + ' select[name=Date_Year]').val();

                                jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);

                                errors = valDirectDebitSecured(errors);
                            }

                        }

                        if (pm == 'papg') {
                            var dob = new Date(jQuery('.hgw_papg select[name="Date_Year"]').val(), jQuery('.hgw_papg select[name="Date_Month"]').val() - 1, jQuery('.hgw_papg select[name="Date_Day"]').val());
                            var today = new Date();
                            var age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                            var errors = valBirthdate(age);
                        }

                        if (pm == 'san') {
                            var dob = new Date(jQuery('.hgw_san select[name="Date_Year"]').val(), jQuery('.hgw_san select[name="Date_Month"]').val() - 1, jQuery('.hgw_san select[name="Date_Day"]').val());
                            var today = new Date();
                            var age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                            var errors = valBirthdate(age);
                        }

                        if (pm == 'ivpd') {
                            var dob = new Date(jQuery('.hgw_ivpd select[name="Date_Year"]').val(), jQuery('.hgw_ivpd select[name="Date_Month"]').val() - 1, jQuery('.hgw_ivpd select[name="Date_Day"]').val());
                            var today = new Date();
                            var age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                            var errors = valBirthdate(age);
                        }
                    }
                } else {
                    checkedOpt = checkedOpt.replace('radio', '').trim();
                }

                if ((jQuery('div.' + checkedOpt + ' .has--error').length > 0)) {
                    if (jQuery('#center .panel.has--border .alert--content ul').length == 0) {
                        jQuery('#center .panel.has--border .alert--content').html('<ul class="alert--list"></ul>');
                    }

                    jQuery('#center .alert .alert--content ul li').remove();
                    jQuery('#center .alert .alert--content ul').append('<li class="list--entry">' + jQuery('.msg_fill').html() + '</li>');

                    jQuery.each(errors, function (key, value) {
                        jQuery('#center .alert .alert--content ul').append('<li class="list--entry">' + jQuery(value).html() + '</li>');
                    });

                    jQuery('#center .alert').show();
                    jQuery('html, body').animate({scrollTop: 0}, 0);

                    return false;
                } else {
                    // disable all other input fields
                    // jQuery('.register--payment input').attr('disabled', 'disabled');
                    // jQuery('.register--payment select').attr('disabled', 'disabled');
                    // jQuery('.register--payment input:radio:checked').parents('.payment--method').find('input').removeAttr('disabled');
                    // jQuery('.register--payment input:radio:checked').parents('.payment--method').find('select').removeAttr('disabled');
                    jQuery('.payment--method .block input').attr('disabled', 'disabled');
                    jQuery('.payment--method .block select').attr('disabled', 'disabled');
                    jQuery('.payment--method .block input:radio:checked').parents('.payment--method').find('input').removeAttr('disabled');
                    jQuery('.payment--method .block input:radio:checked').parents('.payment--method').find('select').removeAttr('disabled');
                }
            }
        }
    } else {
        jQuery('#center .alert .alert--content ul li').remove();
        jQuery('#center .alert .alert--content ul').append('<li class="list--entry">' + jQuery('.msg_checkPymnt').html() + '</li>');
        jQuery('#center .alert').show();
        jQuery('html, body').animate({scrollTop: 0}, 0);

        return false;
    }

}

// VALIDATE FORM ON GATEWAY
function valGatewayForm() {
    checkedOpt = jQuery('#payment .payment_method').find('div').attr('class');
    var pm = checkedOpt.substr(checkedOpt.indexOf('_') + 1);

    // set 'error' to empty inputs
    jQuery('.' + checkedOpt).find('input').each(function () {
        if (jQuery(this).val() == '') {
            jQuery(this).addClass('has--error');
        } else {
            jQuery(this).removeClass('has--error');
        }
    });
    // if (pm == 'dd') {
    //     var errors = new Array();
    //     // direct debit
    //     errors = valInputDdIban(jQuery('.' + checkedOpt + '  #iban').val(), pm);
    //
    //     // direct debit secured
    //     if(jQuery('#salutation').is(':visible')){
    //         // getting Values from input fields
    //         var birthDay = jQuery('select[name=Date_Day]').val();
    //         var birthMonth = jQuery('select[name=Date_Month]').val();
    //         var birthYear = jQuery('select[name=Date_Year]').val();
    //
    //         jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
    //
    //         errors = valDirectDebitSecured(errors);
    //     }
    // } else if (pm == 'gir') {
    //     var errors = valInputDdIban(jQuery('.' + checkedOpt + '  #iban').val(), pm);
    // } else if (pm == "papg"){
    //     var errorsPapg = valInvoiceSec();
    //
    //     if((jQuery('.newreg_papg .has--error'))){
    //         if(jQuery.isEmptyObject(errorsPapg) == false){
    //             jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
    //             jQuery.each(errorsPapg, function(key, value){
    //                 jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
    //             });
    //
    //             jQuery('.alert').removeClass("is--hidden");
    //             jQuery('.alert').show();
    //             jQuery('html, body').animate({scrollTop: 0}, 0);
    //
    //             return false;
    //         }
    //
    //     } else {
    //         jQuery('#payment .alert .is--error .is--rounded div').remove();
    //     }
    // }
    switch (pm) {
        case 'dd':
            var errors = new Array();
            // direct debit
            errors = valInputDdIban(jQuery('.' + checkedOpt + '  #iban').val(), pm);

            // direct debit secured
            if(jQuery('#salutation').is(':visible')){
                // getting Values from input fields
                var birthDay = jQuery('select[name=Date_Day]').val();
                var birthMonth = jQuery('select[name=Date_Month]').val();
                var birthYear = jQuery('select[name=Date_Year]').val();

                jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);

                errors = valDirectDebitSecured(errors);
            }
            break;
        case 'gir':
            var errors = valInputDdIban(jQuery('.' + checkedOpt + '  #iban').val(), pm);
            break;
        case 'papg':
            var errorsPapg = valInvoiceSec();

            if((jQuery('.newreg_papg .has--error'))){
                if(jQuery.isEmptyObject(errorsPapg) == false){
                    jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');
                    jQuery.each(errorsPapg, function(key, value){
                        jQuery('.alert--content ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                    });

                    jQuery('.alert').removeClass("is--hidden");
                    jQuery('.alert').show();
                    jQuery('html, body').animate({scrollTop: 0}, 0);

                    return false;
                }

            } else {
                jQuery('#payment .alert .is--error .is--rounded div').remove();
            }
            break;
        case 'ivb2b':
            if(jQuery('.heidelB2bRegistered').is(':visible')){
                // jQuery('.heidelB2bNotRegistered').remove();
                jQuery('.heidelB2bNotRegistered :input').attr("disabled","disabled");
                jQuery('.heidelB2bRegistered :input').removeAttr("disabled");
            } else {
                // jQuery('.heidelB2bRegistered').remove();
                jQuery('.heidelB2bRegistered :input').attr("disabled","disabled");
                jQuery('.heidelB2bNotRegistered :input').removeAttr("disabled");
            }
            var errors = valInvoiceB2b();
            break;
    }

    if ((jQuery('.' + checkedOpt + '  .has--error').length > 0)) {
        jQuery('#payment .alert .alert--content ul li').remove();
        jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">' + jQuery('.msg_fill').html() + '</li>');

        jQuery.each(errors, function (key, value) {
            jQuery('#payment .alert .alert--content ul').append('<li class="list--entry">' + jQuery(value).html() + '</li>');
        });

        jQuery('#payment .alert').show();
        jQuery('html, body').animate({scrollTop: 0}, 0);

        return false;
    }
}

// VALIDATE FORM ON SHIPPINGPAYMENT
function valShippingPaymentForm() {
    $(".alert.is--error.is--rounded").remove();
    var checkedOpt = jQuery('.payment--method-list input:radio:checked').attr('class');
    var pm = checkedOpt.substr(checkedOpt.indexOf('hgw_') + 4);
    // remove check vor cc and dc
    if ((pm != 'cc') && (pm != 'dc')) {
        // check if 'newreg' is shown
        if (jQuery('.newreg_' + pm).is(':visible')) {
            // set 'error' to empty inputs
            jQuery('.hgw_' + pm).find('input').each(function () {
                if (jQuery(this).val() == '') {
                    jQuery(this).addClass('has--error');
                } else {
                    jQuery(this).removeClass('has--error');
                }
            });

            // if (pm == 'dd') {
            //     var errors = new Array();
            //     // direct debit
            //     errors = valInputDdIban(jQuery('.' + checkedOpt + '  #iban').val(), pm);
            //
            //     // direct debit secured
            //     if(jQuery('#salutation').is(':visible')){
            //         // getting Values from input fields
            //         var birthDay = jQuery('select[name=Date_Day]').val();
            //         var birthMonth = jQuery('select[name=Date_Month]').val();
            //         var birthYear = jQuery('select[name=Date_Year]').val();
            //
            //         jQuery('#birthdate_dd').val(birthYear + '-' + birthMonth + '-' + birthDay);
            //
            //         errors = valDirectDebitSecured(errors);
            //     }
            // }
            //
            // if (pm == 'papg') {
            //     var dob = new Date(jQuery('.hgw_papg select[name="Date_Year"]').val(), jQuery('.hgw_papg select[name="Date_Month"]').val() - 1, jQuery('.hgw_papg select[name="Date_Day"]').val());
            //     var today = new Date();
            //     var age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
            //     var errors = valBirthdate(age);
            // }
            //
            // if(pm == 'san'){
            //     var errors = valSantander();
            //
            //     if(errors.length >0)
            //     {
            //         return false;
            //     }
            // }
            //
            // if(pm == 'ivpd'){
            //     var errors = valPayolutionDirect();
            //     if(errors.length >0)
            //     {
            //         return false;
            //     }
            // }
            switch (pm.toLocaleLowerCase()) {
                case "dd":
                    var errors = valInputDdIban(jQuery('.newreg_' + pm + ' #iban').val(), pm);
                    break;

                case "papg":
                    var dob = new Date(jQuery('.hgw_papg select[name="Date_Year"]').val(), jQuery('.hgw_papg select[name="Date_Month"]').val() - 1, jQuery('.hgw_papg select[name="Date_Day"]').val());
                    var today = new Date();
                    var age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                    var errors = valBirthdate(age);
                    break;

                case "san":
                    var errors = valSantander();
                    if(errors.length >0){return false;}
                    break;

                case "ivpd":
                    var errors = valPayolutionDirect();
                    if(errors.length >0){return false;}
                    break;

                case "hps":
                    var errors = valSantanderHP();
                // if(errors.length >0){return false;}
            }


        }

        if (
            (jQuery('div.hgw_' + pm + ' .has--error').length > 0)
            || (jQuery('div.newreg_' + pm + ' .has--error').length > 0)

        ) {
            if (jQuery('.content-main--inner .content .alert--content ul').length == 0) {
                jQuery('.content-main--inner .content .alert--content').html('<ul class="alert--list"></ul>');
            }

            jQuery('.content-main--inner .content .alert--content ul li').remove();
            jQuery('.content-main--inner .content .alert--content ul').append('<li class="list--entry">' + jQuery('.msg_fill').html() + '</li>');

            jQuery.each(errors, function (key, value) {
                jQuery('.content-main--inner .content .alert--content ul').append('<li class="list--entry">' + jQuery(value).html() + '</li>');
            });

            jQuery('.content-main--inner .alert').removeClass('is--hidden');
            jQuery('html, body').animate({scrollTop: 0}, 0);

            return false;
        } else {
            // disable all other input fields
            // jQuery('.payment--method-list input').attr('disabled', 'disabled');
            // jQuery('.payment--method-list select').attr('disabled', 'disabled');
            // jQuery('.payment--method-list input:radio:checked').parents('.payment--method').find('input').removeAttr('disabled');
            // jQuery('.payment--method-list input:radio:checked').parents('.payment--method').find('select').removeAttr('disabled');
            jQuery('.payment--method .block input').attr('disabled', 'disabled');
            jQuery('.payment--method .block select').attr('disabled', 'disabled');
            jQuery('.payment--method .block input:radio:checked').parents('.payment--method').find('input').removeAttr('disabled');
            jQuery('.payment--method .block input:radio:checked').parents('.payment--method').find('select').removeAttr('disabled');
        }
    }
}

function valInputDdIban(iban, pm) {
    var errors = {};
    var i = 0;

    iban = iban.trim();

    var regexIban = new RegExp('^[A-Z]{2}[0-9]{2}[a-zA-Z0-9]{11,30}$');

    if (iban.search(regexIban) == '-1') {
        jQuery('.newreg_' + pm + ' #iban').addClass('has--error');
        errors[i++] = '.msg_iban';
    } else {
        jQuery('.newreg_' + pm + ' #iban').val(iban);
        jQuery('.newreg_' + pm + ' #iban').removeClass('has--error');

        // jQuery('#birthdate_' + pm).removeClass('has--error');
    }

    jQuery('.newreg_' + pm + ' #account').removeClass('has--error');
    jQuery('.newreg_' + pm + ' #bankcode').removeClass('has--error');

    return errors;
}

function valInputDdAccount(acc, bank, pm) {
    var errors = {};
    var i = 0;

    acc = acc.trim();
    bank = bank.trim();

    var regexAcc = new RegExp('^[0-9]{6,16}$');
    var regexBank = new RegExp('^[0-9]{5,8}$');

    if (acc.search(regexAcc) == '-1') {
        jQuery('.newreg_' + pm + ' #account').addClass('has--error');
        errors[i++] = '.msg_account';
    } else {
        jQuery('.newreg_' + pm + ' #account').val(acc);
        jQuery('.newreg_' + pm + ' #account').removeClass('has--error');
    }

    if (bank.search(regexBank) == '-1') {
        jQuery('.newreg_' + pm + ' #bankcode').addClass('has--error');
        errors[i++] = '.msg_bank';
    } else {
        jQuery('.newreg_' + pm + ' #bankcode').val(bank);
        jQuery('.newreg_' + pm + ' #bankcode').removeClass('has--error');
    }

    jQuery('.newreg_' + pm + ' #iban').removeClass('has--error');
    return errors;
}

function valBirthdate(age) {
    var errors = {};
    var i = 0;

    if (age < 18) {
        jQuery('.hgw_papg select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_papg select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_papg select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
        errors[i++] = '.msg_dob';
    } else {
        jQuery('.hgw_papg select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
        jQuery('.hgw_papg select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
        jQuery('.hgw_papg select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
    }
    return errors;
}

function valSantander() {
    var errors = {};
    var i = 0;

    // validation of salutation
    var salutation = $('.newreg_san select[name="NAME.SALUTATION"]').val();
    if(salutation == undefined || salutation == "UNKNOWN")
    {
        $('.newreg_san #salutation').parent('.js--fancy-select').addClass("has--error");
        $('.newreg_san #salutation').addClass("has--error");
        errors[i++] = '.msg_salut';
    } else {
        $('.newreg_san #salutation').parent('.js--fancy-select').removeClass('has--error');
        $('.newreg_san #salutation').removeClass('has--error');
    }

    // validation of birthdate
    // var birthdate = $('#birthdate_san').val();
    var birthdate = jQuery('.hgw_san select[name="Date_Year"]').val()+"-"+jQuery('.hgw_san select[name="Date_Month"]').val()+"-"+jQuery('.hgw_san select[name="Date_Day"]').val();
    jQuery('#birthdate_san').val(birthdate);
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {
        var dob = new Date(jQuery('.hgw_san select[name="Date_Year"]').val(), jQuery('.hgw_san select[name="Date_Month"]').val()-1, jQuery('.hgw_san select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
        if(age < 18){

            jQuery('.hgw_san select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.hgw_san select[name="Date_Year"]').addClass('has--error');
            jQuery('.hgw_san select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.hgw_san select[name="Date_Month"]').addClass('has--error');
            jQuery('.hgw_san select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.hgw_san select[name="Date_Day"]').addClass('has--error');

            errors[i++] = '.msg_dob';
        }else{
            jQuery('.hgw_san select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.hgw_san select[name="Date_Year"]').removeClass('has--error');
            jQuery('.hgw_san select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.hgw_san select[name="Date_Month"]').removeClass('has--error');
            jQuery('.hgw_san select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.hgw_san select[name="Date_Day"]').removeClass('has--error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.hgw_san select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_san select[name="Date_Year"]').addClass('has--error');
        jQuery('.hgw_san select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_san select[name="Date_Month"]').addClass('has--error');
        jQuery('.hgw_san select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_san select[name="Date_Day"]').addClass('has--error');
        errors[i++] = '.msg_dob';
    }

    // validation of privacy policy
    if($(".hgw_san #hgw_privacyPolicy").is(':checked'))
    {
        $(".hgw_san #hgw_privacyPolicy").removeClass('has--error');
    } else {
        $(".hgw_san #hgw_privacyPolicy").addClass('has--error');
        $(".hgw_san #hgw_privacyPolicy").attr("required","required");

        errors[i++] = '.msg_cb';
    }
    return errors;
}

function valPayolutionDirect() {
    var errors = {};
    var i = 0;
    // validation of salutation
    var salutation = $('.newreg_ivpd select[name="NAME.SALUTATION"]').val();
    if(salutation == undefined || salutation == "UNKNOWN")
    {
        $('.newreg_ivpd #salutation').parent('.js--fancy-select').addClass("has--error");
        errors[i++] = '.msg_salut';
    } else {
        $('.newreg_ivpd #salutation').parent('.js--fancy-select').removeClass('has--error');
    }

    // validation of birthdate
    var birthdate = $('#birthdate_ivpd').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {
        var dob = new Date(jQuery('.hgw_ivpd select[name="Date_Year"]').val(), jQuery('.hgw_ivpd select[name="Date_Month"]').val()-1, jQuery('.hgw_ivpd select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
        if(age < 18){

            jQuery('.hgw_ivpd select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Year"]').addClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Month"]').addClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Day"]').addClass('has--error');

            errors[i++] = '.msg_dob';
        }else{
            jQuery('.hgw_ivpd select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Year"]').removeClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Month"]').removeClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.hgw_ivpd select[name="Date_Day"]').removeClass('has--error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.hgw_ivpd select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_ivpd select[name="Date_Year"]').addClass('has--error');
        jQuery('.hgw_ivpd select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_ivpd select[name="Date_Month"]').addClass('has--error');
        jQuery('.hgw_ivpd select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.hgw_ivpd select[name="Date_Day"]').addClass('has--error');
        errors[i++] = '.msg_dob';
    }

    //validation of Checkbox
    if(document.getElementById("hgw_privpol_ivpd").checked){
        $('#hgw_privpol_ivpd').removeAttr("required");
    } else {
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

function valDirectDebitSecured(errors) {
    var i = 1;
    // validation of salutation
    var salutation = $('.newreg_dd #salutation').val();
    if(salutation == undefined || salutation == "UNKNOWN")
    {
        $('.newreg_dd #salutation').parent('.js--fancy-select').addClass("has--error");
        errors[i++] = '.msg_salut';
    } else {
        $('.newreg_dd #salutation').parent('.js--fancy-select').removeClass('has--error');
    }

    //validation of birthdate
    var birthdate = $('#birthdate_dd').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {
        var dob = new Date(jQuery('.newreg_dd select[name="Date_Year"]').val(), jQuery('.newreg_dd select[name="Date_Month"]').val()-1, jQuery('.newreg_dd select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
        if(age < 18){

            jQuery('.newreg_dd select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_dd select[name="Date_Year"]').addClass('has--error');
            jQuery('.newreg_dd select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_dd select[name="Date_Month"]').addClass('has--error');
            jQuery('.newreg_dd select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_dd select[name="Date_Day"]').addClass('has--error');

            errors[i++] = '.msg_dob';
        }else{
            jQuery('.newreg_dd select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_dd select[name="Date_Year"]').removeClass('has--error');
            jQuery('.newreg_dd select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_dd select[name="Date_Month"]').removeClass('has--error');
            jQuery('.newreg_dd select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_dd select[name="Date_Day"]').removeClass('has--error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.newreg_dd select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_dd select[name="Date_Year"]').addClass('has--error');
        jQuery('.newreg_dd select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_dd select[name="Date_Month"]').addClass('has--error');
        jQuery('.newreg_dd select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_dd select[name="Date_Day"]').addClass('has--error');
        errors[i++] = '.msg_dob';
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
        $('.newreg_papg #salutation').parent('.js--fancy-select').addClass("has--error");
        errors[i++] = '.msg_salut';
    } else {
        $('.newreg_papg #salutation').parent('.js--fancy-select').removeClass('has--error');
    }

    // validation of birthdate
    var birthdate = $('#birthdate_papg').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {

        var dob = new Date(jQuery('.newreg_papg select[name="Date_Year"]').val(), jQuery('.newreg_papg select[name="Date_Month"]').val()-1, jQuery('.newreg_papg select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));

        if(age < 18){
            jQuery('.newreg_papg select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_papg select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_papg select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
            errors[i++] = '.msg_dob';
        }else{
            jQuery('.newreg_papg select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_papg select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_papg select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.newreg_papg select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_papg select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_papg select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
        errors[i++] = '.msg_dob';
    }

    if(errors.length > 0){
        return errors;
    }
}

/**
 * valSantanderHP
 * Function to validate Santander Hire purchace
 */
function valSantanderHP() {
    var errors = new Array();
    var i = 0;

    var birthdate = $('#birthdate_sanHps').val();
    if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
    {

        var dob = new Date(jQuery('.newreg_hps select[name="Date_Year"]').val(), jQuery('.newreg_hps select[name="Date_Month"]').val()-1, jQuery('.newreg_hps select[name="Date_Day"]').val());
        var today = new Date();
        var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));

        if(age < 18){
            jQuery('.newreg_hps select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_hps select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_hps select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
            errors[i++] = '.msg_dob';
        }else{
            jQuery('.newreg_hps select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_hps select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
            jQuery('.newreg_hps select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
        }
    } else {
        //birthdate doesn't fit to formate YYYY-MM-DD
        jQuery('.newreg_hps select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_hps select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
        jQuery('.newreg_hps select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
        errors[i++] = '.msg_dob';
    }
    if(errors.length > 0){
        return errors;
    }

}

/**
 * valInvoiceB2B
 * Validates the Inputs of B2B, returns and marks missing fields
 * @return {errors[]}
 */
function valInvoiceB2b() {
    var errors = new Array();
    var i = 0;
    // checking Company name
    var heidelCompanyName = $('.newreg_ivb2b #heidelb2bCompanyName').val();
    if(heidelCompanyName == '' || heidelCompanyName == "undefined"){
        $('.newreg_ivb2b #heidelb2bCompanyName').prop('class','has--error');
        errors[i++] = '.msg_fill';
    }
    // checking Company Street
    var heidelCompanyStreet = $('.newreg_ivb2b #heidelb2bCompanyStreet').val();
    if(heidelCompanyStreet == '' || heidelCompanyStreet == "undefined"){
        $('.newreg_ivb2b #heidelCompanyStreet').prop('class','has--error');
        errors[i] = '.msg_fill';
    }
    // checking Company Zip
    var heidelb2bCompanyZip = $('.newreg_ivb2b #heidelb2bCompanyZip').val();
    if(heidelb2bCompanyZip == '' || heidelb2bCompanyZip == "undefined"){
        $('.newreg_ivb2b #heidelb2bCompanyZip').prop('class','has--error');
        errors[i] = '.msg_fill';
    }
    // checking Company City
    var heidelb2bCompanyCity = $('.newreg_ivb2b #heidelb2bCompanyCity').val();
    if(heidelb2bCompanyCity == '' || heidelb2bCompanyCity == "undefined"){
        $('.newreg_ivb2b #heidelb2bCompanyCity').prop('class','has--error');
        errors[i] = '.msg_fill';
    }

    // checking depending registered fields
    // if($('.newreg_ivb2b #heidelB2bCompanyRegistered :checked').val()== "REGISTERED"){
    if($('.newreg_ivb2b input:checked').val()== "REGISTERED"){
        // checking Company Commercial registernuimber
        var heidelb2bCompanyRegisterNr = $('.newreg_ivb2b #heidelb2bCompanyRegisterNr').val();
        if(heidelb2bCompanyRegisterNr == '' || heidelb2bCompanyRegisterNr == "undefined"){
            $('.newreg_ivb2b #heidelb2bCompanyRegisterNr').prop('class','has--error');
            errors[i] = '.msg_fill';
        }
        $('.newreg_ivb2b #heidelb2bCompanyUstNr').removeClass('has--error');
        $('.newreg_ivb2b #B2Bsalutation').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bPreName').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bLastName').removeClass('has--error');
        $('.newreg_ivb2b #birthdate_ivb2b').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bEmail').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bExePhone').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bExeStreet').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bExeZip').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bExeCity').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bExeCountry').removeClass('has--error');
        // $('.newreg_ivb2b [name="Date_Day"]').prop('required','');
        // $('.newreg_ivb2b [name="Date_Month"]').prop('required','');
        // $('.newreg_ivb2b [name="Date_Year"]').prop('required','');
        console.log('1351');
    } else {
        // checking Executive Prename
        var heidelb2bPreName = $('.newreg_ivb2b #heidelb2bPreName').val();
        if(heidelb2bPreName == '' || heidelb2bPreName == "undefined"){
            $('.newreg_ivb2b #heidelb2bPreName').prop('class','has--error');
            errors[i] = '.msg_fill';
        }

        // checking Executive Lastname
        var heidelb2bLastName = $('.newreg_ivb2b #heidelb2bLastName').val();
        if(heidelb2bLastName == '' || heidelb2bLastName == "undefined"){
            $('.newreg_ivb2b #heidelb2bLastName').prop('class','has--error');
            errors[i] = '.msg_fill';
        }

        // checking Executive Email
        var heidelb2bEmail = $('.newreg_ivb2b #heidelb2bEmail').val();
        if(heidelb2bLastName == '' || heidelb2bLastName == "undefined"){
            $('.newreg_ivb2b #heidelb2bEmail').prop('class','has--error');
            errors[i] = '.msg_fill';
        }

        // checking Executive Street
        var heidelb2bExeStreet = $('.newreg_ivb2b #heidelb2bExeStreet').val();
        if(heidelb2bExeStreet == '' || heidelb2bExeStreet == "undefined"){
            $('.newreg_ivb2b #heidelb2bExeStreet').prop('class','has--error');
            errors[i] = '.msg_fill';
        }

        // checking Executive Zip
        var heidelb2bExeZip = $('.newreg_ivb2b #heidelb2bExeZip').val();
        if(heidelb2bExeZip == '' || heidelb2bExeZip == "undefined"){
            $('.newreg_ivb2b #heidelb2bExeZip').prop('class','has--error');
            errors[i] = '.msg_fill';
        }

        // checking Executive City
        var heidelb2bExeCity = $('.newreg_ivb2b #heidelb2bExeCity').val();
        if(heidelb2bExeCity == '' || heidelb2bExeCity == "undefined"){
            $('.newreg_ivb2b #heidelb2bExeCity').prop('class','has--error');
            errors[i] = '.msg_fill';
        }

        var birthdateIvB2b = $('.newreg_ivb2b [name="Date_Year"]').val() + '-'+ $('.newreg_ivb2b [name="Date_Month"]').val() + '-'+ $('.newreg_ivb2b [name="Date_Day"]').val();
        $('#birthdate_ivb2b').val($('.newreg_ivb2b [name="Date_Year"]').val() + '-'+ $('.newreg_ivb2b [name="Date_Month"]').val() + '-'+ $('.newreg_ivb2b [name="Date_Day"]').val());

        var birthdate = birthdateIvB2b;
        if(birthdate.match(/[0-9]{4}[-][0-9]{2}[-][0-9]{2}/))
        {
            var dob = new Date(jQuery('.newreg_ivb2b select[name="Date_Year"]').val(), jQuery('.newreg_ivb2b select[name="Date_Month"]').val()-1, jQuery('.newreg_ivb2b select[name="Date_Day"]').val());
            var today = new Date();
            var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
            if(age < 18){

                jQuery('.newreg_ivb2b select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Year"]').prop('class','has--error');
                jQuery('.newreg_ivb2b select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Month"]').prop('class','has--error');
                jQuery('.newreg_ivb2b select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Day"]').prop('class','has--error');

                errors[i++] = '.msg_dob';
            }else{
                jQuery('.newreg_ivb2b select[name="Date_Year"]').parent('.js--fancy-select').removeClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Year"]').removeClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Month"]').parent('.js--fancy-select').removeClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Month"]').removeClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Day"]').parent('.js--fancy-select').removeClass('has--error');
                jQuery('.newreg_ivb2b select[name="Date_Day"]').removeClass('has--error');
            }
        } else {
            //birthdate doesn't match to formate YYYY-MM-DD
            jQuery('.newreg_ivb2b select[name="Date_Year"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_ivb2b select[name="Date_Year"]').prop('class','has--error');
            jQuery('.newreg_ivb2b select[name="Date_Month"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_ivb2b select[name="Date_Month"]').prop('class','has--error');
            jQuery('.newreg_ivb2b select[name="Date_Day"]').parent('.js--fancy-select').addClass('has--error');
            jQuery('.newreg_ivb2b select[name="Date_Day"]').prop('class','has--error');
            errors[i++] = '.msg_dob';
        }
        // remove errors from unsed inputs
        $('.newreg_ivb2b #heidelb2bCompanyRegisterNr').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bExePhone').removeClass('has--error');
        $('.newreg_ivb2b #heidelb2bCompanyUstNr').removeClass('has--error');

    }
    //removing error marks for not necessary inputs
    $('.newreg_ivb2b #heidelb2bCompanyPobox').removeClass('has--error');
    return errors;
}

/**
 * valPhoneNumber
 * in case of valid Number this functins returns a cleaned telephonenumber in format 004912345678
 * @param telephoneNumber
 * @return bool
 */
function valPhoneNumber(telephoneNumber) {
    // checks weather function gets a phonenumber
    if (telephoneNumber.length === 0) {
        return false;
    } else {
        //preparing phonenumber-string to check it
        //removes all Whitespaces in telephonenumber
        var regEx = / /g;
        var cleanedPhoneNumber = telephoneNumber.replace(regEx, "");
        //removes all "+" in telephonenumber
        regEx = /\+/g;
        cleanedPhoneNumber = cleanedPhoneNumber.replace(regEx, "00");
        //removes all "/" in telephonenumber
        regEx = /\//g;
        cleanedPhoneNumber = cleanedPhoneNumber.replace(regEx, "");
        //removes all "-" in telephonenumber
        regEx = /\-/g;
        cleanedPhoneNumber = cleanedPhoneNumber.replace(regEx, "");
        //removes all "(" in telephonenumber
        regEx = /\(/g;
        cleanedPhoneNumber = cleanedPhoneNumber.replace(regEx, "");
        //removes all ")" in telephonenumber
        regEx = /\)/g;
        cleanedPhoneNumber = cleanedPhoneNumber.replace(regEx, "");

        if (cleanedPhoneNumber.match(/[0-9]{6,20}/)) {
            return cleanedPhoneNumber;
        } else {
            return false;
        }
    }

}



