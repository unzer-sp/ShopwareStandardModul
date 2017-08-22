$(document).ready(function(){
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
	if((window.location.pathname.indexOf('account/payment') >= '0') || (window.location.pathname.indexOf('account/savePayment') >= '0')){
		// ACCOUNT/PAYMENT
		var errorDiv = '#center .error';

		// check if payment selection is changed
		$('.payment_method').click(function(){
			// change form action
			checkedOpt = $('.payment_method input:radio:checked');
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
//		$('.payment_method').trigger('click');
		$('.payment_method input:radio:checked').trigger('click');
		
	}else if(window.location.pathname.indexOf('gateway') >= '0'){
		// GATEWAY
		var errorDiv = '#payment .error';
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
		if((pm == 'cc') || (pm == 'dc')){
			// just use eventListener on new registration or debit
			if(jQuery('.newreg_'+pm).is(':visible')){
				$($.loadingIndicator.config.overlay).fadeTo($.loadingIndicator.config.animationSpeed, $.loadingIndicator.config.overlayOpacity);
				$.loadingIndicator.open();
				$(paymentFrameForm).find('input[type="submit"]').attr('disabled', 'disabled');
				var checkedClass = checkedOpt.attr('class');
				
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

					if(activePm == pm){
						// disable all other input fields
						jQuery('.payment_method input').attr('disabled', 'disabled');
						jQuery('.payment_method select').attr('disabled', 'disabled');
						jQuery('.payment_method input:radio:checked').parents('.grid_15').find('input').removeAttr('disabled');
						jQuery('.payment_method input:radio:checked').parents('.grid_15').find('select').removeAttr('disabled');
						// save the form data in an object
						var data = {};
						for(var i = 0, len = paymentFrameForm.length; i < len; ++i){
							var input = paymentFrameForm[i];
							if(input.name && !input.disabled){
								data[input.name] = input.value;
							}
						}
						// send a json message with the form data to the iFrame receiver window.
						paymentFrameIframe.contentWindow.postMessage(JSON.stringify(data), targetOrigin);
					}
				}
			}
		}
	}
	
	// a function to receive a postMessages from iFrame
	function receiveMessage(e, origEvent, targetOrigin, paymentFrameForm, checkedOpt){
		// Check to make sure that this message came from the correct domain
		if(e.origin !== targetOrigin){
			console.log(e.origin+' !== '+targetOrigin);
			return;
		}
		
		var recMsg = JSON.parse(e.data);
		if(recMsg["POST.VALIDATION"] == 'NOK'){
			// enable all input fields
			jQuery('.payment_method input').removeAttr('disabled');
			jQuery('.payment_method select').removeAttr('disabled');
						
			var errors = {};
			var errorExp = false;
			setTimeout(function(){
				$($.loadingIndicator.config.overlay).fadeOut($.loadingIndicator.config.animationSpeed);
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
				jQuery(errorDiv).html('<ul></ul>');
			}
			
			jQuery(errorDiv+' ul li').remove();
			jQuery(errorDiv+' ul').append('<li>'+jQuery('.msg_fill').html()+'</li>');
			
			jQuery.each(errors, function(key, value){
				if(key == 'text'){
					jQuery(errorDiv+' ul').append('<li>'+value+'</li>');
				}else{			
					jQuery(errorDiv+' ul').append('<li>'+jQuery(value).html()+'</li>');
				}
			});

			jQuery(errorDiv).removeClass('is--hidden');
			jQuery(errorDiv).show();
			jQuery('html, body').animate({scrollTop: 0}, 0);
			return false;
		}else{
			// disable all other input fields
			jQuery(errorDiv).fadeOut();
			jQuery('.payment_method input').attr('disabled', 'disabled');
			jQuery('.payment_method select').attr('disabled', 'disabled');
			jQuery('.payment_method input:radio:checked').parents('.grid_15').find('input').removeAttr('disabled');
			jQuery('.payment_method input:radio:checked').parents('.grid_15').find('select').removeAttr('disabled');
		}
	}

	// function to extract protocol, domain and port from url
	function getDomainFromUrl(url){
		var arr = url.split("/");
		return arr[0] + "//" + arr[2];
	}
});