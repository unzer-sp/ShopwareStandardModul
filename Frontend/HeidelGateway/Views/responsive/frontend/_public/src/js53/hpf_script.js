document.asyncReady(function() {
    var hasListener = [];
    hasListener['dc'] = false;
    hasListener['cc'] = false;
    hasListener['msg'] = false;

    var origEvent = '';
    var pm = '';
    var targetOrigin = '';
    var paymentFrameForm = '';
    var paymentFrameIframe = '';

    var checkedOpt = '';

    var sendHandler = function(e){
        origEvent = e;
        sendMessage(e, pm, targetOrigin, paymentFrameForm, paymentFrameIframe, checkedOpt);
    }

    // PATH SWITCH
    if(window.location.pathname.indexOf('account/payment') >= '0'){
console.log("ACCOUNT");
        // ACCOUNT/PAYMENT
        var errorDiv = '#center .alert .alert--content';

        // check if payment selection is changed
        $('.register--payment').click(function(){
            // change form action
            checkedOpt = $('.register--payment input:radio:checked');

            var checkedClass = checkedOpt.attr('class');
            if(typeof checkedClass != 'undefined'){
                var prefix = 'hgw_';
                var checkedClassPos = checkedClass.indexOf(prefix);
                if(checkedClassPos >= 0){
                    pm = checkedClass.substr(checkedClassPos+prefix.length);
                    if(((pm.toLowerCase() == 'cc') || (pm.toLowerCase() == 'dc')) && $('#hp_frame_'+pm).length > 0){
                        // get the target origin from the FRONTEND.PAYMENT_FRAME_URL parameter
                        targetOrigin = getDomainFromUrl($('#hp_frame_'+pm).attr('src'));
                        paymentFrameForm = document.getElementsByName('frmRegister');
                        paymentFrameIframe = document.getElementById('hp_frame_'+pm);
                        // get right element from nodelist
                        for(var i = 0; i < paymentFrameForm.length; i++){
                            var item = paymentFrameForm[i];
                            if((item.className == 'payment') && (item.tagName.toLowerCase() == 'form')){
                                paymentFrameForm = paymentFrameForm[i];
                                break;
                            }
                        }

                        if(!hasListener[pm]){
                            setSubmitListener();
console.log("SubmitListener Set");
                            hasListener[pm] = true;
                        }

                        if(!hasListener['msg']){
                            setMessageListener();
                            hasListener['msg'] = true;
                        }
                    }
                }else{ pm = ''; }
            }
        });
        // trigger 'click' on document.ready() to get functionality if paymet method is preselected
        $('.register--payment').trigger('click');

    }else if(window.location.pathname.indexOf('gateway') >= '0'){
        // GATEWAY
        console.log("GATEWAY")
        var errorDiv = '#payment .alert .alert--content';

        checkedOpt = $('#payment .payment_method');
        var checkedClass = checkedOpt.attr('class');

        if(typeof checkedClass != 'undefined'){
            var prefix = 'hgw_';
            var checkedClassPos = checkedClass.indexOf(prefix);

            if(checkedClassPos >= 0){
                pm = checkedClass.substr(checkedClassPos+prefix.length);

                if(((pm.toLowerCase() == 'cc') || (pm.toLowerCase() == 'dc')) && $('#hp_frame_'+pm).length > 0){
                    // get the target origin from the FRONTEND.PAYMENT_FRAME_URL parameter
                    targetOrigin = getDomainFromUrl($('#hp_frame_'+pm).attr('src'));
                    paymentFrameForm = document.getElementsByName('heidelpay');
                    paymentFrameIframe = document.getElementById('hp_frame_'+pm);
                    // get right element from nodelist
                    for(var i = 0; i < paymentFrameForm.length; i++){
                        var item = paymentFrameForm[i];
                        if((item.className == 'payment') && (item.tagName.toLowerCase() == 'form')){
                            paymentFrameForm = paymentFrameForm[i];
                            break;
                        }
                    }

                    if(!hasListener[pm]){
                        setSubmitListener();
                        hasListener[pm] = true;
                    }

                    if(!hasListener['msg']){
                        setMessageListener();
                        hasListener['msg'] = true;
                    }
                }
            }else{ pm = ''; }
        }
    }else if(window.location.pathname.indexOf('shippingPayment') >= '0'){
        // SHIPPINGPAYMENT
console.log("ShippingPayment");
console.log("Kein CC / DC vorausgewählt");
        var errorDiv = '.content-main--inner .content .alert .alert--content';

        /* ************************************** */
        // wenn CC / DC schon vorausgewählt war
        hasListener['dc'] = false;
        hasListener['cc'] = false;

        checkedOpt = jQuery('.payment--method-list input:radio:checked');
        var checkedClass = checkedOpt.attr('class');

        if(typeof checkedClass != 'undefined') {
            var prefix = 'hgw_';
            var checkedClassPos = checkedClass.indexOf(prefix);

            if(checkedClassPos >= 0) {
                pm = checkedClass.substr(checkedClassPos + prefix.length);

                callAFunction(pm);

                if(((pm.toLowerCase() == 'cc') || (pm.toLowerCase() == 'dc')) && $('#hp_frame_'+pm).length > 0){
                    // get the target origin from the FRONTEND.PAYMENT_FRAME_URL parameter
                    targetOrigin = getDomainFromUrl($('#hp_frame_'+pm).attr('src'));
                    paymentFrameForm = document.getElementsByName('shippingPaymentForm');
                    paymentFrameIframe = document.getElementById('hp_frame_'+pm);
                    // get right element from nodelist
                    for(var i = 0; i < paymentFrameForm.length; i++){
                        var item = paymentFrameForm[i];
                        if((item.className == 'payment') && (item.tagName.toLowerCase() == 'form')){
                            paymentFrameForm = paymentFrameForm[i];
                            break;
                        }
                    }

                    if(!hasListener[pm]){
                        setSubmitListener();
                        hasListener[pm] = true;
                    }

                    if(!hasListener['msg']){
                        setMessageListener();
                        hasListener['msg'] = true;
                    }
                }
            }
            console.log("Kein CC / DC vorausgewählt durch 156");
        } else{ pm = ''; }
        /* ************************************** */
        // reset the flags for the frame listener, because event bindings are deleted due to ajax
        // 'msg' flag don't need a reset because the listener is on the window
        // wenn CC / DC erst auf Shipping-Payment ausgewählt wird (Nach Reload der Seite)
        $( document ).ajaxComplete(function() {
            hasListener['dc'] = false;
            hasListener['cc'] = false;

            checkedOpt = jQuery('.payment--method-list input:radio:checked');
            var checkedClass = checkedOpt.attr('class');
            if(typeof checkedClass != 'undefined'){
                var prefix = 'hgw_';
                var checkedClassPos = checkedClass.indexOf(prefix);

                if(checkedClassPos >= 0){
                    pm = checkedClass.substr(checkedClassPos+prefix.length);

                   if(((pm.toLowerCase() == 'cc') || (pm.toLowerCase() == 'dc')) && $('#hp_frame_'+pm).length > 0){
console.log("ajaxComplete CC / DC vorausgewählt");
                       callAFunction(pm);
                        // get the target origin from the FRONTEND.PAYMENT_FRAME_URL parameter
                        targetOrigin = getDomainFromUrl($('#hp_frame_'+pm).attr('src'));
                        paymentFrameForm = document.getElementsByName('shippingPaymentForm');
                        paymentFrameIframe = document.getElementById('hp_frame_'+pm);
                        // get right element from nodelist
                        for(var i = 0; i < paymentFrameForm.length; i++){
                            var item = paymentFrameForm[i];
                            if((item.className == 'payment') && (item.tagName.toLowerCase() == 'form')){
                                paymentFrameForm = paymentFrameForm[i];
                                break;
                            }
                        }

                        if(!hasListener[pm]){
                            setSubmitListener();
                            hasListener[pm] = true;
                        }

                        if(!hasListener['msg']){
                            setMessageListener();
                            hasListener['msg'] = true;
                        }
    console.log("ajaxComplete durch 200");
                    }
                }else{ pm = ''; }
            }

        });
    }



    // add an event listener that will execute the sendMessage() function when the send button is clicked.
    function setSubmitListener(){
        if(paymentFrameForm.addEventListener){ // W3C DOM
            paymentFrameForm.addEventListener('submit', sendHandler);
        }else if(paymentFrameForm.attachEvent){ // IE DOM
            paymentFrameForm.attachEvent('onsubmit', sendHandler);
        }
    }

    // setup an event listener that calls receiveMessage() when the window receives a new MessageEvent
    function setMessageListener(){
        if(window.addEventListener){ // W3C DOM
            window.addEventListener('message', function(e){ receiveMessage(e, origEvent, targetOrigin, paymentFrameForm, checkedOpt); });
        }else if(window.attachEvent){ // IE DOM
            window.attachEvent('onmessage', function(e){ receiveMessage(e, origEvent, targetOrigin, paymentFrameForm, checkedOpt); });
        }
    }

    // a function to handle sending messages via postMessage to iFrame
    function sendMessage(e, pm, targetOrigin, paymentFrameForm, paymentFrameIframe, checkedOpt){
console.log("sendMessage START");
    	if((pm == 'cc') || (pm == 'dc')){
console.log("186");
            // just use eventListener on new registration or debit
            if(jQuery('.newreg_'+pm).is(':visible')){
                $.overlay.open();
                $.loadingIndicator.open({ animationSpeed: 50 });
                $(paymentFrameForm).find('input[type="submit"]').attr('disabled', 'disabled');
                // var checkedClass = checkedOpt.attr('class');
                checkedClass = jQuery('.payment--method-list input:radio:checked').attr('class');
console.log("194");
console.log(checkedClass)
                if(typeof checkedClass == 'undefined'){
                    //for ShippingPayment
                    checkedClass = jQuery('.payment_method').attr('class');
                }
                if(typeof checkedClass == 'undefined'){
                    // for Account-page
                    checkedClass = jQuery('.payment--selection-input input:radio:checked').attr('class')
                }


console.log(checkedClass)
                if(typeof checkedClass != 'undefined'){
                    var prefix = 'hgw_';
                    var checkedClassPos = checkedClass.indexOf(prefix);
                    var activePm = checkedClass.substr(checkedClassPos+prefix.length);

                    // prevent any default browser behaviour
                    if(e.preventDefault){
                        e.preventDefault();
                    }else{
                        e.returnValue = false;
                    }
console.log("210");
                    if(activePm == pm){
                        // disable all other input fields
                        jQuery('.payment--method input').attr('disabled', 'disabled');
                        jQuery('.payment--method select').attr('disabled', 'disabled');
                        // jQuery(checkedOpt).parents('.payment--method').find('input').removeAttr('disabled');
                        jQuery(".hgw_"+activePm).parents('.payment--method').find('input').removeAttr('disabled');
                        // jQuery(checkedOpt).parents('.payment--method').find('select').removeAttr('disabled');
                        jQuery(".hgw_"+activePm).parents('.payment--method').find('select').removeAttr('disabled');
                        // save the form data in an object
                        // var data = {};
                        var data = new Object();
                        for(var i = 0, len = paymentFrameForm.length; i < len; ++i){
                            var input = paymentFrameForm[i];
                            if(input.name && !input.disabled){
                                data[input.name] = input.value;
                            }
                        }
console.log(data);
                        paymentFrameIframe.contentWindow.postMessage(JSON.stringify(data), targetOrigin);
                    }
                } else {
console.log("checkedClass UNDEFINED")
                }
            }
        }
    }

    /* ********************************* */
    function triggerListeners() {
        setSubmitListener();
        setMessageListener();
    }
    /* ********************************* */

    // a function to receive a postMessages from iFrame
    function receiveMessage(e, origEvent, targetOrigin, paymentFrameForm, checkedOpt){
    	// Check to make sure that this message came from the correct domain
        if(e.origin !== targetOrigin){
            return;
        }

        var recMsg = JSON.parse(e.data);

        if(recMsg["POST.VALIDATION"] == 'NOK'){
            // enable all input fields
            jQuery('.payment--method input').removeAttr('disabled');
            jQuery('.payment--method select').removeAttr('disabled');

            var errors = {};
            var errorExp = false;
            setTimeout(function(){
                $.overlay.close();
                $.loadingIndicator.close();
            }, 250);

            $(paymentFrameForm).find('input[type="submit"]').removeAttr('disabled');

            if(typeof recMsg["PROCESSING.RETURN"] != 'undefined'){
                var i = 1;
                var nokReturn = recMsg["PROCESSING.RETURN"];
            }
        }else if((recMsg["PROCESSING.RESULT"] == 'ACK') && (recMsg["FRONTEND.PREVENT_ASYNC_REDIRECT"].toLowerCase() == 'true')){
            // remove event listener
            if(paymentFrameForm.removeEventListener){ // W3C DOM
                paymentFrameForm.removeEventListener('submit', sendHandler);
            }else if(paymentFrameForm.detachEvent){ // IE DOM
                paymentFrameForm.detachEvent('onsubmit', sendHandler);
            }

            // do default action
            $(paymentFrameForm).trigger(origEvent.type);
        }

        // error handling
        var errorParams = [];
        if(typeof recMsg["PROCESSING.MISSING.PARAMETERS"] != 'undefined'){
            errorParams = errorParams.concat(recMsg["PROCESSING.MISSING.PARAMETERS"]);
        }
        if(typeof recMsg["PROCESSING.WRONG.PARAMETERS"] != 'undefined'){
            errorParams = errorParams.concat(recMsg["PROCESSING.WRONG.PARAMETERS"]);
        }

        if(errorParams.length > 0){
            for(var i = 0; i < errorParams.length; i++){
                if(errorParams[i] == 'account.number'){
                    errors[i] = '.msg_crdnr';
                }
                if(((errorParams[i] == 'account.expiry_month') || (errorParams[i] == 'account.expiry_year')) && !errorExp){
                    errorExp = true;
                    errors[i] = '.msg_exp';
                }
            }
        }else if(typeof nokReturn != 'undefined'){
            errors['text'] = nokReturn;
        }

        if(i > 0){
            if(jQuery(errorDiv+' ul').length == 0){
                jQuery(errorDiv).html('<ul class="alert--list"></ul>');
            }

            jQuery(errorDiv+' ul li').remove();
            jQuery(errorDiv+' ul').append('<li class="list--entry">'+jQuery('.msg_fill').html()+'</li>');

            jQuery.each(errors, function(key, value){
                if(key == 'text'){
                    jQuery(errorDiv+' ul').append('<li class="list--entry">'+value+'</li>');
                }else{
                    jQuery(errorDiv+' ul').append('<li class="list--entry">'+jQuery(value).html()+'</li>');
                }
            });

            jQuery(errorDiv).parent().removeClass('is--hidden');
            jQuery(errorDiv).parent().show();
            jQuery('html, body').animate({scrollTop: 0}, 0);
            return false;
        }else{
            // disable all other input fields
            jQuery(errorDiv).parent().fadeOut();
            jQuery('.payment--method input').attr('disabled', 'disabled');
            jQuery('.payment--method select').attr('disabled', 'disabled');
            jQuery(checkedOpt).parents('.payment--method').find('input').removeAttr('disabled');
            jQuery(checkedOpt).parents('.payment--method').find('select').removeAttr('disabled');
        }
    }

    // function to extract protocol, domain and port from url
    function getDomainFromUrl(url){
        var arr = url.split("/");
        return arr[0] + "//" + arr[2];
    }

    function callAFunction(payMeth) {
        pm = payMeth;
console.log("callAFunction");
        if(((payMeth.toLowerCase() == 'cc') || (payMeth.toLowerCase() == 'dc')) && $('#hp_frame_'+payMeth).length > 0){
            // get the target origin from the FRONTEND.PAYMENT_FRAME_URL parameter
            targetOrigin = getDomainFromUrl($('#hp_frame_'+payMeth).attr('src'));
            paymentFrameForm = document.getElementsByName('shippingPaymentForm');
            paymentFrameIframe = document.getElementById('hp_frame_'+payMeth);
            checkedOpt = jQuery("method--input input:radio:checked");

            // get right element from nodelist
            for(var i = 0; i < paymentFrameForm.length; i++){
                var item = paymentFrameForm[i];
                if((item.className == 'payment') && (item.tagName.toLowerCase() == 'form')){
                    paymentFrameForm = paymentFrameForm[i];
                    break;
                }
            }

            if(!hasListener[pm]){
                setSubmitListener();
                hasListener[pm] = true;
            }

            if(!hasListener['msg']){
                setMessageListener();
                hasListener['msg'] = true;
            }
        }
    }
});