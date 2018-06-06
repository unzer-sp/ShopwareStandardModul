<?php
/**
 * Heidelpay
*
* @category Shopware
* @package Shopware_Plugins
* @subpackage Plugin
* @link http://www.heidelpay.com
* @copyright Copyright (c) 2018, heidelpay GmbH
* @author Jens Richter / Sascha Pflueger
*/
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_PaymentHgw extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware{
	var $dbtable = '';
	var $curl_response = '';
	var $error = '';
	var $httpstatus = '';

	/**
	 * Index action method
	 */
	public function indexAction(){
		try{
			if($this->Config()->HGW_DEBUG > 0){
				print "<div style='font-family: arial; font-size: 13px;'><h1>HGW Controller</h1>";
				print "<h2>Debug Mode</h2><br />";
				print "PaymentShortName: <b>".$this->getPaymentShortName();
				print '</b><br /><br /><a href="'.$this->Front()->Router()->assemble(array(
						'action' => 'gateway',
						'forceSecure' => 1
				)).'">Weiter zu Request / Response</a></div>';
				die();
			}

			$avaliblePayment = $this->hgw()->paymentMethod();
			$Payment = array();
			foreach ($avaliblePayment as $key => $value){
				$Payment[] = $avaliblePayment[$key]['name'];
			}
			$activePayment	= preg_replace('/hgw_/', '', $this->getPaymentShortName());
			$locId = Shopware()->Shop()->getLocale()->getId();

			if(in_array($activePayment, $Payment , true)){
				return $this->redirect(array('action' => 'gateway', 'forceSecure' => 1));
			}else{
				return $this->forward('index', 'checkout');
			}
		}catch(Exception $e){
			$this->hgw()->Logging('indexAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Pre dispatch action method
	 */
	public function preDispatch(){
		if(in_array($this->Request()->getActionName(), array('notify', 'book', 'refresh', 'memo', 'response', 'wallet'))){
			Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
		}
	}

	/**
	 * Gateway action method
	 * Api call hCO
	 */
	public function gatewayAction(){
		try{
			if(isset(Shopware()->Session()->sOrderVariables['sOrderNumber'])){
				$this->redirect(array(
						'forceSecure' => 1,
						'action' => 'success',
				));
			}
			$this->View()->swVersion	= Shopware()->Config()->Version;

			if($this->Config()->HGW_MOBILE_CSS){
				$this->View()->isMobile = $this->hgw()->isMobile();
			}
			if($this->hgw()->swfActive()){
				$this->View()->swfActive = true;
			}

			$RefID = NULL;
			$user = $this->getUser();
			$router = Shopware()->Front()->Router();
			$request = Shopware()->Front()->Request();
			$hasReg = false;

			unset(Shopware()->Session()->HPError);
			// checks if SW-Session is expired
			if($this->hgw()->formatNumber($this->getAmount()) == 0){
				Shopware()->Session()->HPError = '';
				$this->hgw()->Logging('Basket empty or Session expired');

				return $this->forward('fail');
			}

			/* PaymentMethode */
			$activePayment	= preg_replace('/hgw_/', '', $this->getPaymentShortName());
			$tempID = $this->createPaymentUniqueId();
			Shopware()->Session()->HPOrderID = $tempID;

			$bookingMode = array('cc','dc','dd','va');
			$basket['currency']	= Shopware()->Currency()->getShortName();
			$basket['amount']	= $this->getAmount();

			if($this->Config()->HGW_INVOICE_DETAILS > 0 or $activePayment == 'bs'){ $ppd_crit = $this->getBasketDetails(); }
			if($this->Config()->HGW_INVOICE_DETAILS > 0){ $ppd_crit = $this->getInvoiceDetails($ppd_crit); }

			$ppd_crit['CRITERION.RESPONSE_URL'] = $this->Front()->Router()->assemble(array(
					'forceSecure' => 1,
					'action' => 'notify',
					'appendSession' => 'SESSION_ID'
			));
			$ppd_crit['CRITERION.SECRET'] = $this->createSecretHash($tempID);
			$ppd_crit['IDENTIFICATION.TRANSACTIONID'] = $tempID;
			$ppd_crit['CRITERION.SESS'] = $user['additional']['user']['sessionID'];

			$realpath 		= realpath(dirname(__FILE__));
			$start 			= strpos($realpath, '/engine');
			$ende 			= strpos($realpath, '/Controllers');
			$len = $ende - $start;
			$pluginPath 	= substr($realpath,$start,$len);
			$basepath		= Shopware()->System()->sCONFIG['sBASEPATH'];
			$shopPath		= substr($basepath,strpos($basepath, '/'));

			$pref = 'http://';
			if(isset($_SERVER['HTTPS'])){
				if($_SERVER['HTTPS'] == 'on'){ $pref = 'https://'; }
			}

			if($activePayment == 'pay'){ $activePayment = va; }
			// BookingMode: CC, DC, DD, VA
			if(in_array($activePayment, $bookingMode)){
				$booking = 'HGW_'.strtoupper($activePayment).'_BOOKING_MODE';

				if($this->Config()->$booking == 3 || $this->Config()->$booking == 4){
					// Registrierung ist vorhanden
					$hasReg = true;
					$reg = $this->hgw()->getRegData($user['additional']['user']['id'], $activePayment);

					$shippingHash = $this->createShippingHash($user, $activePayment);
					$last = mktime(23,59,00,$reg['expMonth']+1,0,$reg['expYear']); // timestamp: last day of registration month
					if(!empty($reg) && ($reg['uid'] != '') && ((($reg['expMonth'] == '0') && ($reg['expYear'] == '0')) || ($last > time())) && (($reg['shippingHash'] == $shippingHash) || ($this->Config()->HGW_SHIPPINGHASH == 1))){
						$ppd_config = $this->hgw()->ppd_config($this->Config()->$booking, $activePayment, $reg['uid'], true);
						$ppd_user = $this->hgw()->ppd_user(NULL, $activePayment);

						$ppd_bskt['PRESENTATION.AMOUNT'] 	= $this->hgw()->formatNumber($basket['amount']);
						$ppd_bskt['PRESENTATION.CURRENCY'] 	= $basket['currency'];
						$ppd_crit['CRITERION.GATEWAY'] 		= '1';

						//adding a basketId for direct debit payment with gurantee
						if ($activePayment == 'dd' && ($this->Config()->HGW_DD_GUARANTEE_MODE == 1) ) {
							//adding a basketId for direct debit payment with gurantee
							$basketId = self::getBasketId();
							if($basketId['result'] == 'NOK'){
								return $this->forward('fail');
							}else{
								$basketId = $basketId['basketId'];
							}
							$ppd_crit['BASKET.ID'] = $basketId;

							// setting birthdate and salutation for template
							$regData = $this->hgw()->getRegData($user['additional']['user']['id'], $activePayment);
							setlocale(LC_TIME, Shopware()->Locale()->getLanguage(), Shopware()->Shop()->getLocale()->getLocale());
							$regData = $this->hgw()->getRegData($user['additional']['user']['id'], $activePayment);

							if (!empty($regData)) {
								$dateOfBirth = json_decode($regData['payment_data'], true);
								$this->View()->regData		= $dateOfBirth;
								$this->View()->salutation	= $dateOfBirth['salut'];
							}

							$this->View()->ddWithGuarantee 		= true;
						} else {
							$this->View()->ddWithGuarantee 		= false;
						}

						$params 		= $this->preparePostData($ppd_config, array(), $ppd_user, $ppd_bskt, $ppd_crit);
						$getFormUrl 	= $this->hgw()->doRequest($params);

						if(trim($getFormUrl['FRONTEND_REDIRECT_URL']) == ''){
							$this->hgw()->Logging($activePayment.' | '.$getFormUrl['PROCESSING_RETURN_CODE'].' | '.$getFormUrl['PROCESSING_RETURN']);
							Shopware()->Session()->HPError = $getFormUrl['PROCESSING_RETURN_CODE'];
							return $this->forward('fail');
						}

						$this->View()->formUrl = $getFormUrl['FRONTEND_REDIRECT_URL'];
//						$this->View()->PaymentUrl = $getFormUrl['FRONTEND_REDIRECT_URL'];
						$this->View()->showButton = false;
					}else{
						// form to register Card and then do a debit on registration
						// if registration of card is expired: reregister
						// registrierung ist noch nicht vorhanden
						if(!empty($reg)){ $uid = $reg['uid']; }
						else{ $uid = NULL; }

						Shopware()->Session()->HPGateway = true;

						//adding a basketId for direct debit payment with gurantee
						if ($activePayment == 'dd' && ($this->Config()->HGW_DD_GUARANTEE_MODE == 1) ) {
							$basketId = self::getBasketId();
							if($basketId['result'] == 'NOK'){
								return $this->forward('fail');
							}else{
								$basketId = $basketId['basketId'];
							}
							$ppd_crit['BASKET.ID'] = $basketId;

							// setting Birthdate
							setlocale(LC_TIME, Shopware()->Locale()->getLanguage(), Shopware()->Shop()->getLocale()->getLocale());
							$regData = $this->hgw()->getRegData($user['additional']['user']['id'], $activePayment);

							if (!empty($regData)) {
								$dateOfBirth = json_decode($regData['payment_data'], true);
								$this->View()->regData		= $dateOfBirth;
							}
							$this->View()->ddWithGuarantee 		= true;
						} else {
							$this->View()->ddWithGuarantee 		= false;
						}

						$getFormUrl = $this->getFormUrl($activePayment, $this->Config()->$booking, $user['additional']['user']['id'], $tempID, $uid, $basket, $ppd_crit);
						unset(Shopware()->Session()->HPGateway);

						$cardBrands[$activePayment]	= json_decode($getFormUrl['CONFIG_BRANDS'], true);
						$bankCountry[$activePayment]	= json_decode($getFormUrl['CONFIG_BANKCOUNTRY'], true);

						if(trim($getFormUrl['FRONTEND_REDIRECT_URL']) == ''){
							$this->hgw()->Logging($activePayment.' | '.$getFormUrl['PROCESSING_RETURN_CODE'].' | '.$getFormUrl['PROCESSING_RETURN']);
							Shopware()->Session()->HPError = $getFormUrl['PROCESSING_RETURN_CODE'];
							return $this->forward('fail');
						}

						$frame[$activePayment] = false;
						if(isset($getFormUrl['FRONTEND_PAYMENT_FRAME_URL']) && ($getFormUrl['FRONTEND_PAYMENT_FRAME_URL'] != '')){
							$formUrl = $getFormUrl['FRONTEND_PAYMENT_FRAME_URL'];
							$frame[$activePayment] = true;
						}else{
							$formUrl = $getFormUrl['FRONTEND_REDIRECT_URL'];
						}

						$this->View()->formUrl 		= $formUrl;
						$this->View()->cardBrands 	= $cardBrands;
						$this->View()->bankCountry	= $bankCountry;
						$this->View()->pm 			= $activePayment;
						$this->View()->heidel_iban 	= $this->Config()->HGW_IBAN;
						$this->View()->user			= $user;
						$this->View()->DbOnRg		= true;
						$this->View()->pluginPath 	= $pref.$basepath.$pluginPath;
						$this->View()->frame		= $frame;
						$this->View()->showButton 	= true;
					}
				}else{
					// KEINE REGISTRIERUNG DER ZAHLDATEN Booking Mode 1 oder 2
					// Paymethods CC, DC, DD, VA

					// DD with guarantee
					if ($activePayment == 'dd' && ($this->Config()->HGW_DD_GUARANTEE_MODE == 1) ) {
						//adding a basketId for direct debit payment with gurantee
						$basketId = self::getBasketId();
						if($basketId['result'] == 'NOK'){
							return $this->forward('fail');
						}else{
							$basketId = $basketId['basketId'];
						}
						$ppd_crit['BASKET.ID'] = $basketId;

						// setting birthdate and salutation for template
						setlocale(LC_TIME, Shopware()->Locale()->getLanguage(), Shopware()->Shop()->getLocale()->getLocale());
						$regData = $this->hgw()->getRegData($user['additional']['user']['id'], $activePayment);

						if (!empty($regData)) {
							$dateOfBirth = json_decode($regData['payment_data'], true);
							$this->View()->regData		= $dateOfBirth;
							$this->View()->salutation	= $dateOfBirth['salut'];
						}

						$this->View()->ddWithGuarantee 		= true;

					} else {
						$this->View()->ddWithGuarantee 		= false;
					}

					$getFormUrl = $this->getFormUrl($activePayment, $this->Config()->$booking, $user['additional']['user']['id'], $tempID, NULL, $basket, $ppd_crit);

					if($getFormUrl['POST_VALIDATION'] == 'NOK' || trim($getFormUrl['FRONTEND_REDIRECT_URL']) == ''){
						$this->hgw()->Logging($activePayment.' | '.$getFormUrl['PROCESSING_RETURN_CODE'].' | '.$getFormUrl['PROCESSING_RETURN']);
						Shopware()->Session()->HPError = $getFormUrl['PROCESSING_RETURN_CODE'];
						return $this->forward('fail');
					}

					$cardBrands[$activePayment]	= json_decode($getFormUrl['CONFIG_BRANDS'], true);
					$bankCountry[$activePayment]	= json_decode($getFormUrl['CONFIG_BANKCOUNTRY'], true);

					if(isset($getFormUrl['FRONTEND_PAYMENT_FRAME_URL']) && ($getFormUrl['FRONTEND_PAYMENT_FRAME_URL'] != '')){
						$formUrl = $getFormUrl['FRONTEND_PAYMENT_FRAME_URL'];
						$frame[$activePayment] = true;

					}else{
						$formUrl = $getFormUrl['FRONTEND_REDIRECT_URL'];
						$frame[$activePayment] = false;

					}
					
					if ($activePayment == 'va') {
					    $this->redirect($getFormUrl['FRONTEND_REDIRECT_URL']);
                    }

					$cssVar = 'HGW_HPF_'.strtoupper($config['PAYMENT.METHOD']).'_CSS';
					$params['FRONTEND.CSS_PATH']	=	$this->Config()->$cssVar;

					$this->View()->formUrl 		= $formUrl;
					$this->View()->cardBrands 	= $cardBrands;
					$this->View()->bankCountry	= $bankCountry;
					$this->View()->pm 			= $activePayment;
					$this->View()->heidel_iban 	= $this->Config()->HGW_IBAN;
					$this->View()->user			= $user;
					$this->View()->pluginPath 	= $pref .$basepath .$pluginPath;
					$this->View()->frame		= $frame;
					$this->View()->showButton 	= true;
				}
			}else{
				$ppd_config = $this->hgw()->ppd_config(NULL, $activePayment, NULL, true);
				$ppd_user = $this->hgw()->ppd_user();
				$ppd_bskt['PRESENTATION.AMOUNT'] = $this->hgw()->formatNumber($basket['amount']);
				$ppd_bskt['PRESENTATION.CURRENCY'] = $basket['currency'];

				if( 	($activePayment != 'pp') &&
						($activePayment != 'iv') &&
						($activePayment != 'bs') &&
						($activePayment != 'mk') &&
                        ($activePayment != 'mpa')&&
                        ($activePayment != 'san')&&
                        ($activePayment != 'ivpd')&&
                        ($activePayment != 'hpr')
						){

							//adding a basketId for papg payment
							if($activePayment == 'papg') {
								$basketId = self::getBasketId();

								if($basketId['result'] == 'NOK'){
									return $this->forward('fail');
								}else{
									$basketId = $basketId['basketId'];
								}
								$ppd_crit['BASKET.ID'] = $basketId;
							}

							$getFormUrl = $this->getFormUrl($activePayment, NULL, $user['additional']['user']['id'], $tempID, NULL, $basket, $ppd_crit);
							if(isset($getFormUrl['FRONTEND_REDIRECT_URL'])){
								$redirectUrl = $getFormUrl['FRONTEND_REDIRECT_URL'];
							}elseif(isset($getFormUrl['PROCESSING_REDIRECT_URL'])){
								$redirectUrl = $getFormUrl['PROCESSING_REDIRECT_URL'];
							}


							if($getFormUrl['POST_VALIDATION'] == 'NOK' || trim($redirectUrl) == ''){
								$this->hgw()->Logging($activePayment.' | '.$getFormUrl['PROCESSING_RETURN_CODE'].' | '.$getFormUrl['PROCESSING_RETURN']);
								Shopware()->Session()->HPError = $getFormUrl['PROCESSING_RETURN_CODE'];
								return $this->forward('fail');
							}

							/* Paymentmethod Sofortueberweisung, Prezlewy24, iDeal, EPS*/
							$cardBrands[$activePayment]	= json_decode($getFormUrl['CONFIG_BRANDS'], true);
							$bankCountry[$activePayment]= json_decode($getFormUrl['CONFIG_BANKCOUNTRY'], true);
							$this->View()->formUrl 		= $redirectUrl;
							$this->View()->cardBrands 	= $cardBrands;
							$this->View()->bankCountry	= $bankCountry;

							if(	$activePayment != 'sue' && $activePayment != 'p24' ){
								$this->View()->pm 		= $activePayment;
							}

							if($activePayment == 'papg'){

								$regData = self::hgw()->getRegData($user['additional']['user']['id'], $activePayment);

								setlocale(LC_TIME, Shopware()->Locale()->getLanguage(), Shopware()->Shop()->getLocale()->getLocale());
								if(!empty($regData)){
									$dobPapg = json_decode($regData['payment_data'], true);
								}

								if((isset($dobPapg)) && ($dobPapg['NAME_BIRTHDATE'] != '')){
									$ppd_crit['NAME.BIRTHDATE'] = $dobPapg['NAME_BIRTHDATE'];
									$this->View()->salutation	= $dobPapg['NAME_SALUTATION'];
									$this->View()->birthdate	= $dobPapg['NAME_BIRTHDATE'];
								}

								$this->View()->accountHolder = $getFormUrl['ACCOUNT_HOLDER'];
							}

							$this->View()->heidel_iban 	= $this->Config()->HGW_IBAN;
							$this->View()->user			= $user;
							$this->View()->pluginPath 	= $pref .$basepath .$pluginPath;

				}else{
					$booking = 'HGW_'.strtoupper($activePayment).'_BOOKING_MODE';
					$ppd_config = $this->hgw()->ppd_config($this->Config()->$booking, $activePayment, NULL, true);
					$regData = self::hgw()->getRegData($user['additional']['user']['id'], $activePayment);

					if($activePayment == 'mpa'){
						if(empty($regData)){
							$basketId = self::getBasketId();

							if($basketId['result'] == 'NOK'){
								return $this->forward('fail');
							}else{
								$basketId = $basketId['basketId'];
							}
							$ppd_crit['BASKET.ID'] = $basketId;
							$ppd_crit['WALLET.DIRECT_PAYMENT'] = 'true';
							$ppd_crit['WALLET.DIRECT_PAYMENT_CODE'] = "WT.".$ppd_config['PAYMENT.TYPE'];
							$ppd_crit['PAYMENT.CODE'] = 'WT.IN';
						}else{
							$ppd_crit['IDENTIFICATION.REFERENCEID'] = $regData['uid'];
							$ppd_crit['FRONTEND.ENABLED'] = 'true';
						}
					}

                    if($activePayment == 'san') {
                        $basketId = self::getBasketId();

                        if($basketId['result'] == 'NOK'){
                            return $this->forward('fail');
                        }else{
                            $basketId = $basketId['basketId'];
                        }
                        $ppd_crit['BASKET.ID'] = $basketId;

                        $regDataParameters = json_decode($regData["payment_data"]);

                        $ppd_crit["NAME.BIRTHDATE"] = $regDataParameters->NAME_BIRTHDATE;
                        $ppd_crit["NAME.SALUTATION"] = $regDataParameters->NAME_SALUTATION;
                        $ppd_crit["CUSTOMER.OPTIN"] = strtoupper($regDataParameters->CUSTOMER_OPTIN);
                        $ppd_crit["CUSTOMER.OPTIN_2"] =
                            strtoupper(($regDataParameters->CUSTOMER_ACCEPT_PRIVACY_POLICY == "ON") || ($regDataParameters->CUSTOMER_ACCEPT_PRIVACY_POLICY == "TRUE") ? "TRUE" : "FALSE");
                        $ppd_crit["CUSTOMER.ACCEPT_PRIVACY_POLICY"] =
                            strtoupper(($regDataParameters->CUSTOMER_ACCEPT_PRIVACY_POLICY == "ON") || ($regDataParameters->CUSTOMER_ACCEPT_PRIVACY_POLICY == "TRUE") ? "TRUE" : "FALSE");
                        $ppd_crit["CRITERION.IVBRAND"] = "SANTANDER";

                        //to prevent sending request to paymentgateway if browser-back-button was pushed
                        if(
                            empty($ppd_crit["NAME.BIRTHDATE"]) ||
                            empty($ppd_crit["NAME.SALUTATION"]) ||
                            empty($ppd_crit["CUSTOMER.ACCEPT_PRIVACY_POLICY"])
                        )
                        {
                            return $this->forward('missinginput');
                        }
                    }

                    if($activePayment == 'ivpd') {
                        $basketId = self::getBasketId();

                        if($basketId['result'] == 'NOK'){
                            return $this->forward('fail');
                        }else{
                            $basketId = $basketId['basketId'];
                        }
                        $ppd_crit['BASKET.ID'] = $basketId;
                        $regDataParameters = json_decode($regData["payment_data"]);

                        $ppd_crit["NAME.BIRTHDATE"] = $regDataParameters->NAME_BIRTHDATE;
                        $ppd_crit["NAME.SALUTATION"] = $regDataParameters->NAME_SALUTATION;
                        $ppd_crit["CONTACT.PHONE"] = $regDataParameters->CONTACT_PHONE;

                        //fetching count of orders of customer
                        $countOrderForCustomer = '';
                        $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
                        $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);

                        $ppd_crit['RISKINFORMATION.CUSTOMERGUESTCHECKOUT']  = $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE';
                        $ppd_crit['RISKINFORMATION.CUSTOMERSINCE'] 		    = $user['additional']['user']['firstlogin'];
                        $ppd_crit['RISKINFORMATION.CUSTOMERORDERCOUNT'] 	= $countOrderForCustomer['COUNT(id)'];
                        $ppd_crit['CRITERION.USER_ID'] 	= $user['additional']['user']['id'];
                        $ppd_crit["CRITERION.IVBRAND"] = "PAYOLUTION";

                        //to prevent sending request to paymentgateway if browser-back-button was pushed
                        if(
                            empty($ppd_crit["NAME.BIRTHDATE"]) ||
                            empty($ppd_crit["NAME.SALUTATION"])
                        )
                        {
                            return $this->forward('missinginput');
                        }

                    }

                    if ($activePayment == 'hpr') {
                        // fetch INI Transaction to set the ReferenceId
                        $transaction = $this->getHgwTransactions(Shopware()->Session()->sessionId);

                        //setting
                        $ppd_bskt['PRESENTATION.AMOUNT'] 	= $this->hgw()->formatNumber($basket['amount']);
                        $ppd_bskt['PRESENTATION.CURRENCY'] 	= $basket['currency'];

                        $ppd_crit['IDENTIFICATION.REFERENCEID'] = $transaction['uniqueid'];
                        unset($this->View()->configOptInText);
                    }

					$params = $this->preparePostData($ppd_config, array(), $ppd_user, $ppd_bskt, $ppd_crit);

					if($activePayment == 'bs'){
						if(!$this->mergeAddress()){
							$locId = (Shopware()->Locale()->getLanguage() == 'de') ? 1 : 2;
							Shopware()->Session()->HPError = '';
							return $this->forward('fail');
						}else{
							$params['CRITERION.GATEWAY'] = '1';
							$this->saveBillSafeRequest2DB($tempID, $params);
						}
					}elseif($activePayment == 'mk'){
						$params['CRITERION.GATEWAY'] = '1';
					}

					$response = $this->hgw()->doRequest($params);
				}
			}

			$this->View()->pluginPath = $pref .$basepath .$pluginPath;
			if($response['POST_VALIDATION'] == "NOK"){
				$this->hgw()->Logging(
						$response['PROCESSING_RETURN'].
						" -> please verify plugin configuration.<br/>" . print_r($params,1));
						Shopware()->Session()->HPError = $response['PROCESSING_RETURN_CODE'];
						return $this->forward('fail');
			}elseif($response['PROCESSING_RESULT'] == "NOK"){
                $this->hgw()->saveRes($response);
				Shopware()->Session()->HPError = $response['PROCESSING_RETURN_CODE'];

				return $this->forward('fail');
			}

			if($response['PROCESSING_RESULT'] == "ACK" || $response['POST_VALIDATION'] == "ACK"){
				$this->View()->pluginPath = $pref.$basepath.$pluginPath;
				if(in_array($activePayment, array('mpa')) && !empty($response['FRONTEND_REDIRECT_URL'])){
					return $this->redirect($response['FRONTEND_REDIRECT_URL'], array('code' => '302'));
				}elseif(!empty($response['PROCESSING_REDIRECT_URL'])){
					if($response['PROCESSING_STATUS_CODE'] == '80'){

						$this->View()->PaymentUrl = $response['PROCESSING_REDIRECT_URL'];
						$input = array();

						if($hasReg){ $this->View()->useIframe = 0; }
						else{ $this->View()->useIframe = 1; }

						if(($response['ACCOUNT_BRAND'] == 'BILLSAFE') || (strtolower($response['PAYMENT_CODE']) == 'wt.in')){
							$this->View()->useIframe = 0;
							$input[] = '';
						}

						foreach($response AS $k => $v){
							if(strpos($k,'PROCESSING_REDIRECT_PARAMETER_') !== false){
								$key = preg_replace('/PROCESSING_REDIRECT_PARAMETER_/', '', $k);
								$input[$key] = $v;
							}
						}
						$this->View()->Input = $input;
					}else{
						Shopware()->Template()->addTemplateDir(dirname(__FILE__).'/Views/');
						$this->View()->RedirectURL = $response['PROCESSING_REDIRECT_URL'];
						$input = array();
						foreach($response AS $k => $v){
							if(strpos($k,'PROCESSING_REDIRECT_PARAMETER_') !== false){
								$key = preg_replace('/PROCESSING_REDIRECT_PARAMETER_/', '', $k);
								$input[$key] = $v;
							}
						}
						$this->View()->Input = $input;
					}
				}

				elseif(in_array($activePayment, array('pp', 'iv', 'papg', 'san', 'ivpd')) && empty($response['ACCOUNT.BRAND'])){

                    if($activePayment == "san" || $activePayment == "ivpd")
                    {
                        return $this->redirect($response['FRONTEND_REDIRECT_URL']);
                    }

					$transactionId = $response['IDENTIFICATION_TRANSACTIONID'];
					$paymentUniqueId = $response['IDENTIFICATION_UNIQUEID'];
					$locId = (Shopware()->Locale()->getLanguage() == 'de') ? 1 : 2;
					$repl = array(
							'{AMOUNT}'						=> $this->hgw()->formatNumber($this->getAmount()),
							'{CURRENCY}'					=> $this->getCurrencyShortName(),
							'{CONNECTOR_ACCOUNT_COUNTRY}'	=> $response['CONNECTOR_ACCOUNT_COUNTRY']."\n",
							'{CONNECTOR_ACCOUNT_HOLDER}'	=> $response['CONNECTOR_ACCOUNT_HOLDER']."\n",
							'{CONNECTOR_ACCOUNT_NUMBER}'	=> $response['CONNECTOR_ACCOUNT_NUMBER']."\n",
							'{CONNECTOR_ACCOUNT_BANK}'		=> $response['CONNECTOR_ACCOUNT_BANK']."\n",
							'{CONNECTOR_ACCOUNT_IBAN}'		=> $response['CONNECTOR_ACCOUNT_IBAN']."\n",
							'{CONNECTOR_ACCOUNT_BIC}'		=> $response['CONNECTOR_ACCOUNT_BIC']."\n\n",
							'{IDENTIFICATION_SHORTID}'		=> "\n".$response['IDENTIFICATION_SHORTID'],
					);

					if(
					    ($activePayment == 'pp') ||
                        ($activePayment == 'iv') ||
                        ($activePayment == 'papg') ||
                        ($activePayment == 'san') ||
                        ($activePayment == 'ivpd')
                    ){
						$comment = '<strong>'.$this->getSnippet('InvoiceHeader', $locId).":</strong>";
						$comment.= strtr($this->getSnippet('PrepaymentText', $locId), $repl);

					}elseif($activePayment == 'san'){
                        $repl = array(
                            '{AMOUNT}'						=> $this->hgw()->formatNumber($this->getAmount()),
                            '{CURRENCY}'					=> $this->getCurrencyShortName(),
                            '{CONNECTOR_ACCOUNT_COUNTRY}'	=> $response['CONNECTOR_ACCOUNT_COUNTRY']."\n",
                            '{CONNECTOR_ACCOUNT_HOLDER}'	=> $response['CONNECTOR_ACCOUNT_HOLDER']."\n",
                            '{CONNECTOR_ACCOUNT_NUMBER}'	=> $response['CONNECTOR_ACCOUNT_NUMBER']."\n",
                            '{CONNECTOR_ACCOUNT_BANK}'		=> $response['CONNECTOR_ACCOUNT_BANK']."\n",
                            '{CONNECTOR_ACCOUNT_IBAN}'		=> $response['CONNECTOR_ACCOUNT_IBAN']."\n",
                            '{CONNECTOR_ACCOUNT_BIC}'		=> $response['CONNECTOR_ACCOUNT_BIC']."\n\n",
                            '{CONNECTOR_ACCOUNT_USAGE}'		=> "\n".$response['CONNECTOR_ACCOUNT_USAGE'],
                        );

                        $comment = '<strong>'.$this->getSnippet('InvoiceHeader', $locId).":</strong>";
                        $comment.= strtr($this->getSnippet('PrepaymentSanText', $locId), $repl);

                    }elseif($activePayment == 'ivpd'){
                        $repl = array(
                            '{AMOUNT}'						=> $this->hgw()->formatNumber($this->getAmount()),
                            '{CURRENCY}'					=> $this->getCurrencyShortName(),
                            '{CONNECTOR_ACCOUNT_COUNTRY}'	=> $response['CONNECTOR_ACCOUNT_COUNTRY']."\n",
                            '{CONNECTOR_ACCOUNT_HOLDER}'	=> $response['CONNECTOR_ACCOUNT_HOLDER']."\n",
                            '{CONNECTOR_ACCOUNT_IBAN}'		=> $response['CONNECTOR_ACCOUNT_IBAN']."\n",
                            '{CONNECTOR_ACCOUNT_BIC}'		=> $response['CONNECTOR_ACCOUNT_BIC']."\n\n",
                            '{CONNECTOR_ACCOUNT_USAGE}'		=> "\n".$response['CONNECTOR_ACCOUNT_USAGE'],
                        );

                        $comment = '<strong>'.$this->getSnippet('InvoiceHeader', $locId).":</strong>";
                        $comment.= strtr($this->getSnippet('PrepaymentIvpdText', $locId), $repl);

                    }else{
						$comment = strtr($this->getSnippet('PrepaymentText', $locId), $repl);
					}

					// basket to order
					$paymentStatus = "21";
					//$paymentStatus = "9";

					Shopware()->Session()->HPTrans = $paymentUniqueId;
					$response['TRANSACTION_SOURCE'] = 'GATEWAY';

					$this->hgw()->saveRes($response);
					$return = $this->saveOrder($transactionId, $paymentUniqueId, $paymentStatus,false);

					// add infos to order
					$params = array(
							'comment' => $comment,
							'internalcomment' => $comment,
					);
					$this->addOrderInfos($transactionId, $params);

					$comment = preg_replace('/:/', ':<br/><br/>', $comment, 1);
					$comment = nl2br($comment);
					Shopware()->Session()->sOrderVariables['sTransactionumber'] = $transactionId;
					Shopware()->Session()->sOrderVariables['prepaymentText'] = $comment;

					return $this->redirect(array(
							'forceSecure' => 1,
							'action' => 'success',
							'txnID' => $transactionId,
							'sUniqueID' => $transactionId,
					));
				}elseif(!empty($response['IDENTIFICATION_REFERENCEID'])){
					$transactionId = $response['IDENTIFICATION_TRANSACTIONID'];
					$paymentUniqueId = $response['IDENTIFICATION_UNIQUEID'];

					if(is_int(strpos($response['PAYMENT_CODE'], 'DB'))){
						$paymentStatus = '12';
					}else{
						$paymentStatus = '18';
					}
					Shopware()->Session()->HPTrans = $paymentUniqueId;
					$return = $this->saveOrder($transactionId, $paymentUniqueId, $paymentStatus);

					$params = array(
							'o_attr1' => $response['IDENTIFICATION_SHORTID'],
							'o_attr2' => $response['IDENTIFICATION_UNIQUEID'],
							'internalcomment' => "ShortID: ".$response['IDENTIFICATION_SHORTID']
					);
					$this->addOrderInfos($transactionId, $params, $paymentStatus);

                    // save Response for HP.PA
                    if ($response['PAYMENT_CODE'] == 'HP.PA') {
                        $this->hgw()->saveRes($response);
                    }

					return $this->redirect(array(
							'forceSecure' => 1,
							'action' => 'success',
							'txnID' => $transactionId
					));
				}

				if(!empty($response['FRONTEND_REDIRECT_URL'])){
					$this->View()->PaymentUrl = $response['FRONTEND_REDIRECT_URL'];
				}
			}
		}catch(Exception $e){

			$this->hgw()->Logging('gatewayAction | '.$e->getMessage());

			return;
		}
	}

    /**
     * Function redirects to payment method choice in case of missing birthdate fpr Santander oder Payolution
     */
    public function missinginputAction()
    {
        try{
            switch (Shopware()->Shop()->getTemplate()->getVersion()){
                // Emotion Template
                case "2":
                    return $this->redirect(array(
                        'forceSecure' => 1,
                        'controller' => 'account',
                        'action' => 'payment'
                    ));
                    break;
                // Responsive Template
                case "3":
                    return $this->redirect(array(
                        'forceSecure' => 1,
                        'controller' => 'checkout',
                        'action' => 'shippingPayment'
                    ));
                    break;
                // other Templates
                default:
                    return $this->redirect(array(
                        'forceSecure' => 1,
                        'controller' => 'checkout',
                        'action' => 'shippingPayment'
                    ));
                    break;
            }
        } catch (Exception $e) {
            $this->hgw()->Logging('missinginputAction | '.$e->getMessage());
        }

	}
	/**
	 * response action method for the reponse call of a debitfrom heidelpay
	 */
	public function responseAction(){
		try{
			unset(Shopware()->Session()->HPError);
			if($this->Request()->isPost()){
    			$flag = ENT_COMPAT;
				$enc = 'UTF-8';
				if($this->Request()->getPost('TRANSACTION_SOURCE') == false){ $this->Request()->setPost('TRANSACTION_SOURCE', 'RESPONSE'); }
				$resp['REQUEST_VERSION']			= $this->Request()->getPost('REQUEST_VERSION') == true ? htmlspecialchars($this->Request()->getPost('REQUEST_VERSION'), $flag, $enc) : '';
				$resp['SECURITY_SENDER']			= $this->Request()->getPost('SECURITY_SENDER') == true ? htmlspecialchars($this->Request()->getPost('SECURITY_SENDER'), $flag, $enc) : '';
				$resp['USER_LOGIN']					= $this->Request()->getPost('USER_LOGIN') == true ? htmlspecialchars($this->Request()->getPost('USER_LOGIN'), $flag, $enc) : '';
				$resp['USER_PWD']					= $this->Request()->getPost('USER_PWD') == true ? htmlspecialchars($this->Request()->getPost('USER_PWD'), $flag, $enc) : '';
				$resp['TRANSACTION_CHANNEL']		= $this->Request()->getPost('TRANSACTION_CHANNEL') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_CHANNEL'), $flag, $enc) : '';

				$resp['PROCESSING_RESULT']			= $this->Request()->getPost('PROCESSING_RESULT') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RESULT'), $flag, $enc) : '';
				$resp['PROCESSING_RETURN']			= $this->Request()->getPost('PROCESSING_RETURN') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RETURN'), $flag, $enc) : '';
				$resp['PROCESSING_CODE']			= $this->Request()->getPost('PROCESSING_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_CODE'), $flag, $enc) : '';
				$resp['PROCESSING_RETURN_CODE']		= $this->Request()->getPost('PROCESSING_RETURN_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RETURN_CODE'), $flag, $enc) : '';
				$resp['PROCESSING_STATUS_CODE']		= $this->Request()->getPost('PROCESSING_STATUS_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_STATUS_CODE'), $flag, $enc) : '';
				$resp['PROCESSING_REASON_CODE']		= $this->Request()->getPost('PROCESSING_REASON_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_REASON_CODE'), $flag, $enc) : '';
				$resp['PROCESSING_REASON']			= $this->Request()->getPost('PROCESSING_REASON') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_REASON'), $flag, $enc) : '';
				$resp['PROCESSING_TIMESTAMP']		= $this->Request()->getPost('PROCESSING_TIMESTAMP') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_TIMESTAMP'), $flag, $enc) : '';
				$resp['PROCESSING_STATUS']			= $this->Request()->getPost('PROCESSING_STATUS') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_STATUS'), $flag, $enc) : '';

				$resp['CRITERION_SHOP_ID']			= $this->Request()->getPost('CRITERION_SHOP_ID') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHOP_ID'), $flag, $enc) : '';
				$resp['CRITERION_USER_ID']			= $this->Request()->getPost('CRITERION_USER_ID') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_USER_ID'), $flag, $enc) : '';
				$resp['CRITERION_DBONRG']			= $this->Request()->getPost('CRITERION_DBONRG') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_DBONRG'), $flag, $enc) : '';
				$resp['CRITERION_SHIPPAY']			= $this->Request()->getPost('CRITERION_SHIPPAY') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHIPPAY'), $flag, $enc) : '';
				$resp['CRITERION_GATEWAY']			= $this->Request()->getPost('CRITERION_GATEWAY') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_GATEWAY'), $flag, $enc) : '';
				$resp['CRITERION_WALLET']			= $this->Request()->getPost('CRITERION_WALLET') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_WALLET'), $flag, $enc) : '';
				$resp['CRITERION_WALLET_PAYNAME']	= $this->Request()->getPost('CRITERION_WALLET_PAYNAME') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_WALLET_PAYNAME'), $flag, $enc) : '';
				$resp['CRITERION_SECRET']			= $this->Request()->getPost('CRITERION_SECRET') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SECRET'), $flag, $enc) : '';
				$resp['CRITERION_SHIPPINGHASH']		= $this->Request()->getPost('CRITERION_SHIPPINGHASH') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHIPPINGHASH'), $flag, $enc) : '';
				$resp['CRITERION_BILLSAFE_REFERENCE']= $this->Request()->getPost('CRITERION_BILLSAFE_REFERENCE') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_BILLSAFE_REFERENCE'), $flag, $enc) : '';
				$resp['CRITERION_SESS']				= $this->Request()->getPost('CRITERION_SESS') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SESS'), $flag, $enc) : '';
				$resp['CRITERION_PUSH_URL']			= $this->Request()->getPost('CRITERION_PUSH_URL') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_PUSH_URL'), $flag, $enc) : '';
				$resp['CRITERION_RESPONSE_URL']		= $this->Request()->getPost('CRITERION_RESPONSE_URL') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_RESPONSE_URL'), $flag, $enc) : '';
				$resp['CRITERION_SHOP_TYPE']		= $this->Request()->getPost('CRITERION_SHOP_TYPE') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHOP_TYPE'), $flag, $enc) : '';
				$resp['CRITERION_MODULE_VERSION']	= $this->Request()->getPost('CRITERION_MODULE_VERSION') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_MODULE_VERSION'), $flag, $enc) : '';
				$resp['SHOPMODULE_VERSION']			= $this->Request()->getPost('SHOPMODULE_VERSION') == true ? htmlspecialchars($this->Request()->getPost('SHOPMODULE_VERSION'), $flag, $enc) : '';
				$resp['CRITERION_INSURANCE-RESERVATION'] = $this->Request()->getPost('CRITERION_INSURANCE-RESERVATION') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_INSURANCE-RESERVATION'), $flag, $enc) : '';

				$resp['PAYMENT_CODE']				= $this->Request()->getPost('PAYMENT_CODE') == true ? htmlspecialchars($this->Request()->getPost('PAYMENT_CODE'), $flag, $enc) : '';

				$resp['PRESENTATION_CURRENCY']		= $this->Request()->getPost('PRESENTATION_CURRENCY') == true ? htmlspecialchars($this->Request()->getPost('PRESENTATION_CURRENCY'), $flag, $enc) : '';
				$resp['PRESENTATION_AMOUNT']		= $this->Request()->getPost('PRESENTATION_AMOUNT') == true ? htmlspecialchars($this->Request()->getPost('PRESENTATION_AMOUNT'), $flag, $enc) : '';

				$resp['IDENTIFICATION_TRANSACTIONID']= $this->Request()->getPost('IDENTIFICATION_TRANSACTIONID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_TRANSACTIONID'), $flag, $enc) : '';
				$resp['IDENTIFICATION_UNIQUEID']	= $this->Request()->getPost('IDENTIFICATION_UNIQUEID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_UNIQUEID'), $flag, $enc) : '';
				$resp['IDENTIFICATION_SHORTID']		= $this->Request()->getPost('IDENTIFICATION_SHORTID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_SHORTID'), $flag, $enc) : '';
				$resp['IDENTIFICATION_CREDITOR_ID']	= $this->Request()->getPost('IDENTIFICATION_CREDITOR_ID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_CREDITOR_ID'), $flag, $enc) : '';
				$resp['IDENTIFICATION_REFERENCEID']	= $this->Request()->getPost('IDENTIFICATION_REFERENCEID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_REFERENCEID'), $flag, $enc) : '';
				$resp['IDENTIFICATION_SHOPPERID']	= $this->Request()->getPost('IDENTIFICATION_SHOPPERID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_SHOPPERID'), $flag, $enc) : '';

				$resp['FRONTEND_MODE']				= $this->Request()->getPost('FRONTEND_MODE') == true ? $this->Request()->getPost('FRONTEND_MODE') : '';
				$resp['FRONTEND_ENABLED']			= $this->Request()->getPost('FRONTEND_ENABLED') == true ? $this->Request()->getPost('FRONTEND_ENABLED') : '';
				$resp['FRONTEND_LANGUAGE']			= $this->Request()->getPost('FRONTEND_LANGUAGE') == true ? $this->Request()->getPost('FRONTEND_LANGUAGE') : '';
                $resp['FRONTEND_RESPONSE_URL']		= $this->Request()->getPost('FRONTEND_RESPONSE_URL') == true ? $this->Request()->getPost('FRONTEND_RESPONSE_URL') : '';

				$resp['ACCOUNT_EXPIRY_MONTH']		= $this->Request()->getPost('ACCOUNT_EXPIRY_MONTH') == true ? $this->Request()->getPost('ACCOUNT_EXPIRY_MONTH') : '';
				$resp['ACCOUNT_EXPIRY_YEAR']		= $this->Request()->getPost('ACCOUNT_EXPIRY_YEAR') == true ? $this->Request()->getPost('ACCOUNT_EXPIRY_YEAR') : '';
				$resp['ACCOUNT_BRAND']				= $this->Request()->getPost('ACCOUNT_BRAND') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BRAND'), $flag, $enc) : '';
				$resp['ACCOUNT_HOLDER']				= $this->Request()->getPost('ACCOUNT_HOLDER') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_HOLDER'), $flag, $enc) : '';
				$resp['ACCOUNT_IBAN']				= $this->Request()->getPost('ACCOUNT_IBAN') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_IBAN'), $flag, $enc) : '';
				$resp['ACCOUNT_BIC']				= $this->Request()->getPost('ACCOUNT_BIC') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BIC'), $flag, $enc) : '';
				$resp['ACCOUNT_NUMBER']				= $this->Request()->getPost('ACCOUNT_NUMBER') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_NUMBER'), $flag, $enc) : '';
				$resp['ACCOUNT_BANK']				= $this->Request()->getPost('ACCOUNT_BANK') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BANK'), $flag, $enc) : '';
				$resp['ACCOUNT_IDENTIFICATION']		= $this->Request()->getPost('ACCOUNT_IDENTIFICATION') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_IDENTIFICATION'), $flag, $enc) : '';

				$resp['CONNECTOR_ACCOUNT_BANK']		= $this->Request()->getPost('CONNECTOR_ACCOUNT_BANK') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_BANK'), $flag, $enc) : '';
				$resp['CONNECTOR_ACCOUNT_BIC']		= $this->Request()->getPost('CONNECTOR_ACCOUNT_BIC') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_BIC'), $flag, $enc) : '';
				$resp['CONNECTOR_ACCOUNT_NUMBER']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_NUMBER') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_NUMBER'), $flag, $enc) : '';
				$resp['CONNECTOR_ACCOUNT_IBAN']		= $this->Request()->getPost('CONNECTOR_ACCOUNT_IBAN') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_IBAN'), $flag, $enc) : '';
				$resp['CONNECTOR_ACCOUNT_COUNTRY']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_COUNTRY') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_COUNTRY'), $flag, $enc) : '';
				$resp['CONNECTOR_ACCOUNT_HOLDER']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_HOLDER') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_HOLDER'), $flag, $enc) : '';
                $resp['CONNECTOR_ACCOUNT_USAGE']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_USAGE') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_USAGE'), $flag, $enc) : '';

                $resp['BASKET_ID']	                = $this->Request()->getPost('BASKET_ID') == true ? htmlspecialchars($this->Request()->getPost('BASKET_ID'), $flag, $enc) : '';

                $resp['NAME_COMPANY']				= $this->Request()->getPost('NAME_COMPANY') == true ? htmlspecialchars($this->Request()->getPost('NAME_COMPANY'), $flag, $enc) : '';
				$resp['NAME_SALUTATION']			= $this->Request()->getPost('NAME_SALUTATION') == true ? htmlspecialchars($this->Request()->getPost('NAME_SALUTATION'), $flag, $enc) : '';
				$resp['NAME_BIRTHDATE']				= $this->Request()->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($this->Request()->getPost('NAME_BIRTHDATE'), $flag, $enc) : '';
				$resp['NAME_FAMILY']				= $this->Request()->getPost('NAME_FAMILY') == true ? htmlspecialchars($this->Request()->getPost('NAME_FAMILY'), $flag, $enc) : '';
				$resp['NAME_GIVEN']					= $this->Request()->getPost('NAME_GIVEN') == true ? htmlspecialchars($this->Request()->getPost('NAME_GIVEN'), $flag, $enc) : '';
				$resp['ADDRESS_STREET']				= $this->Request()->getPost('ADDRESS_STREET') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_STREET'), $flag, $enc) : '';
				$resp['ADDRESS_CITY']				= $this->Request()->getPost('ADDRESS_CITY') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_CITY'), $flag, $enc) : '';
				$resp['ADDRESS_ZIP']				= $this->Request()->getPost('ADDRESS_ZIP') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_ZIP'), $flag, $enc) : '';
				$resp['ADDRESS_COUNTRY']			= $this->Request()->getPost('ADDRESS_COUNTRY') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_COUNTRY'), $flag, $enc) : '';

                $resp['CUSTOMER_OPTIN']			    = $this->Request()->getPost('CUSTOMER_OPTIN') == true ? strtoupper(htmlspecialchars($this->Request()->getPost('CUSTOMER_OPTIN'), $flag, $enc)) : '';
                $resp['CUSTOMER_OPTIN_2']			= $this->Request()->getPost('CUSTOMER_OPTIN_2') == true ? strtoupper(htmlspecialchars($this->Request()->getPost('CUSTOMER_OPTIN_2'), $flag, $enc)) : '';

                $resp['CONTACT_EMAIL']				= $this->Request()->getPost('CONTACT_EMAIL') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_EMAIL'), $flag, $enc) : '';
				$resp['CONTACT_PHONE']				= $this->Request()->getPost('CONTACT_PHONE') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_PHONE'), $flag, $enc) : '';
				$resp['CONTACT_IP']					= $this->Request()->getPost('CONTACT_IP') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_IP'), $flag, $enc) : '';

				$resp['TRANSACTION_CHANNEL']		= $this->Request()->getPost('TRANSACTION_CHANNEL') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_CHANNEL'), $flag, $enc) : '';
				$resp['TRANSACTION_MODE']			= $this->Request()->getPost('TRANSACTION_MODE') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_MODE'), $flag, $enc) : '';

				$resp['CLEARING_AMOUNT']			= $this->Request()->getPost('CLEARING_AMOUNT') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_AMOUNT'), $flag, $enc) : '';
				$resp['CLEARING_CURRENCY']			= $this->Request()->getPost('CLEARING_CURRENCY') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_CURRENCY'), $flag, $enc) : '';
				$resp['CLEARING_DESCRIPTOR']		= $this->Request()->getPost('CLEARING_DESCRIPTOR') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_DESCRIPTOR'), $flag, $enc) : '';


				$resp['var_Register']		= ($this->Request()->getPost('register') == true && gettype($this->Request()->getPost('register')) == 'array') ? $this->Request()->getPost('register') : '';
				$resp['var_sTarget']		= $this->Request()->getPost('sTarget') == true ? htmlspecialchars($this->Request()->getPost('sTarget'), $flag, $enc) : '';
				$resp['var_sepa']			= $this->Request()->getPost('hpdd_sepa') == true ? htmlspecialchars($this->Request()->getPost('hpdd_sepa'), $flag, $enc) : '';
				$resp['__csrf_token']		= $this->Request()->getPost('__csrf_token') == true ? htmlspecialchars($this->Request()->getPost('__csrf_token'), $flag, $enc) : '';

				$resp['CONFIG_OPTIN_TEXT']	= $this->Request()->getPost('CONFIG_OPTIN_TEXT') == true ? htmlspecialchars(json_decode($this->Request()->getPost('CONFIG_OPTIN_TEXT'), $flag, $enc),true) : '';

				if (isset($resp['NAME_BIRTHDATE']) && !(empty($resp['NAME_BIRTHDATE'])) ) {
					$resp['NAME_BIRTHDATE'] 	= $resp['NAME_BIRTHDATE'];
				} else {
					$resp['NAME_BIRTHDATE'] 	= $this->Request()->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($this->Request()->getPost('birthdate_san'), $flag, $enc) : '';
				}
				$orgHash 					= $this->createSecretHash($resp['IDENTIFICATION_TRANSACTIONID']);

				if($resp['CRITERION_SECRET'] != $orgHash){
					Shopware()->Session()->HPError = '';
					$this->hgw()->Logging(
							"Hash verification error, suspecting manipulation.".
							"<br />PaymentUniqeID: " . $resp['IDENTIFICATION_TRANSACTIONID'] .
							"<br />IP: " . $_SERVER['REMOTE_ADDR'] .
							"<br />Hash: " .htmlspecialchars($orgHash) .
							"<br />ResponseHash: " .htmlspecialchars($resp['CRITERION_SECRET']));

					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' => 1,
							'action' => 'fail'
					));
					exit;
				}

				if ($resp['PROCESSING_RESULT'] == 'ACK' && $resp['PAYMENT_CODE'] != 'WT.IN') {
					// save result to database hgw_transactions
					$this->hgw()->saveRes($resp);
					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' 	=> 1,
							'controller' 	=> 'PaymentHgw',
							'action' 		=> 'success'
					));
					return;
				} elseif ($resp['PROCESSING_RESULT'] == 'ACK' && $resp['PAYMENT_CODE'] == 'WT.IN') {
					// save result to database hgw_transactions
					$this->hgw()->saveRes($resp);
					// Weiterleitung zu accountAction bei MaPa QuickCheckout
					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' 	=> 1,
							'controller' 	=> 'PaymentHgw',
							'action' 		=> 'createAcc',

					));
				} elseif ($resp['PROCESSING_RESULT'] == 'NOK') {
					// save result to database hgw_transactions
					$this->hgw()->saveRes($resp);
					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' => 1,
							'controller' => 'PaymentHgw',
							'action' => 'fail'
					));
					return;
				}
			}
		}catch(Exception $e){
			$this->hgw()->Logging('responseAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * response action method for the reponse call of a registration from heidelpay
	 */
	public function responseRegAction() {
		// setting csrf-Token is required
		if($this->Request()->getPost('__csrf_token')){
			$token = 'X-CSRF-Token';
			Shopware()->Session()->$token = $this->Request()->getPost('__csrf_token');
		}
		Shopware()->Session()->sUserId	= htmlspecialchars($this->Request()->getPost('CRITERION_USER_ID'));

		unset(Shopware()->Session()->HPError);
		if($this->Request()->isPost()){
			$flag = ENT_COMPAT;
			$enc = 'UTF-8';
			if($this->Request()->getPost('TRANSACTION_SOURCE') == false){ $this->Request()->setPost('TRANSACTION_SOURCE', 'RESPONSE'); }
			// if(myFunction() == true) -> equivalent of !empty($var)
			$flag = ENT_COMPAT;
			$enc = 'UTF-8';
			if($this->Request()->getPost('TRANSACTION_SOURCE') == false){ $this->Request()->setPost('TRANSACTION_SOURCE', 'RESPONSE'); }
			$resp['REQUEST_VERSION']			= $this->Request()->getPost('REQUEST_VERSION') == true ? htmlspecialchars($this->Request()->getPost('REQUEST_VERSION'), $flag, $enc) : '';
			$resp['SECURITY_SENDER']			= $this->Request()->getPost('SECURITY_SENDER') == true ? htmlspecialchars($this->Request()->getPost('SECURITY_SENDER'), $flag, $enc) : '';
			$resp['USER_LOGIN']					= $this->Request()->getPost('USER_LOGIN') == true ? htmlspecialchars($this->Request()->getPost('USER_LOGIN'), $flag, $enc) : '';
			$resp['USER_PWD']					= $this->Request()->getPost('USER_PWD') == true ? htmlspecialchars($this->Request()->getPost('USER_PWD'), $flag, $enc) : '';
			$resp['TRANSACTION_CHANNEL']		= $this->Request()->getPost('TRANSACTION_CHANNEL') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_CHANNEL'), $flag, $enc) : '';

			$resp['PROCESSING_RESULT']			= $this->Request()->getPost('PROCESSING_RESULT') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RESULT'), $flag, $enc) : '';
			$resp['PROCESSING_RETURN']			= $this->Request()->getPost('PROCESSING_RETURN') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RETURN'), $flag, $enc) : '';
			$resp['PROCESSING_CODE']			= $this->Request()->getPost('PROCESSING_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_CODE'), $flag, $enc) : '';
			$resp['PROCESSING_RETURN_CODE']		= $this->Request()->getPost('PROCESSING_RETURN_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RETURN_CODE'), $flag, $enc) : '';
			$resp['PROCESSING_STATUS_CODE']		= $this->Request()->getPost('PROCESSING_STATUS_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_STATUS_CODE'), $flag, $enc) : '';
			$resp['PROCESSING_REASON_CODE']		= $this->Request()->getPost('PROCESSING_REASON_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_REASON_CODE'), $flag, $enc) : '';
			$resp['PROCESSING_REASON']			= $this->Request()->getPost('PROCESSING_REASON') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_REASON'), $flag, $enc) : '';
			$resp['PROCESSING_TIMESTAMP']		= $this->Request()->getPost('PROCESSING_TIMESTAMP') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_TIMESTAMP'), $flag, $enc) : '';
			$resp['PROCESSING_STATUS']			= $this->Request()->getPost('PROCESSING_STATUS') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_STATUS'), $flag, $enc) : '';

			$resp['CRITERION_SHOP_ID']			= $this->Request()->getPost('CRITERION_SHOP_ID') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHOP_ID'), $flag, $enc) : '';
			$resp['CRITERION_USER_ID']			= $this->Request()->getPost('CRITERION_USER_ID') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_USER_ID'), $flag, $enc) : '';
			$resp['CRITERION_DBONRG']			= $this->Request()->getPost('CRITERION_DBONRG') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_DBONRG'), $flag, $enc) : '';
			$resp['CRITERION_SHIPPAY']			= $this->Request()->getPost('CRITERION_SHIPPAY') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHIPPAY'), $flag, $enc) : '';
			$resp['CRITERION_GATEWAY']			= $this->Request()->getPost('CRITERION_GATEWAY') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_GATEWAY'), $flag, $enc) : '';
			$resp['CRITERION_WALLET']			= $this->Request()->getPost('CRITERION_WALLET') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_WALLET'), $flag, $enc) : '';
			$resp['CRITERION_WALLET_PAYNAME']	= $this->Request()->getPost('CRITERION_WALLET_PAYNAME') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_WALLET_PAYNAME'), $flag, $enc) : '';
			$resp['CRITERION_SECRET']			= $this->Request()->getPost('CRITERION_SECRET') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SECRET'), $flag, $enc) : '';
			$resp['CRITERION_SHIPPINGHASH']		= $this->Request()->getPost('CRITERION_SHIPPINGHASH') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHIPPINGHASH'), $flag, $enc) : '';
			$resp['CRITERION_BILLSAFE_REFERENCE']= $this->Request()->getPost('CRITERION_BILLSAFE_REFERENCE') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_BILLSAFE_REFERENCE'), $flag, $enc) : '';
			$resp['CRITERION_SESS']				= $this->Request()->getPost('CRITERION_SESS') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SESS'), $flag, $enc) : '';
			$resp['CRITERION_PUSH_URL']			= $this->Request()->getPost('CRITERION_PUSH_URL') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_PUSH_URL'), $flag, $enc) : '';
			$resp['CRITERION_RESPONSE_URL']		= $this->Request()->getPost('CRITERION_RESPONSE_URL') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_RESPONSE_URL'), $flag, $enc) : '';
			$resp['CRITERION_SHOP_TYPE']		= $this->Request()->getPost('CRITERION_SHOP_TYPE') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHOP_TYPE'), $flag, $enc) : '';
			$resp['CRITERION_MODULE_VERSION']	= $this->Request()->getPost('CRITERION_MODULE_VERSION') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_MODULE_VERSION'), $flag, $enc) : '';
			$resp['SHOPMODULE_VERSION']			= $this->Request()->getPost('SHOPMODULE_VERSION') == true ? htmlspecialchars($this->Request()->getPost('SHOPMODULE_VERSION'), $flag, $enc) : '';

			$resp['PAYMENT_CODE']				= $this->Request()->getPost('PAYMENT_CODE') == true ? htmlspecialchars($this->Request()->getPost('PAYMENT_CODE'), $flag, $enc) : '';

			$resp['PRESENTATION_CURRENCY']		= $this->Request()->getPost('PRESENTATION_CURRENCY') == true ? htmlspecialchars($this->Request()->getPost('PRESENTATION_CURRENCY'), $flag, $enc) : '';
			$resp['PRESENTATION_AMOUNT']		= $this->Request()->getPost('PRESENTATION_AMOUNT') == true ? htmlspecialchars($this->Request()->getPost('PRESENTATION_AMOUNT'), $flag, $enc) : '';

			$resp['IDENTIFICATION_TRANSACTIONID']= $this->Request()->getPost('IDENTIFICATION_TRANSACTIONID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_TRANSACTIONID'), $flag, $enc) : '';
			$resp['IDENTIFICATION_UNIQUEID']	= $this->Request()->getPost('IDENTIFICATION_UNIQUEID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_UNIQUEID'), $flag, $enc) : '';
			$resp['IDENTIFICATION_SHORTID']		= $this->Request()->getPost('IDENTIFICATION_SHORTID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_SHORTID'), $flag, $enc) : '';
			$resp['IDENTIFICATION_CREDITOR_ID']	= $this->Request()->getPost('IDENTIFICATION_CREDITOR_ID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_CREDITOR_ID'), $flag, $enc) : '';
			$resp['IDENTIFICATION_REFERENCEID']	= $this->Request()->getPost('IDENTIFICATION_REFERENCEID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_REFERENCEID'), $flag, $enc) : '';
			$resp['IDENTIFICATION_SHOPPERID']	= $this->Request()->getPost('IDENTIFICATION_SHOPPERID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_SHOPPERID'), $flag, $enc) : '';

			$resp['FRONTEND_MODE']				= $this->Request()->getPost('FRONTEND_MODE') == true ? $this->Request()->getPost('FRONTEND_MODE') : '';
			$resp['FRONTEND_ENABLED']			= $this->Request()->getPost('FRONTEND_ENABLED') == true ? $this->Request()->getPost('FRONTEND_ENABLED') : '';
			$resp['FRONTEND_LANGUAGE']			= $this->Request()->getPost('FRONTEND_LANGUAGE') == true ? $this->Request()->getPost('FRONTEND_LANGUAGE') : '';
			$resp['FRONTEND_CUSTOMERTEXT']		= $this->Request()->getPost('FRONTEND_CUSTOMERTEXT') == true ? $this->Request()->getPost('FRONTEND_CUSTOMERTEXT') : '';

			$resp['ACCOUNT_EXPIRY_MONTH']		= $this->Request()->getPost('ACCOUNT_EXPIRY_MONTH') == true ? $this->Request()->getPost('ACCOUNT_EXPIRY_MONTH') : '';
			$resp['ACCOUNT_EXPIRY_YEAR']		= $this->Request()->getPost('ACCOUNT_EXPIRY_YEAR') == true ? $this->Request()->getPost('ACCOUNT_EXPIRY_YEAR') : '';
			$resp['ACCOUNT_BRAND']				= $this->Request()->getPost('ACCOUNT_BRAND') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BRAND'), $flag, $enc) : '';
			$resp['ACCOUNT_HOLDER']				= $this->Request()->getPost('ACCOUNT_HOLDER') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_HOLDER'), $flag, $enc) : '';
			$resp['ACCOUNT_IBAN']				= $this->Request()->getPost('ACCOUNT_IBAN') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_IBAN'), $flag, $enc) : '';
			$resp['ACCOUNT_BIC']				= $this->Request()->getPost('ACCOUNT_BIC') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BIC'), $flag, $enc) : '';
			$resp['ACCOUNT_NUMBER']				= $this->Request()->getPost('ACCOUNT_NUMBER') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_NUMBER'), $flag, $enc) : '';
			$resp['ACCOUNT_BANK']				= $this->Request()->getPost('ACCOUNT_BANK') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BANK'), $flag, $enc) : '';
			$resp['ACCOUNT_IDENTIFICATION']		= $this->Request()->getPost('ACCOUNT_IDENTIFICATION') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_IDENTIFICATION'), $flag, $enc) : '';

			$resp['CONNECTOR_ACCOUNT_BANK']		= $this->Request()->getPost('CONNECTOR_ACCOUNT_BANK') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_BANK'), $flag, $enc) : '';
			$resp['CONNECTOR_ACCOUNT_BIC']		= $this->Request()->getPost('CONNECTOR_ACCOUNT_BIC') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_BIC'), $flag, $enc) : '';
			$resp['CONNECTOR_ACCOUNT_NUMBER']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_NUMBER') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_NUMBER'), $flag, $enc) : '';
			$resp['CONNECTOR_ACCOUNT_IBAN']		= $this->Request()->getPost('CONNECTOR_ACCOUNT_IBAN') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_IBAN'), $flag, $enc) : '';
			$resp['CONNECTOR_ACCOUNT_COUNTRY']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_COUNTRY') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_COUNTRY'), $flag, $enc) : '';
			$resp['CONNECTOR_ACCOUNT_HOLDER']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_HOLDER') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_HOLDER'), $flag, $enc) : '';
            $resp['CONNECTOR_ACCOUNT_USAGE']	= $this->Request()->getPost('CONNECTOR_ACCOUNT_USAGE') == true ? htmlspecialchars($this->Request()->getPost('CONNECTOR_ACCOUNT_USAGE'), $flag, $enc) : '';

            $resp['NAME_COMPANY']				= $this->Request()->getPost('NAME_COMPANY') == true ? htmlspecialchars($this->Request()->getPost('NAME_COMPANY'), $flag, $enc) : '';
			$resp['NAME_SALUTATION']			= $this->Request()->getPost('NAME_SALUTATION') == true ? htmlspecialchars($this->Request()->getPost('NAME_SALUTATION'), $flag, $enc) : '';
			$resp['NAME_BIRTHDATE']				= $this->Request()->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($this->Request()->getPost('NAME_BIRTHDATE'), $flag, $enc) : '';
			$resp['NAME_FAMILY']				= $this->Request()->getPost('NAME_FAMILY') == true ? htmlspecialchars($this->Request()->getPost('NAME_FAMILY'), $flag, $enc) : '';
			$resp['NAME_GIVEN']					= $this->Request()->getPost('NAME_GIVEN') == true ? htmlspecialchars($this->Request()->getPost('NAME_GIVEN'), $flag, $enc) : '';
			$resp['ADDRESS_STREET']				= $this->Request()->getPost('ADDRESS_STREET') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_STREET'), $flag, $enc) : '';
			$resp['ADDRESS_CITY']				= $this->Request()->getPost('ADDRESS_CITY') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_CITY'), $flag, $enc) : '';
			$resp['ADDRESS_ZIP']				= $this->Request()->getPost('ADDRESS_ZIP') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_ZIP'), $flag, $enc) : '';
			$resp['ADDRESS_COUNTRY']			= $this->Request()->getPost('ADDRESS_COUNTRY') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_COUNTRY'), $flag, $enc) : '';

			$resp['CONTACT_EMAIL']				= $this->Request()->getPost('CONTACT_EMAIL') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_EMAIL'), $flag, $enc) : '';
			$resp['CONTACT_PHONE']				= $this->Request()->getPost('CONTACT_PHONE') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_PHONE'), $flag, $enc) : '';
			$resp['CONTACT_IP']					= $this->Request()->getPost('CONTACT_IP') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_IP'), $flag, $enc) : '';

			$resp['TRANSACTION_CHANNEL']		= $this->Request()->getPost('TRANSACTION_CHANNEL') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_CHANNEL'), $flag, $enc) : '';
			$resp['TRANSACTION_MODE']			= $this->Request()->getPost('TRANSACTION_MODE') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_MODE'), $flag, $enc) : '';

			$resp['CLEARING_AMOUNT']			= $this->Request()->getPost('CLEARING_AMOUNT') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_AMOUNT'), $flag, $enc) : '';
			$resp['CLEARING_CURRENCY']			= $this->Request()->getPost('CLEARING_CURRENCY') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_CURRENCY'), $flag, $enc) : '';
			$resp['CLEARING_DESCRIPTOR']		= $this->Request()->getPost('CLEARING_DESCRIPTOR') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_DESCRIPTOR'), $flag, $enc) : '';

			$resp['var_Register']		= ($this->Request()->getPost('register') == true && gettype($this->Request()->getPost('register')) == 'array') ? $this->Request()->getPost('register') : '';
			if(empty($resp['var_Register'])){
				$resp['var_Register']['payment'] = $this->Request()->getPost('payment') == true ? htmlspecialchars($this->Request()->getPost('payment'), $flag, $enc) : '';
			}
			$resp['var_sTarget']		= $this->Request()->getPost('sTarget') == true ? htmlspecialchars($this->Request()->getPost('sTarget'), $flag, $enc) : '';
			$resp['var_sepa']			= $this->Request()->getPost('hpdd_sepa') == true ? htmlspecialchars($this->Request()->getPost('hpdd_sepa'), $flag, $enc) : '';
			$resp['__csrf_token']		= $this->Request()->getPost('__csrf_token') == true ? htmlspecialchars($this->Request()->getPost('__csrf_token'), $flag, $enc) : '';
			$resp['CONFIG_OPTIN_TEXT']	= $this->Request()->getPost('CONFIG_OPTIN_TEXT') == true ? htmlspecialchars(json_decode($this->Request()->getPost('CONFIG_OPTIN_TEXT'), $flag, $enc),true) : '';

			if (isset($resp['NAME_BIRTHDATE']) && !(empty($resp['NAME_BIRTHDATE'])) ) {
				$resp['NAME_BIRTHDATE'] 	= $resp['NAME_BIRTHDATE'];
			} else {
				$resp['NAME_BIRTHDATE'] 	= $this->Request()->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($this->Request()->getPost('birthdate_san'), $flag, $enc) : '';
			}

			// case for suspected Manipulation
			$orgHash = $this->createSecretHash($resp['IDENTIFICATION_TRANSACTIONID']);
			if($resp['CRITERION_SECRET'] != $orgHash){
				Shopware()->Session()->HPError = '';
				$this->hgw()->Logging(
						"Hash verification error, suspecting manipulation.".
						"<br />PaymentUniqeID: " . $resp['IDENTIFICATION_TRANSACTIONID']  .
						"<br />IP: " . $_SERVER['REMOTE_ADDR'] .
						"<br />Hash: " .htmlspecialchars($orgHash) .
						"<br />ResponseHash: " .htmlspecialchars($resp['CRITERION_SECRET']));
				print Shopware()->Front()->Router()->assemble(array(
						'forceSecure' => 1,
						'action' => 'fail'
				));
				exit;
			}

			//save result to hgw_transactions
			try {
				$this->hgw()->saveRes($resp);
			} catch (Exception $e) {
				$this->hgw()->Logging('responseRegAction saving response failed | '.$e->getMessage());
				return;
			}

			if ($resp['PROCESSING_RESULT'] == 'ACK') {
				// save registration to DB
				switch (substr($resp['PAYMENT_CODE'], 0,2)) {
					case 'CC':
					case 'DC':
						$saved = $this->saveRegData($resp, '', '');
						break;
					case 'DD':
						// saving birthdate for direct debit with guarantee
						$dataToSave = array();
						if ($this->Config()->HGW_DD_GUARANTEE_MODE == 1){
							$dataToSave = self::prepareBirthdate($resp['NAME_BIRTHDATE'],$resp['NAME_SALUTATION']);
						} else {
							$dataToSave = NULL;
						}

    					if (!empty($resp['ACCOUNT_IBAN'])) {
							$saved = $this->saveRegData($resp, $resp['ACCOUNT_IBAN'], $resp['ACCOUNT_BIC'],$dataToSave);
						} else {
							$saved = $this->saveRegData($resp, $resp['ACCOUNT_NUMBER'], $resp['ACCOUNT_BANK'],$dataToSave);
						}
						break;
					default:
						;
						break;
				}

				// routing to account or payment via savePaymentAction
				switch (Shopware()->Shop()->getTemplate()->getVersion()) {
					// Emotion-Template
					case '2':
						// filling SESSION with Values
						Shopware()->Session()->Bot 		= null;
						Shopware()->Session()->sUserId 	= $resp['IDENTIFICATION_SHOPPERID'];

						switch ($resp['var_sTarget']){
							case 'account':
							case 'checkout':
								print Shopware()->Front()->Router()->assemble(array(
								'forceSecure' 	=> 1,
								'controller' 	=> 'PaymentHgw',
								'action'		=> 'savePayment',
								'register' 		=> $resp['var_Register']['payment'],
								'sTarget'		=> $resp['var_sTarget'],
								'txnId'			=> $resp['IDENTIFICATION_TRANSACTIONID'],
								));
								return;
								break;
							default:
    							print Shopware()->Front()->Router()->assemble(array(
								'forceSecure' 	=> 1,
								'controller' 	=> 'checkout',
								'sTarget' 		=> $this->Request()->getPost('sTarget'),
								'sRegister'		=> $resp['var_Register']['payment'],
								));
								return;
								break;
						}
						break;
					// Responsive-Template
					case '3':
						//setting Csrf-Token is required
						$token = 'X-CSRF-Token';
						Shopware()->Session()->$token 	= $resp['__csrf_token'];

						$this->Request()->setPost('sTarget',$resp['var_sTarget']);

						$registrierteZahlart = $resp['var_Register'];
						$this->Request()->setPost('register', $registrierteZahlart['payment']);

						//Fallback case if target is not set
						if(empty($resp['var_sTarget'])){$resp['var_sTarget'] = 'checkout';}

						print Shopware()->Front()->Router()->assemble(array(
								'forceSecure' 	=> 1,
								'controller' 	=> 'PaymentHgw',
								'action'		=> 'savePayment',
								'sRegister' 	=> $registrierteZahlart['payment'],
								'sTarget'		=> $resp['var_sTarget'],
								'txnId'			=> $resp['IDENTIFICATION_TRANSACTIONID'],
                        ));
						return;
						break;
					// Other Templates
					default:
						break;
				}
			}
			else {
				// Registration is NOK
				$token = 'X-CSRF-Token';
				Shopware()->Session()->$token = $resp['__csrf_token'];

				if($resp['CRITERION_GATEWAY'] == '1'){
					Shopware()->Session()->HPError = $resp['PROCESSING_RETURN_CODE'];

					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' 	=> 1,
							'controller' 	=> 'PaymentHgw',
							'action' 		=> 'fail'
                    ));
				}else{
					Shopware()->Session()->HPError = $this->getHPErrorMsg($resp['PROCESSING_RETURN_CODE']);

					if($resp['CRITERION_SHIPPAY'] == '1'){
						print Shopware()->Front()->Router()->assemble(array(
								'forceSecure'	=> 1,
								'controller' 	=> 'PaymentHgw',
								'action' 		=> 'fail'
                        ));

					}else{
						print Shopware()->Front()->Router()->assemble(array(
								'forceSecure' 	=> 1,
								'controller' 	=> 'PaymentHgw',
								'action' 		=> 'fail',
								'sTarget' 		=> 'payment'
						));
					}
				}
			}
		}
	}

    /** responseHprAction()
     * receives response from payment for HP.PA and .IN with special Criterion Parameters,
     * saves transactions to transaction table,
     * redirects to successAction() or failAction()
     */
    public function responseHprAction() {
        unset(Shopware()->Session()->HPError);
        if($this->Request()->isPost()){
            $flag = ENT_COMPAT;
            $enc = 'UTF-8';
            if($this->Request()->getPost('TRANSACTION_SOURCE') == false){ $this->Request()->setPost('TRANSACTION_SOURCE', 'RESPONSE'); }

            $flag = ENT_COMPAT;
            $enc = 'UTF-8';
            if($this->Request()->getPost('TRANSACTION_SOURCE') == false){ $this->Request()->setPost('TRANSACTION_SOURCE', 'RESPONSE'); }
            $resp['REQUEST_VERSION']			= $this->Request()->getPost('REQUEST_VERSION') == true ? htmlspecialchars($this->Request()->getPost('REQUEST_VERSION'), $flag, $enc) : '';
            $resp['SECURITY_SENDER']			= $this->Request()->getPost('SECURITY_SENDER') == true ? htmlspecialchars($this->Request()->getPost('SECURITY_SENDER'), $flag, $enc) : '';
            $resp['USER_LOGIN']					= $this->Request()->getPost('USER_LOGIN') == true ? htmlspecialchars($this->Request()->getPost('USER_LOGIN'), $flag, $enc) : '';
            $resp['USER_PWD']					= $this->Request()->getPost('USER_PWD') == true ? htmlspecialchars($this->Request()->getPost('USER_PWD'), $flag, $enc) : '';
            $resp['TRANSACTION_CHANNEL']		= $this->Request()->getPost('TRANSACTION_CHANNEL') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_CHANNEL'), $flag, $enc) : '';

            $resp['PROCESSING_RESULT']			= $this->Request()->getPost('PROCESSING_RESULT') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RESULT'), $flag, $enc) : '';
            $resp['PROCESSING_RETURN']			= $this->Request()->getPost('PROCESSING_RETURN') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RETURN'), $flag, $enc) : '';
            $resp['PROCESSING_CODE']			= $this->Request()->getPost('PROCESSING_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_CODE'), $flag, $enc) : '';
            $resp['PROCESSING_RETURN_CODE']		= $this->Request()->getPost('PROCESSING_RETURN_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_RETURN_CODE'), $flag, $enc) : '';
            $resp['PROCESSING_STATUS_CODE']		= $this->Request()->getPost('PROCESSING_STATUS_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_STATUS_CODE'), $flag, $enc) : '';
            $resp['PROCESSING_REASON_CODE']		= $this->Request()->getPost('PROCESSING_REASON_CODE') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_REASON_CODE'), $flag, $enc) : '';
            $resp['PROCESSING_REASON']			= $this->Request()->getPost('PROCESSING_REASON') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_REASON'), $flag, $enc) : '';
            $resp['PROCESSING_TIMESTAMP']		= $this->Request()->getPost('PROCESSING_TIMESTAMP') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_TIMESTAMP'), $flag, $enc) : '';
            $resp['PROCESSING_STATUS']			= $this->Request()->getPost('PROCESSING_STATUS') == true ? htmlspecialchars($this->Request()->getPost('PROCESSING_STATUS'), $flag, $enc) : '';

            // special criterions for HPR
            $resp['CRITERION_EASYCREDIT_FIRSTRATEDUEDATE']	= $this->Request()->getPost('CRITERION_EASYCREDIT_FIRSTRATEDUEDATE') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_FIRSTRATEDUEDATE'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_AMORTISATIONTEXTT']	= $this->Request()->getPost('CRITERION_EASYCREDIT_AMORTISATIONTEXT') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_AMORTISATIONTEXT'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_TOTALRUNTIME']		= $this->Request()->getPost('CRITERION_EASYCREDIT_TOTALRUNTIME') 		== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_TOTALRUNTIME'), $flag, $enc) : '';
            $resp['CONFIG_OPTIN_EASYCREDIT']				= $this->Request()->getPost('CONFIG_OPTIN_TEXT') 					== true ? htmlspecialchars($this->Request()->getPost('CONFIG_OPTIN_TEXT'), $flag, $enc) : '';

            $resp['CRITERION_EASYCREDIT_MONTHLYRATECOUNT']	= $this->Request()->getPost('CRITERION_EASYCREDIT_MONTHLYRATECOUNT') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_MONTHLYRATECOUNT'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_PRECONTRACTINFORMATIONURL']	= $this->Request()->getPost('CRITERION_EASYCREDIT_PRECONTRACTINFORMATIONURL') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_PRECONTRACTINFORMATIONURL'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_LASTRATEAMOUNT']	= $this->Request()->getPost('CRITERION_EASYCREDIT_LASTRATEAMOUNT') 		== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_LASTRATEAMOUNT'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_MONTHLYRATEAMOUNT']	= $this->Request()->getPost('CRITERION_EASYCREDIT_MONTHLYRATEAMOUNT') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_MONTHLYRATEAMOUNT'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_NOMINALINTEREST']	= $this->Request()->getPost('CRITERION_EASYCREDIT_NOMINALINTEREST') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_NOMINALINTEREST'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_ACCRUINGINTEREST']	= $this->Request()->getPost('CRITERION_EASYCREDIT_ACCRUINGINTEREST') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_ACCRUINGINTEREST'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_TOTALAMOUNT']		= $this->Request()->getPost('CRITERION_EASYCREDIT_TOTALAMOUNT') 		== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_TOTALAMOUNT'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_LASTRATEDUEDATE']	= $this->Request()->getPost('CRITERION_EASYCREDIT_LASTRATEDUEDATE') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_LASTRATEDUEDATE'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_TOTALORDERAMOUNT']	= $this->Request()->getPost('CRITERION_EASYCREDIT_TOTALORDERAMOUNT') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_TOTALORDERAMOUNT'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_EFFECTIVEINTEREST']	= $this->Request()->getPost('CRITERION_EASYCREDIT_EFFECTIVEINTEREST') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_EFFECTIVEINTEREST'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_DEVICEIDENTTOKEN']	= $this->Request()->getPost('CRITERION_EASYCREDIT_DEVICEIDENTTOKEN') 	== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_DEVICEIDENTTOKEN'), $flag, $enc) : '';
            $resp['CRITERION_EASYCREDIT_UUID']				= $this->Request()->getPost('CRITERION_EASYCREDIT_UUID') 				== true ? htmlspecialchars($this->Request()->getPost('CRITERION_EASYCREDIT_UUID'), $flag, $enc) : '';

            $resp['RISKINFORMATION.CUSTOMERGUESTCHECKOUT']	= $this->Request()->getPost('RISKINFORMATION.CUSTOMERGUESTCHECKOUT') 	== true ? htmlspecialchars($this->Request()->getPost('RISKINFORMATION.CUSTOMERGUESTCHECKOUT'), $flag, $enc) : '';
            $resp['RISKINFORMATION.CUSTOMERSINCE']			= $this->Request()->getPost('RISKINFORMATION.CUSTOMERSINCE') 			== true ? htmlspecialchars($this->Request()->getPost('RISKINFORMATION.CUSTOMERSINCE'), $flag, $enc) : '';
            $resp['RISKINFORMATION.CUSTOMERORDERCOUNT']		= $this->Request()->getPost('RISKINFORMATION.CUSTOMERORDERCOUNT') 		== true ? htmlspecialchars($this->Request()->getPost('RISKINFORMATION.CUSTOMERORDERCOUNT'), $flag, $enc) : '';

            $resp['CRITERION_SHOP_ID']			= $this->Request()->getPost('CRITERION_SHOP_ID') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHOP_ID'), $flag, $enc) : '';
            $resp['CRITERION_SECRET']			= $this->Request()->getPost('CRITERION_SECRET') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SECRET'), $flag, $enc) : '';
            $resp['CRITERION_SESS']				= $this->Request()->getPost('CRITERION_SESS') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SESS'), $flag, $enc) : '';
            $resp['CRITERION_PUSH_URL']			= $this->Request()->getPost('CRITERION_PUSH_URL') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_PUSH_URL'), $flag, $enc) : '';
            $resp['CRITERION_SHOP_TYPE']		= $this->Request()->getPost('CRITERION_SHOP_TYPE') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_SHOP_TYPE'), $flag, $enc) : '';
            $resp['SHOP_TYPE']					= $this->Request()->getPost('SHOP_TYPE') == true ? htmlspecialchars($this->Request()->getPost('SHOP_TYPE'), $flag, $enc) : '';
            $resp['SHOPMODULE_VERSION']			= $this->Request()->getPost('SHOPMODULE_VERSION') == true ? htmlspecialchars($this->Request()->getPost('SHOPMODULE_VERSION'), $flag, $enc) : '';
            $resp['CRITERION_MODULE_VERSION']	= $this->Request()->getPost('CRITERION_MODULE_VERSION') == true ? htmlspecialchars($this->Request()->getPost('CRITERION_MODULE_VERSION'), $flag, $enc) : '';

            $resp['PAYMENT_CODE']				= $this->Request()->getPost('PAYMENT_CODE') == true ? htmlspecialchars($this->Request()->getPost('PAYMENT_CODE'), $flag, $enc) : '';

            $resp['PRESENTATION_CURRENCY']		= $this->Request()->getPost('PRESENTATION_CURRENCY') == true ? htmlspecialchars($this->Request()->getPost('PRESENTATION_CURRENCY'), $flag, $enc) : '';
            $resp['PRESENTATION_AMOUNT']		= $this->Request()->getPost('PRESENTATION_AMOUNT') == true ? htmlspecialchars($this->Request()->getPost('PRESENTATION_AMOUNT'), $flag, $enc) : '';

            $resp['IDENTIFICATION_TRANSACTIONID']= $this->Request()->getPost('IDENTIFICATION_TRANSACTIONID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_TRANSACTIONID'), $flag, $enc) : '';
            $resp['IDENTIFICATION_UNIQUEID']	= $this->Request()->getPost('IDENTIFICATION_UNIQUEID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_UNIQUEID'), $flag, $enc) : '';
            $resp['IDENTIFICATION_SHORTID']		= $this->Request()->getPost('IDENTIFICATION_SHORTID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_SHORTID'), $flag, $enc) : '';
            $resp['IDENTIFICATION_SHOPPERID']	= $this->Request()->getPost('IDENTIFICATION_SHOPPERID') == true ? htmlspecialchars($this->Request()->getPost('IDENTIFICATION_SHOPPERID'), $flag, $enc) : '';
            $resp['CLEARING_DESCRIPTOR']		= $this->Request()->getPost('CLEARING_DESCRIPTOR') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_DESCRIPTOR'), $flag, $enc) : '';
            $resp['CLEARING_CURRENCY']			= $this->Request()->getPost('CLEARING_CURRENCY') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_CURRENCY'), $flag, $enc) : '';
            $resp['CLEARING_AMOUNT']			= $this->Request()->getPost('CLEARING_AMOUNT') == true ? htmlspecialchars($this->Request()->getPost('CLEARING_AMOUNT'), $flag, $enc) : '';

            $resp['BASKET_ID']					= $this->Request()->getPost('BASKET_ID') == true ? htmlspecialchars($this->Request()->getPost('BASKET_ID'), $flag, $enc) : '';

            $resp['FRONTEND_MODE']				= $this->Request()->getPost('FRONTEND_MODE') == true ? $this->Request()->getPost('FRONTEND_MODE') : '';
            $resp['FRONTEND_RESPONSE_URL']		= $this->Request()->getPost('FRONTEND_RESPONSE_URL') == true ? $this->Request()->getPost('FRONTEND_RESPONSE_URL') : '';
            $resp['FRONTEND_ENABLED']			= $this->Request()->getPost('FRONTEND_ENABLED') == true ? $this->Request()->getPost('FRONTEND_ENABLED') : '';
            $resp['FRONTEND_LANGUAGE']			= $this->Request()->getPost('FRONTEND_LANGUAGE') == true ? $this->Request()->getPost('FRONTEND_LANGUAGE') : '';

            $resp['ACCOUNT_BRAND']				= $this->Request()->getPost('ACCOUNT_BRAND') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_BRAND'), $flag, $enc) : '';
            $resp['ACCOUNT_HOLDER']				= $this->Request()->getPost('ACCOUNT_HOLDER') == true ? htmlspecialchars($this->Request()->getPost('ACCOUNT_HOLDER'), $flag, $enc) : '';

            $resp['NAME_COMPANY']				= $this->Request()->getPost('NAME_COMPANY') == true ? htmlspecialchars($this->Request()->getPost('NAME_COMPANY'), $flag, $enc) : '';
            $resp['NAME_SALUTATION']			= $this->Request()->getPost('NAME_SALUTATION') == true ? htmlspecialchars($this->Request()->getPost('NAME_SALUTATION'), $flag, $enc) : '';

            $resp['NAME_BIRTHDATE']				= $this->Request()->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($this->Request()->getPost('NAME_BIRTHDATE'), $flag, $enc) : '';
            $resp['NAME_FAMILY']				= $this->Request()->getPost('NAME_FAMILY') == true ? htmlspecialchars($this->Request()->getPost('NAME_FAMILY'), $flag, $enc) : '';
            $resp['NAME_GIVEN']					= $this->Request()->getPost('NAME_GIVEN') == true ? htmlspecialchars($this->Request()->getPost('NAME_GIVEN'), $flag, $enc) : '';
            $resp['ADDRESS_STREET']				= $this->Request()->getPost('ADDRESS_STREET') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_STREET'), $flag, $enc) : '';
            $resp['ADDRESS_CITY']				= $this->Request()->getPost('ADDRESS_CITY') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_CITY'), $flag, $enc) : '';
            $resp['ADDRESS_ZIP']				= $this->Request()->getPost('ADDRESS_ZIP') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_ZIP'), $flag, $enc) : '';
            $resp['ADDRESS_COUNTRY']			= $this->Request()->getPost('ADDRESS_COUNTRY') == true ? htmlspecialchars($this->Request()->getPost('ADDRESS_COUNTRY'), $flag, $enc) : '';

            $resp['CONTACT_EMAIL']				= $this->Request()->getPost('CONTACT_EMAIL') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_EMAIL'), $flag, $enc) : '';
            $resp['CONTACT_PHONE']				= $this->Request()->getPost('CONTACT_PHONE') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_PHONE'), $flag, $enc) : '';
            $resp['CONTACT_IP']					= $this->Request()->getPost('CONTACT_IP') == true ? htmlspecialchars($this->Request()->getPost('CONTACT_IP'), $flag, $enc) : '';

            $resp['TRANSACTION_CHANNEL']		= $this->Request()->getPost('TRANSACTION_CHANNEL') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_CHANNEL'), $flag, $enc) : '';
            $resp['TRANSACTION_MODE']			= $this->Request()->getPost('TRANSACTION_MODE') == true ? htmlspecialchars($this->Request()->getPost('TRANSACTION_MODE'), $flag, $enc) : '';

            $resp['var_Register']				= ($this->Request()->getPost('register') == true && gettype($this->Request()->getPost('register')) == 'array') ? $this->Request()->getPost('register') : '';
            if(empty($resp['var_Register'])){
                $resp['var_Register']['payment'] = $this->Request()->getPost('payment') == true ? htmlspecialchars($this->Request()->getPost('payment'), $flag, $enc) : '';
            }
            $resp['var_sTarget']				= $this->Request()->getPost('sTarget') == true ? htmlspecialchars($this->Request()->getPost('sTarget'), $flag, $enc) : '';
            $resp['var_sepa']					= $this->Request()->getPost('hpdd_sepa') == true ? htmlspecialchars($this->Request()->getPost('hpdd_sepa'), $flag, $enc) : '';
            $resp['__csrf_token']				= $this->Request()->getPost('__csrf_token') == true ? htmlspecialchars($this->Request()->getPost('__csrf_token'), $flag, $enc) : '';

            if (isset($resp['NAME_BIRTHDATE']) && !(empty($resp['NAME_BIRTHDATE'])) ) {
                $resp['NAME_BIRTHDATE'] 		= $resp['NAME_BIRTHDATE'];
            } else {
                $resp['NAME_BIRTHDATE'] 		= $this->Request()->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($this->Request()->getPost('birthdate_san'), $flag, $enc) : '';
            }

            // case for suspected Manipulation
            $orgHash = $this->createSecretHash($resp['IDENTIFICATION_TRANSACTIONID']);
            if($resp['CRITERION_SECRET'] != $orgHash){
                Shopware()->Session()->HPError = '';
                $this->hgw()->Logging(
                    "Hash verification error, suspecting manipulation.".
                    "<br />PaymentUniqeID: " . $resp['IDENTIFICATION_TRANSACTIONID']  .
                    "<br />IP: " . $_SERVER['REMOTE_ADDR'] .
                    "<br />Hash: " .htmlspecialchars($orgHash) .
                    "<br />ResponseHash: " .htmlspecialchars($resp['CRITERION_SECRET']));
                print Shopware()->Front()->Router()->assemble(array(
                    'forceSecure' => 1,
                    'action' => 'fail'
                ));
                exit;
            }
            //save Temporary Order-Id to Session to get the order in failAction() or successAction()
            Shopware()->Session()->HPOrderID = $resp['IDENTIFICATION_TRANSACTIONID'];
            Shopware()->Session()->sessionId = $resp['CRITERION_SESS'];

            if ($resp['PROCESSING_RESULT'] == 'ACK') {
                switch ($resp['PAYMENT_CODE'] == 'HP.IN') {
                    case 'HP.IN':
                        //save result to hgw_transactions
                        $this->hgw()->saveRes($resp);

                        // Weiterleitung auf "checkout/confirm";
                        print Shopware()->Front()->Router()->assemble(array(
                            'forceSecure' 	=> 1,
                            'controller' 	=> 'paymentHgw',
                            'action' 		=> 'afterEasy',
                        ));
                        break;
                    case 'HP.PA':
                        //save result to hgw_transactions
                        $this->hgw()->saveRes($resp);

                        // Weiterleitung auf "success";
                        print Shopware()->Front()->Router()->assemble(array(
                            'forceSecure' => 1,
                            'controller' => 'PaymentHgw',
                            'action' => 'success'
                        ));
                        break;

                    default:
                        print Shopware()->Front()->Router()->assemble(array(
                            'forceSecure' => 1,
                            'controller' => 'checkout',
                            'action' => 'confirm'
                        ));
                        break;
                }

            }else {
                //save result to hgw_transactions
                $this->hgw()->saveRes($resp);
                Shopware()->Session()->HPOrderID = $resp['IDENTIFICATION_TRANSACTIONID'];

                // Weiterleitung auf Fehlercontroller
                print Shopware()->Front()->Router()->assemble(array(
                    'forceSecure' => 1,
                    'controller' => 'PaymentHgw',
                    'action' => 'fail'
                ));
                return;
            }


        }
    }

	/**
	 * Recurring payment action method.
	 */
	public function recurringAction(){
		try{
			if(!$this->getAmount() || $this->getOrderNumber()){
				$this->redirect(array(
						'controller' => 'checkout'
				));
				return;
			}

			$params = array();
			$orderId = $this->Request()->getParam('orderId');
			// get payData from first order and uid from registration for this payType
			// necessary to do a debit on registration with Abo Commerce
			$sql = '
				SELECT s_core_paymentmeans.id, s_core_paymentmeans.name
				FROM s_order, s_core_paymentmeans
				WHERE s_order.paymentID = s_core_paymentmeans.id
				AND s_order.id = ?';
			$payData = Shopware()->Db()->fetchAll($sql, array($orderId));

			if(substr($payData[0]['name'], 4) == 'pay'){
				$pm = 'va';
			}else{ $pm = substr($payData[0]['name'], 4); }

			$sql = '
				SELECT payType, uid
				FROM s_plugin_hgw_regdata
				WHERE payType = ?
				AND userID = ?';
			$user = $this->getUser();
			$data = Shopware()->Db()->fetchAll($sql, array($pm, $user['additional']['user']['id']));

			$tempID = $this->createPaymentUniqueId();
			Shopware()->Session()->HPOrderID = $tempID;

			$booking = 'HGW_'.strtoupper($data[0]['payType']).'_BOOKING_MODE';
			$ppd_config = $this->hgw()->ppd_config($this->Config()->$booking, $data[0]['payType'], $data[0]['uid'], true, true);

			$ppd_frontend['FRONTEND.ENABLED'] = 'false';
			$ppd_user = $this->hgw()->ppd_user(NULL, $pm);

			$ppd_bskt['PRESENTATION.AMOUNT'] = $this->hgw()->formatNumber($this->getAmount());
			$ppd_bskt['PRESENTATION.CURRENCY'] = Shopware()->Currency()->getShortName();

			$ppd_crit['CRITERION.USER_ID']	= $user['additional']['user']['id'];
			$ppd_crit['CRITERION.SECRET'] = Shopware_Controllers_Frontend_PaymentHgw::createSecretHash($tempID);

			$ppd_crit['IDENTIFICATION.TRANSACTIONID'] = $tempID;
			$params = $this->preparePostData($ppd_config, $ppd_frontend, $ppd_user, $ppd_bskt, $ppd_crit,true);

			// always save order, whether successful or not and than set order status to NOK if necessary
			$response = $this->hgw()->doRequest($params);
			if($response['PROCESSING_RESULT'] == 'ACK' and $response['PROCESSING_REASON_CODE'] == '00'){
				$status		= '12';
				$comment	= 'ShortID: '.$response['IDENTIFICATION_SHORTID'];
			}else{
				$status 		= '35';
				if(!empty($data)){
					$comment	= 'Error: '.$response['PROCESSING_RETURN'];
				}else{
					$sql = '
						SELECT s_core_paymentmeans.description
						FROM s_order, s_core_paymentmeans
						WHERE s_order.paymentID = s_core_paymentmeans.id
						AND s_order.id = ?';
					$data = Shopware()->Db()->fetchAll($sql, array($orderId));
					$comment	= 'Error: No registration found - Please provide a valid registration for '.$data[0]['description'];
				}
				if(empty($response['IDENTIFICATION_UNIQUEID'])){ $response['IDENTIFICATION_UNIQUEID'] = ' '; }
			}

			$return = $this->saveOrder($response['IDENTIFICATION_TRANSACTIONID'], $response['IDENTIFICATION_UNIQUEID'], $status);

			$params = array(
					'o_attr1' => $response['IDENTIFICATION_SHORTID'],
					'o_attr2' => $response['IDENTIFICATION_UNIQUEID'],
					'internalcomment' => strip_tags($comment),
					'paymentID' => $payData[0]['id']
			);
			$this->addOrderInfos($response['IDENTIFICATION_TRANSACTIONID'], $params, $status);
		}catch(Exception $e){
			$this->hgw()->Logging('recurringAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Cancel action method
	 */
	public function cancelAction(){}

	/**
	 * Action to save payment(id) after successful registration
	 */
	public function savePaymentAction(){
		try{
		    $swVersion = Shopware::VERSION;
		    $tplVersion = Shopware()->Shop()->getTemplate()->getVersion();

			$postparams = array();
            $postparams = array('payment' => $this->Request()->getParam('sRegister'));

			$_SERVER['REQUEST_METHOD'] = 'GET';
			$this->Request()->setPost('isPost', 'true');
			$transaction = $this->getHgwTransactions($this->Request()->txnId);

			$token = json_decode($transaction['jsonresponse']);

			$tokenNameSession = 'X-CSRF-Token';
			$tokenNameResponse = '__csrf_token';
			Shopware()->Session()->$tokenNameSession = $token->$tokenNameResponse;

			if($swVersion < 5.3)
			{
			    $postparams['payment'] = $token->var_Register->payment;
                Shopware()->Session()->sRegister = $postparams;
            }

			$this->Request()->setPost('sRegister', $postparams);

			$target = false;
			$target = $this->Request()->getParam('sTarget');
			if(!empty($target)){
				$this->Request()->setParam('sTarget', $target);
			}

			$this->Request()->setParam('__csrf', $token->$tokenNameResponse);

			//Update s_user in payment
			$userData = Shopware()->Modules()->Admin()->sGetUserData();
			$updateSql = 'UPDATE `s_user` SET `paymentID` = ? WHERE `id` = ?';
			$parameter = array($postparams['payment'],$userData['additional']['user']['id']);
			try{
				Shopware()->Db()->query($updateSql,$parameter);
			} catch(Exception $e) {
				$this->hgw()->Logging('savePaymentAction | changing paymenthod failed| '.$e->getMessage());
			}

            if($swVersion < 5.3)
            {   // bis SW 5.2.27 aktuell
			    $this->forward('savePayment', 'account', '', $postparams);
            } else {
                // funktionstüchtig für SW 5.3
                $this-> redirect(array(
                        'controller' => 'checkout',
                        'action' => 'confirm',
                        'sTarget' => 'checkout',
                        'sTargetAction' => 'confirm',
                    )
                );
            }

		}catch(Exception $e){
			//Shopware()->Plugins()->Logging('savePaymentAction | '.$e->getMessage());
			Shopware()->Plugins()->Frontend()->Heidelgateway()->Logging('savePaymentAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Action to save birthdate for hgw_papg on payment selection
	 */
	public function saveBirthdateAction(){

		$postData = $this->Request()->getPost();
		/* gets data from birthdayform */
		if(($postData['Date_Day'] == '') || ($postData['Date_Month'] == '') || ($postData['Date_Year'] == '')){
			return $this->forward('fail');
			exit;
		}
		/* helpers to get data */
		// sometimes "$this->getUser" delivers no informations

		if ($this->getUser() != false) {
			$userInfo = $this->getUser();
		} else {
			$userInfo = Shopware()->Modules()->Admin()->sGetUserData();
		}

		$channel 				= strtoupper($userInfo['additional']['payment']['name']).'_CHANNEL';
		/* collect Data to be saved */
		$resp['pay_Code']		= preg_replace('/hgw_/', '', $userInfo['additional']['payment']['name']);
		$resp['crit_UserId']	= $userInfo['additional']['user']['customerId'];
		$resp['cnt_Mail']		= $userInfo['additional']['user']['email'];
		$resp['trans_Chan']		= $this->Config()->$channel;
		$resp['ident_Uid']		= '';
		$resp['acc_Numb']		= '';
		$resp['acc_ExpMon']		= '';
		$resp['acc_ExpYear']	= '';
		$resp['acc_Holder']		= $userInfo['billingaddress']['firstname'].' '.$userInfo['billingaddress']['lastname'];
		switch ($resp['pay_Code']) {
			case 'papg':
				$resp['acc_Brand'] = 'CMS';
				break;
			case 'san':
				$resp['acc_Brand'] = 'SAN';
				break;
			default:
				$resp['acc_Brand'] = '';
				break;
		}

		$address['birthdate']['day']		= $postData['Date_Day'];
		$address['birthdate']['month']		= $postData['Date_Month'];
		$address['birthdate']['year']		= $postData['Date_Year'];
		$address['birthdate']['NAME_BIRTHDATE']	= $postData['Date_Year'].'-'.$postData['Date_Month'].'-'.$postData['Date_Day'];

		if($this->saveRegData($resp, '', '', $address, true) === false){
			return $this->forward('fail');
			exit;
		}else{

			if(strpos(strtolower($_SERVER['HTTP_REFERER']), 'shippingpayment') !== false){
				$this->Request()->setParam('sTarget', 'checkout');
				$this->Request()->setParam('sTargetAction', 'index');
				$_SERVER['REQUEST_METHOD'] = 'POST';
    			$this->forward('saveShippingPayment', 'checkout', '', $postData);
			}else{

				return $this->redirect(array(
						'forceSecure' => 1,
						'controller' => 'PaymentHgw',
						'action' => 'savePayment',
						'appendSession' => 'SESSION_ID',
						'register' => $postData['register']['payment'],
						'sTarget' => $postData['sTarget']
				));
			}
		}
	}

	/**
	 * Fail action method.
	 * For error and cancel case.
	 */
	public function failAction(){
		try{
            if (empty(Shopware()->Session()->HPOrderID)) {
                $transaction = $this->getHgwTransactions(Shopware()->Session()->sessionId);
                $parameters = json_decode($transaction['jsonresponse']);
            } else {
                $transaction = $this->getHgwTransactions(Shopware()->Session()->HPOrderID);
                $parameters = json_decode($transaction['jsonresponse']);
            }

			$payType = $transaction['payment_method'];
			$transType = $transaction['payment_type'];

			// cancelation MPA, HP
            if (
                $parameters->CRITERION_GATEWAY == '1' ||
                (strtolower($payType) == 'wt') ||
                (strtolower($payType)== 'hp')
            )
            {
                Shopware()->Session()->HPError = $this->getHPErrorMsg($parameters->PROCESSING_RETURN_CODE);

                print Shopware()->Front()->Router()->assemble(array(
						'forceSecure' => 1,
						'controller' => 'PaymentHgw',
						'action' => 'fail',
						'appendSession' => 'SESSION_ID'
				));
			} else{
				Shopware()->Session()->HPError = $this->getHPErrorMsg($parameters->PROCESSING_RETURN_CODE);

				if($parameters->CRITERION_SHIPPAY == '1'){
					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' => 1,
							'controller' => 'checkout',
							'action' => 'shippingPayment',
							'appendSession' => 'SESSION_ID'
					));
				}else{
                    $this->View()->ErrorMessage = Shopware()->Session()->HPError;
                    $this->View()->sErrorMessage = $this->getHPErrorMsg(Shopware()->Session()->HPError);
					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure' => 1,
							'controller' => 'account',
							'action' => 'payment',
							'sTarget' => $parameters->sTarget,
							'appendSession' => 'SESSION_ID'
					));
				}
			}

            unset(Shopware()->Session()->HPdidRequest);
            unset(Shopware()->Session()->wantEasy);

			Shopware()->Template()->addTemplateDir(dirname(__FILE__).'/Views/');
			$this->View()->back2basket = 1;
			$this->View()->ErrorMessage = Shopware()->Session()->HPError;
            $this->View()->sErrorMessage = $this->getHPErrorMsg(Shopware()->Session()->HPError);
			unset(Shopware()->Session()->HPError);
		}catch(Exception $e){
			Shopware()->Plugins()->HeidelGateway()->Logging('failAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Success action method.
	 * Transactions will be forwarded.
	 */
	public function successAction(){
		try{
            unset(Shopware()->Session()->HPError);
            unset($this->View()->amortisationText);

            // get transaction from hgw_transactions
            if (empty(Shopware()->Session()->HPOrderID)) {
                $transaction = $this->getHgwTransactions(Shopware()->Session()->sessionId);
                $parameters = json_decode($transaction['jsonresponse']);
            } else {
                $transaction = $this->getHgwTransactions(Shopware()->Session()->HPOrderID);
                $parameters = json_decode($transaction['jsonresponse']);
            }

			if ($parameters->PROCESSING_RESULT == 'NOK') {
				Shopware()->Session()->HPError = $parameters->PROCESSING_RETURN_CODE;
				print Shopware()->Front()->Router()->assemble(array(
						'forceSecure' 	=> 1,
						'controller' 	=> 'PaymentHgw',
						'action' 		=> 'fail',
						'appendSession' => 'SESSION_ID'
				));
				return;
			}

			$payType 	= $transaction['payment_method'];
			$transType 	= $transaction['payment_type'];
			$kto = $blz = '';

			if(($parameters->CRITERION_WALLET == '1') && (strtoupper($transType) == 'IN')){
				Shopware()->Session()->HPResp = $parameters;

				print Shopware()->Front()->Router()->assemble(array(
						'forceSecure' 	=> 1,
						'controller' 	=> 'PaymentHgw',
						'action' 		=> 'createAcc',
						'appendSession' => 'SESSION_ID'
				));
				exit;
			}

			if(
					(strtolower($transType) == 'db') ||
					(strtolower($transType) == 'pa') ||
					(((strtolower($payType) == 'ot') || (strtolower($payType) == 'pc')) && (strtolower($transType) == 'rc'))
					){
						// debit or reservation: finish Order
						if(strtolower($transType) == 'pa' || $parameters->PROCESSING_STATUS_CODE == '80'){
							$paymentStatus = '18'; // reserved
							// IV and PP Payment sould be set to "review necessary" and not to "reserved"
							if($payType == 'IV' || $payType == 'PP'){
								$paymentStatus = '21'; // review necessary
							}

						}else{
							$paymentStatus = '12'; // paid
						}

						//setting infos for internal comment for customers
						//$comment = "ShortID: ".$parameters->IDENTIFICATION_SHORTID."\n";
						$locId = (Shopware()->Locale()->getLanguage() == 'de') ? 1 : 2;

						$repl = array(
								'{AMOUNT}'						=> str_replace(".",",",$this->hgw()->formatNumber($this->getAmount())),
								'{CURRENCY}'					=> $this->getCurrencyShortName(),
								'{CONNECTOR_ACCOUNT_COUNTRY}'	=> $parameters->CONNECTOR_ACCOUNT_COUNTRY."\n",
								'{CONNECTOR_ACCOUNT_HOLDER}'	=> $parameters->CONNECTOR_ACCOUNT_HOLDER."\n",
								'{CONNECTOR_ACCOUNT_NUMBER}'	=> $parameters->CONNECTOR_ACCOUNT_NUMBER."\n",
								'{CONNECTOR_ACCOUNT_BANK}'		=> $parameters->CONNECTOR_ACCOUNT_BANK."\n",
								'{CONNECTOR_ACCOUNT_IBAN}'		=> $parameters->CONNECTOR_ACCOUNT_IBAN."\n",
								'{CONNECTOR_ACCOUNT_BIC}'		=> $parameters->CONNECTOR_ACCOUNT_BIC."\n\n",
								'{IDENTIFICATION_SHORTID}'		=> "\n".$parameters->IDENTIFICATION_SHORTID,
						);

                        if(
                            (strtolower($payType) == 'iv') ||
                            (strtolower($payType) == 'papg') ||
                            (strtolower($payType) == 'ivpd') ||
                            (strtolower($payType) == 'pp')
                        ) {
                            $comment .= '<strong>' . $this->getSnippet('InvoiceHeader', $locId) . ": </strong>";
                            $comment .= strtr($this->getSnippet('PrepaymentText', $locId), $repl);

                            if($parameters->ACCOUNT_BRAND == "SANTANDER" || $parameters->ACCOUNT_BRAND == "PAYOLUTION_DIRECT")
                            {
                                $repl = array(
                                    '{AMOUNT}'						=> str_replace(".",",",$this->hgw()->formatNumber($this->getAmount())),
                                    '{CURRENCY}'					=> $this->getCurrencyShortName(),
                                    '{CONNECTOR_ACCOUNT_COUNTRY}'	=> $parameters->CONNECTOR_ACCOUNT_COUNTRY."\n",
                                    '{CONNECTOR_ACCOUNT_HOLDER}'	=> $parameters->CONNECTOR_ACCOUNT_HOLDER."\n",
                                    '{CONNECTOR_ACCOUNT_NUMBER}'	=> $parameters->CONNECTOR_ACCOUNT_NUMBER."\n",
                                    '{CONNECTOR_ACCOUNT_BANK}'		=> $parameters->CONNECTOR_ACCOUNT_BANK."\n",
                                    '{CONNECTOR_ACCOUNT_IBAN}'		=> $parameters->CONNECTOR_ACCOUNT_IBAN."\n",
                                    '{CONNECTOR_ACCOUNT_BIC}'		=> $parameters->CONNECTOR_ACCOUNT_BIC."\n\n",
                                    '{CONNECTOR_ACCOUNT_USAGE}'		=> "\n".$parameters->CONNECTOR_ACCOUNT_USAGE,
                                );
                                $comment = '<strong>' . $this->getSnippet('InvoiceHeader', $locId) . ": </strong>";

                                switch ($parameters->ACCOUNT_BRAND){
                                    case 'SANTANDER':
                                        $comment .= strtr($this->getSnippet('PrepaymentSanText', $locId), $repl);
                                        break;
                                    case 'PAYOLUTION_DIRECT':
                                        $comment .= strtr($this->getSnippet('PrepaymentIvpdText', $locId), $repl);
                                        break;
                                    default:
                                        $comment .= strtr($this->getSnippet('PrepaymentText', $locId), $repl);
                                        break;
                                }

                            }
						}else{
                            $comment = '<strong>' . $this->getSnippet('InvoiceHeader', $locId) . ": </strong>";
							$comment.= strtr($this->getSnippet('PrepaymentText', $locId), $repl);
						}

						$comment = nl2br($comment);
						$comment = str_replace('Konto: ','Konto: <br />',$comment);

						// Fix to compare Basket amount with payment amount and set order status to
						$swAmount = $this->getAmount();
						$hpAmount = floatval(trim($parameters->PRESENTATION_AMOUNT));

						if ($swAmount - $hpAmount >= 0.01) {
							$paymentStatus = '21'; // review necessary
							// expand the comment for order in case of suspected manipulation
							$comment .= ' | Suspected manipulation! Please check the amount of the order and the amount payment | Amount paid: '.$responseAmount;
							$params = array(
									'internalcomment' => ' | Suspected manipulation! Please check the amount of the order and the amount payment | Amount paid: '.$responseAmount,
							);
						}

						Shopware()->Session()->HPTrans = $parameters->IDENTIFICATION_UNIQUEID;
						$return = $this->saveOrder($parameters->IDENTIFICATION_TRANSACTIONID, $parameters->IDENTIFICATION_UNIQUEID, $paymentStatus);

						Shopware()->Session()->sOrderVariables['sTransactionumber'] = $parameters->IDENTIFICATION_TRANSACTIONID;

						switch (strtolower($payType)) {

							case 'cc':
							case 'dc':
							case 'ot':
							case 'va':
								unset($comment);
								break;

							case 'dd':
								unset($comment);
								$locId = (Shopware()->Locale()->getLanguage() == 'de') ? 1 : 2;

								Shopware()->Session()->sOrderVariables['accountAmount']		= $parameters->PRESENTATION_AMOUNT;
								Shopware()->Session()->sOrderVariables['accountCurrency']	= $parameters->PRESENTATION_CURRENCY;
								Shopware()->Session()->sOrderVariables['accountIdent'] 		= $parameters->ACCOUNT_IDENTIFICATION;//$resp['acc_Ident'];
								Shopware()->Session()->sOrderVariables['accountIban'] 		= $parameters->ACCOUNT_IBAN;//$resp['acc_Iban'];
								Shopware()->Session()->sOrderVariables['accountBic'] 		= $parameters->ACCOUNT_BIC != '' ? $parameters->ACCOUNT_BIC : '';
								Shopware()->Session()->sOrderVariables['identCreditorId'] 	= $parameters->IDENTIFICATION_CREDITOR_ID ;//$resp['ident_CredId'];

								// write comment to front- and backend
								if($parameters->IDENTIFICATION_CREDITOR_ID != ''){
									$comment = '<strong>'.$this->getSnippet('InvoiceHeader', $locId).":</strong></br>";
									$repl = array(
											'{$smarty.session.Shopware.sOrderVariables->accountAmount}' 	=> $parameters->PRESENTATION_AMOUNT,
											'{$smarty.session.Shopware.sOrderVariables->accountCurrency}' 	=> $parameters->PRESENTATION_CURRENCY,
											'{$smarty.session.Shopware.sOrderVariables->accountIban}' 		=> $parameters->ACCOUNT_IBAN,
											'{$smarty.session.Shopware.sOrderVariables->accountBic}' 		=> $parameters->ACCOUNT_BIC,
											'{$smarty.session.Shopware.sOrderVariables->accountIdent}' 		=> $parameters->IDENTIFICATION_SHORTID,
									);
									$comment.= strtr($this->getSnippet('accountIdent', $locId,'frontend/checkout/finish'), $repl).'</br>';

									$setIn = array(
											'{$smarty.session.Shopware.sOrderVariables->identCreditorId}' 	=> $parameters->IDENTIFICATION_CREDITOR_ID,
									);
									$comment.= ' '.strtr($this->getSnippet('identCreditorId', $locId, 'frontend/checkout/finish'), $setIn);

								}

								// sending DirectDebit-Email
								if($this->Config()->HGW_DD_MAIL > 0){
									$user = Shopware()->Modules()->Admin()->sGetUserData($parameters->CRITERION_USER_ID);
									$orderNum = $this->getOrder($parameters->IDENTIFICATION_TRANSACTIONID);
									$directdebitData = array(
											'ACCOUNT_IBAN'			=> $parameters->ACCOUNT_IBAN,
											'ACCOUNT_BIC' 			=> $parameters->ACCOUNT_BIC != '' ? $parameters->ACCOUNT_BIC : '',
											'ACCOUNT_IDENT'			=> $parameters->ACCOUNT_IDENTIFICATION,
											'IDENT_CREDITOR_ID'		=> $parameters->IDENTIFICATION_CREDITOR_ID,
									);
									$this->prepaymentMail($orderNum['ordernumber'] , $user['additional']['user']['email'], $directdebitData, 'directdebitHeidelpay');
								}
								break;

							case 'wt':
								Shopware()->Session()->sOrderVariables['payType'] 		= $payType;
								Shopware()->Session()->sOrderVariables['contactMail'] 	= $parameters->CONTACT_EMAIL;
								Shopware()->Session()->sOrderVariables['accountExpMon'] = $parameters->ACCOUNT_EXPIRY_MONTH;
								Shopware()->Session()->sOrderVariables['accountExpYear']= $parameters->ACCOUNT_EXPIRY_YEAR;
								Shopware()->Session()->sOrderVariables['accountNr'] 	= $parameters->ACCOUNT_NUMBER;
								Shopware()->Session()->sOrderVariables['accountBrand'] 	= $this->getBrandName($parameters->ACCOUNT_BRAND);
								break;

							case 'iv';
								// setting Comments for frontend and Backend
								Shopware()->Session()->sOrderVariables['prepaymentText'] = $comment;
								// Santander saving birthdate
								$nameOfCriterion = 'CRITERION_INSURANCE-RESERVATION';
								if (
								    $parameters->$nameOfCriterion == 'ACCEPTED'||
                                    $parameters->ACCOUNT_BRAND == 'SANTANDER' ||
                                    $parameters->ACCOUNT_BRAND == 'PAYOLUTION_DIRECT'
                                ) {
									$birthdayArray = explode('-', $parameters->NAME_BIRTHDATE);
									if (!empty($birthdayArray)) {
										$regDataParams['NAME_SALUTATION'] 	= $parameters->NAME_SALUTATION;
                                        $regDataParams['NAME_BIRTHDATE']    = $parameters->NAME_BIRTHDATE;
										if($parameters->ACCOUNT_BRAND == 'SANTANDER'){
                                            $regDataParams['CUSTOMER_OPTIN'] 	                = strtoupper($parameters->CUSTOMER_OPTIN);
                                            $regDataParams['CUSTOMER_ACCEPT_PRIVACY_POLICY'] 	= strtoupper($parameters->CUSTOMER_OPTIN_2);
                                        }

										$parametersToSave = json_decode($transaction['jsonresponse'],1);

										try{
											$this->saveRegData($parametersToSave, '', '',$regDataParams);
										} catch (Exception $e){
											$this->hgw()->Logging('successAction CMS / Santander | saving birthdate to Db failed | '.$e->getMessage());
										}
									}
								}

								//sending Invoice email
								if($this->Config()->HGW_IV_MAIL > 0){
									$repl = array(
											'{AMOUNT}'						=> str_replace(".",",",$parameters->PRESENTATION_AMOUNT),
											'{CURRENCY}'					=> $parameters->PRESENTATION_CURRENCY,
											'{CONNECTOR_ACCOUNT_COUNTRY}'	=> $parameters->CONNECTOR_ACCOUNT_COUNTRY."\n",
											'{CONNECTOR_ACCOUNT_HOLDER}'	=> $parameters->CONNECTOR_ACCOUNT_HOLDER."\n",
											'{CONNECTOR_ACCOUNT_NUMBER}'	=> $parameters->CONNECTOR_ACCOUNT_NUMBER."\n",
											'{CONNECTOR_ACCOUNT_BANK}'		=> $parameters->CONNECTOR_ACCOUNT_BANK."\n",
											'{CONNECTOR_ACCOUNT_IBAN}'		=> $parameters->CONNECTOR_ACCOUNT_IBAN."\n",
											'{CONNECTOR_ACCOUNT_BIC}'		=> $parameters->CONNECTOR_ACCOUNT_BIC."\n\n",
											'{IDENTIFICATION_SHORTID}'		=> "\n".$parameters->IDENTIFICATION_SHORTID,
									);

                                    if($parameters->ACCOUNT_BRAND == "SANTANDER")
                                    {
                                        $repl = array(
                                            '{AMOUNT}'						=> str_replace(".",",",$parameters->PRESENTATION_AMOUNT),
                                            '{CURRENCY}'					=> $parameters->PRESENTATION_CURRENCY,
                                            '{CONNECTOR_ACCOUNT_COUNTRY}'	=> $parameters->CONNECTOR_ACCOUNT_COUNTRY."\n",
                                            '{CONNECTOR_ACCOUNT_HOLDER}'	=> $parameters->CONNECTOR_ACCOUNT_HOLDER."\n",
                                            '{CONNECTOR_ACCOUNT_NUMBER}'	=> $parameters->CONNECTOR_ACCOUNT_NUMBER."\n",
                                            '{CONNECTOR_ACCOUNT_BANK}'		=> $parameters->CONNECTOR_ACCOUNT_BANK."\n",
                                            '{CONNECTOR_ACCOUNT_IBAN}'		=> $parameters->CONNECTOR_ACCOUNT_IBAN."\n",
                                            '{CONNECTOR_ACCOUNT_BIC}'		=> $parameters->CONNECTOR_ACCOUNT_BIC."\n\n",
                                            '{CONNECTOR_ACCOUNT_USAGE}'		=> "\n".$parameters->CONNECTOR_ACCOUNT_USAGE,
                                        );
                                    }

                                    if($parameters->ACCOUNT_BRAND == "PAYOLUTION_DIRECT")
                                    {
                                        $repl = array(
                                            '{AMOUNT}'						=> str_replace(".",",",$parameters->PRESENTATION_AMOUNT),
                                            '{CURRENCY}'					=> $parameters->PRESENTATION_CURRENCY,
                                            '{CONNECTOR_ACCOUNT_COUNTRY}'	=> $parameters->CONNECTOR_ACCOUNT_COUNTRY."\n",
                                            '{CONNECTOR_ACCOUNT_HOLDER}'	=> $parameters->CONNECTOR_ACCOUNT_HOLDER."\n",
                                            '{CONNECTOR_ACCOUNT_IBAN}'		=> $parameters->CONNECTOR_ACCOUNT_IBAN."\n",
                                            '{CONNECTOR_ACCOUNT_BIC}'		=> $parameters->CONNECTOR_ACCOUNT_BIC."\n\n",
                                            '{CONNECTOR_ACCOUNT_USAGE}'		=> "\n".$parameters->CONNECTOR_ACCOUNT_USAGE,
                                        );
                                    }

									$orderNum = $this->getOrder($transactionId);
									$prepayment = array();
									$user = $this->getUser();
									foreach($repl AS $k => $v){
										$key = preg_replace('/{/', '', $k);
										$key = preg_replace('/}/', '', $key);
										$prepayment[$key] = $v;
									}

                                    if($parameters->ACCOUNT_BRAND == "SANTANDER"){
                                        $this->prepaymentMail($orderNum['ordernumber'], $user['additional']['user']['email'], $prepayment,'invoiceSanHeidelpay');
                                    } elseif ($parameters->ACCOUNT_BRAND == "PAYOLUTION_DIRECT") {
                                        // send E-Mail to customer
                                        $this->prepaymentMail($orderNum['ordernumber'], $user['additional']['user']['email'], $prepayment,'invoiceIvpdHeidelpay');
                                        // send E-Mail to Payolution-E-Mail-Adress
                                    }
                                    else{
                                        $this->prepaymentMail($orderNum['ordernumber'], $user['additional']['user']['email'], $prepayment);
                                    }
								}
							break;

							case 'pp':
								Shopware()->Session()->sOrderVariables['prepaymentText'] = $comment;
								// sendeing Prepayment Email
								if($this->Config()->HGW_PP_MAIL > 0){
									$repl = array(
											'{AMOUNT}'						=> str_replace(".",",",$parameters->PRESENTATION_AMOUNT),
											'{CURRENCY}'					=> $parameters->PRESENTATION_CURRENCY,
											'{CONNECTOR_ACCOUNT_COUNTRY}'	=> $parameters->CONNECTOR_ACCOUNT_COUNTRY."\n",
											'{CONNECTOR_ACCOUNT_HOLDER}'	=> $parameters->CONNECTOR_ACCOUNT_HOLDER."\n",
											'{CONNECTOR_ACCOUNT_NUMBER}'	=> $parameters->CONNECTOR_ACCOUNT_NUMBER."\n",
											'{CONNECTOR_ACCOUNT_BANK}'		=> $parameters->CONNECTOR_ACCOUNT_BANK."\n",
											'{CONNECTOR_ACCOUNT_IBAN}'		=> $parameters->CONNECTOR_ACCOUNT_IBAN."\n",
											'{CONNECTOR_ACCOUNT_BIC}'		=> $parameters->CONNECTOR_ACCOUNT_BIC."\n\n",
											'{IDENTIFICATION_SHORTID}'		=> "\n".$parameters->IDENTIFICATION_SHORTID,
									);

									if (!empty($transactionId)) {
                                        $orderNum = $this->getOrder($transactionId);
                                    } else {
                                        $orderNum = $this->getOrder($parameters->IDENTIFICATION_TRANSACTIONID);
                                    }

									$prepayment = array();
									$user = $this->getUser();
									foreach($repl AS $k => $v){
										$key = preg_replace('/{/', '', $k);
										$key = preg_replace('/}/', '', $key);
										$prepayment[$key] = $v;
									}
									$this->prepaymentMail($orderNum['ordernumber'], $user['additional']['user']['email'], $prepayment);
								}

								break;

                            case 'hp':
                                unset(Shopware()->Session()->HPdidRequest);
                                unset(Shopware()->Session()->wantEasy);
                                unset($this->View()->configOptInText);
                                unset($this->View()->amortisationText);

                                unset($comment);
                                //delete chosen payment of user
                                $user = Shopware()->Modules()->Admin()->sGetUserData();
                                break;

							default:
								break;
						}

						// Invoice-Payment
						switch ($parameters->ACCOUNT_BRAND) {
							case 'BILLSAFE':
								$comment .= '<br />BillSafe Referenz: '. $parameters->CRITERION_BILLSAFE_REFERENCE;
								break;

							case 'SANTANDER':
								$birthdayArray = explode('-', $parameters->NAME_BIRTHDATE);
								if (!empty($birthdayArray)) {
									$birthdate['NAME_SALUTATION'] 	= $parameters->NAME_SALUTATION;
									$birthdate['NAME_BIRTHDATE']    = $parameters->NAME_BIRTHDATE;
									$birthdate['CUSTOMER_OPTIN']    = $parameters->CUSTOMER_OPTIN;
									$birthdate['CUSTOMER_ACCEPT_PRIVACY_POLICY']= $parameters->CUSTOMER_OPTIN_2;
									$parametersToSave = json_decode($transaction['jsonresponse'],1);

									try{
										$this->saveRegData($parametersToSave, '', '',$birthdate);
									} catch (Exception $e){
										$this->hgw()->Logging('successAction SANTANDER | saving birthdate to Db failed | '.$e->getMessage());
										unset($rueckGabe);
									}
								}
								break;
							default:
								;
								break;
						}

						$params = array(
								'comment' => htmlspecialchars_decode($comment),
								'internalcomment' => 'Short-Id: '.$parameters->IDENTIFICATION_SHORTID,
						);

						$this->addOrderInfos($parameters->IDENTIFICATION_TRANSACTIONID, $params, $paymentStatus);

						print Shopware()->Front()->Router()->assemble(array(
								'forceSecure' 	=> 1,
								'controller' 	=> 'PaymentHgw',
								'action' 		=> 'success',
								'appendSession' => 'SESSION_ID'
						));

			} else {
                switch (strtolower($payType)) {
                    case 'dd':
                        if ($parameters->var_sepa == 'iban') {
                            $kto = substr($parameters->ACCOUNT_IBAN, 0, 2) . str_repeat('*', strlen($parameters->ACCOUNT_IBAN) - 6) . substr($parameters->ACCOUNT_IBAN, -4);
                            $blz = str_repeat('*', strlen($parameters->ACCOUNT_BIC) - 4) . substr($parameters->ACCOUNT_BIC, -4);
                        } else {
                            $kto = str_repeat('*', strlen($parameters->ACCOUNT_NUMBER) - 4) . substr($parameters->ACCOUNT_NUMBER, -4);
                            $blz = str_repeat('*', strlen($parameters->ACCOUNT_BANK) - 4) . substr($parameters->ACCOUNT_BANK, -4);
                        }
                        $parameters->ACCOUNT_NUMBER = '';

                        // prepare Values of response to save in DB
                        if ($this->Config()->HGW_DD_GUARANTEE_MODE == 1) {

                            $birthdayArray = explode('-', $parameters->NAME_BIRTHDATE);
                            $address = array(
                                'NAME_SALUTATION' => $parameters->NAME_SALUTATION,
                                'birthdate' =>
                                    array(
                                        'day'   => $birthdayArray[2],
                                        'month' => $birthdayArray[1],
                                        'year'  => $birthdayArray[0],
                                        'NAME_BIRTHDATE' => $parameters->NAME_BIRTHDATE
                                    )
                            );
                            $this->saveRegData(json_decode($transaction['jsonresponse'], 1), $kto, $blz, $address);
                        } else {
                            $this->saveRegData(json_decode($transaction['jsonresponse'], 1), $kto, $blz);
                        }
                        break;
                    case 'hp':
                        unset(Shopware()->Session()->HPdidRequest);
                        unset(Shopware()->Session()->wantEasy);
                        break;
                }

				if($parameters->CRITERION_DBONRG === '1'){
					print Shopware()->Front()->Router()->assemble(array(
							'forceSecure'	=> 1,
							'controller' 	=> 'PaymentHgw',
							'action' 		=> 'gateway'
					));
				}else{
					if($parameters->CRITERION_SHIPPAY == '1'){
						print Shopware()->Front()->Router()->assemble(array(
								'forceSecure' 	=> 1,
								'controller'	=> 'checkout',
								'appendSession' => 'SESSION_ID'
						));
					}else{
						// save Payment
						if (isset($parameters->__csrf_token)) {
							print Shopware()->Front()->Router()->assemble(array(
									'forceSecure' 	=> 1,
									'controller' 	=> 'PaymentHgw',
									'action' 		=> 'savePayment',
									'appendSession' => 'SESSION_ID',
									'register' 		=> $resp['var_Register']['payment'],
									'sTarget' 		=> $parameters->var_sTarget,
									'__csrf_token' 	=> $parameters->__csrf_token
							));
						} else {
							print Shopware()->Front()->Router()->assemble(array(
									'forceSecure' 	=> 1,
									'controller' 	=> 'PaymentHgw',
									'action' 		=> 'savePayment',
									'appendSession' => 'SESSION_ID',
									'register' 		=> $resp['var_Register']['payment'],
									'sTarget' 		=> $parameters->var_sTarget
							));
						}
					}
				}
            }

			return $this->redirect(array(
					'controller' 	=> 'checkout',
					'action' 		=> 'finish',
					'forceSecure'	=> 1,
					'sUniqueID' 	=> Shopware()->Session()->HPTrans
			)
					);
		}catch(Exception $e){
			Shopware()->Plugins()->HeidelGateway()->Logging('successAction | '.$e->getMessage());
			// 			$this->hgw()->Logging('successAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * helper method
	 * returns payment plugin configuration
	 * @return unknown
	 */
	public function Config(){
		return self::hgw()->Config();
	}

	/**
	 * Method to generate a hash
	 * @param sting $orderID - order id
	 * @return string $hash
	 */
	public function createSecretHash($orderID){

		$konfiguration = self::Config();
		$secret = $konfiguration['HGW_SECRET'];

		$hash = hash('sha512', $orderID.$secret);

		return $hash;
	}

	/**
	 * Method to get a text snippet form database
	 * @param string $name - name of the value
	 * @param string $localeId - languarge id
	 * @param string $ns - namespace
	 * @param int $shopId
	 * @return string - text snippet
	 */
	public function getSnippet($name, $localeId, $ns = 'frontend/payment_heidelpay/success', $shopId = 1){
		try{
			$sql = 'SELECT `value` FROM `s_core_snippets` WHERE `namespace` = ? AND `shopID` = ? AND `localeID` = ? AND `name` = ?';
			$data = current(Shopware()->Db()->fetchAll($sql, array($ns, $shopId, $localeId, $name)));

			return $data['value'];
		}catch(Exception $e){
			$this->hgw()->Logging('getSnippet | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to load all order details (by transaction id)
	 * @param string $transactionId
	 * @return array - Order details from s_order table
	 */
	public function getOrder($transactionId){
		try{
			$sql = 'SELECT * FROM `s_order` WHERE `transactionID` = ?';
			$data = current(Shopware()->Db()->fetchAll($sql, $transactionId));

			return $data;
		}catch(Exception $e){
			$this->hgw()->Logging('getOrder | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to load all order details (by session id)
	 * @param string $sessionId
	 * @return array - Order details from s_order table
	 */
	public function getOrderBySession($sessionId){
		try{
			$sql = 'SELECT * FROM `s_order` WHERE `temporaryID` = ?';
			$data = Shopware()->Db()->fetchRow($sql, $sessionId);

			return $data;
		}catch(Exception $e){
			$this->hgw()->Logging('getOrderBySession | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to add information to order
	 * @param string $transactionID
	 * @param array $params
	 * @param array $status - paymet status
	 */
	public function addOrderInfos($transactionID, $params, $status=NULL){
		try{
			Shopware()->Models()->clear();
			$orderModel = Shopware()
			->Models()
			->getRepository('Shopware\Models\Order\Order')
			->findOneBy(array('transactionId' => $transactionID));

            if($status == '12' && $orderModel != NULL )
            {
                $orderModel->setClearedDate(date('d.m.Y H:i:s'));
            }
			// if internalComment is set, read old commment and add time stamp
			$alterWert = $orderModel->getInternalComment();
			if(!empty($params['internalcomment'])){
				$params['internalcomment'] = date('d.m.Y H:i:s') . "\n" . $params['internalcomment'] . "\n \n" . $alterWert;
			}else{
				$params['internalcomment'] = $alterWert;
			}

			// mapping database -> model
			$orderMappings = array(
					'ordernumber' => 'number',
					'userID' => 'customerId',
					'invoice_amount' => 'invoiceAmount',
					'invoice_amount_net' => 'invoiceAmountNet',
					'invoice_shipping' => 'invoiceShipping',
					'invoice_shipping_net' => 'invoiceShippingNet',
					'ordertime' => 'orderTime',
					'status' => 'status',
					'cleared' => 'cleared', // Payment Status model
					'paymentID' => 'paymentId',
					'transactionID' => 'transactionId',
					'comment' => 'comment',
					'customercomment' => 'customerComment',
					'internalcomment' => 'internalComment',
					'net' => 'net',
					'taxfree' => 'taxFree',
					'partnerID' => 'partnerId',
					'temporaryID' => 'temporaryId',
					'referer' => 'referer',
					'cleareddate' => 'clearedDate',
					'trackingcode' => 'trackingCode',
					'language' => 'languageIso',
					'dispatchID' => 'dispatch', // dispatch model
					'currency' => 'currency',
					'currencyFactor' => 'currencyFactor',
					'subshopID'=> 'shopId',
					'remote_addr' => 'remoteAddress'
			);

			$attributeMapping = array(
					'o_attr1' => 'attribute1',
					'o_attr2' => 'attribute2',
					'o_attr3' => 'attribute3',
					'o_attr4' => 'attribute4',
					'o_attr5' => 'attribute5',
					'o_attr6' => 'attribute6'
			);

			$newData	= array();
			$attribute		= array();
			// order mapping
			foreach ($orderMappings as $key => $mapping){
				if(isset($params[$key])){
					$newData[$mapping] = $params[$key];
				}
			}
			// attribute mapping
			foreach($attributeMapping as $key => $mapping){
				if(isset($params[$key])){
					$attribute[$mapping] = $params[$key];
				}
			}
			if(!empty($attribute)){
				$newData['attribute'] = $attribute;
			}
			// check if the cleared parameter is passed and update the order
			if(isset($params['cleared']) && ($params['cleared'] != '')){
				$sql = 'UPDATE `s_order` SET `cleared` = ? WHERE `transactionID` = ?';
				Shopware()->Db()->query($sql,array((int)$params['cleared'], $transactionID));
			}
			// check if the paymentId parameter is passed and update the order
			if(isset($params['paymentID']) && ($params['paymentID'] != '')){
				$sql = 'UPDATE `s_order` SET `paymentID` = ? WHERE `transactionID` = ?';
				Shopware()->Db()->query($sql, array($params['paymentID'], $transactionID));
			}

			// populate Model with data
			$orderModel->fromArray($newData);
			Shopware()->Models()->persist($orderModel);
			// save to database
			Shopware()->Models()->flush();
		}catch(Exception $e){
			// 			$this->hgw()->Logging('addOrderInfos | '.$e->getMessage());
			self::hgw()->Logging('addOrderInfos | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get the current cleared status
	 * @param stirng $tempID - temporary order id
	 * @return int $dat - cleared ID
	 */
	private function getOrderPaymentStatus($tempID){
		try{
			$sql = 'SELECT `cleared` FROM `s_order` WHERE `transactionID` = ?';
			$dat = Shopware()->Db()->fetchOne($sql, $tempID);
            return $dat;
		}catch(Exception $e){
			$this->hgw()->Logging('getOrderPaymentStatus | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Rawnotify action method.
	 * Method for the heidelpay push notification system
	 */
	public function rawnotifyAction(){
		try{
			$rawBody = $this->Request()->getRawBody();
			if(empty($rawBody)){ exit; }

			// hack to remove a structure problem in criterion nodes
			$rawPost = preg_replace('/<Criterion(\s+)name="(\w+)">(.+)<\/Criterion>/', '<$2>$3</$2>', $rawBody);
			$xml = simplexml_load_string($rawPost);

			$xml->Transaction['mode'] == true ? $xmlData['TRANSACTION_MODE'] = (string)$xml->Transaction['mode'] : '';
			$xml->Transaction['channel'] == true ? $xmlData['TRANSACTION_CHANNEL'] = (string)$xml->Transaction['channel'] : '';
			//		$xml->Transaction['source'] == true ? $xmlData['TRANSACTION_SOURCE'] = (string)$xml->Transaction['source'] : '';
			$xml->Transaction->Identification->UniqueID == true ? $xmlData['IDENTIFICATION_UNIQUEID'] = (string)$xml->Transaction->Identification->UniqueID : '';
			$xml->Transaction->Identification->ShortID == true ? $xmlData['IDENTIFICATION_SHORTID'] = (string)$xml->Transaction->Identification->ShortID : '';
			$xml->Transaction->Identification->TransactionID == true ? $xmlData['IDENTIFICATION_TRANSACTIONID'] = (string)$xml->Transaction->Identification->TransactionID : '';
			$xml->Transaction->Identification->ReferenceID == true ? $xmlData['IDENTIFICATION_REFERENCEID'] = (string)$xml->Transaction->Identification->ReferenceID : '';
			$xml->Transaction->Processing->Result == true ? $xmlData['PROCESSING_RESULT'] = (string)$xml->Transaction->Processing->Result : '';
			$xml->Transaction->Processing->Status['code'] == true ? $xmlData['PROCESSING_STATUS_CODE'] = (string)$xml->Transaction->Processing->Status['code'] : '';
			$xml->Transaction->Processing->Return == true ? $xmlData['PROCESSING_RETURN'] = (string)$xml->Transaction->Processing->Return : '';
			$xml->Transaction->Processing->Return['code'] == true ? $xmlData['PROCESSING_RETURN_CODE'] = (string)$xml->Transaction->Processing->Return['code'] : '';
			$xml->Transaction->Processing->Timestamp == true ? $xmlData['PROCESSING_TIMESTAMP'] = (string)$xml->Transaction->Processing->Timestamp : '';
			$xml->Transaction->Payment['code'] == true ? $xmlData['PAYMENT_CODE'] = (string)$xml->Transaction->Payment['code'] : '';
			$xml->Transaction->Payment->Presentation->Amount == true ? $xmlData['PRESENTATION_AMOUNT'] = (string)$xml->Transaction->Payment->Presentation->Amount : '';
			$xml->Transaction->Payment->Presentation->Currency == true ? $xmlData['PRESENTATION_CURRENCY'] = (string)$xml->Transaction->Payment->Presentation->Currency : '';
			$xml->Transaction->Connector->Account->Country == true ? $xmlData['CONNECTOR_ACCOUNT_COUNTRY'] = (string)$xml->Transaction->Connector->Account->Country : '';
			$xml->Transaction->Connector->Account->Holder == true ? $xmlData['CONNECTOR_ACCOUNT_HOLDER'] = (string)$xml->Transaction->Connector->Account->Holder : '';
			$xml->Transaction->Connector->Account->Iban == true ? $xmlData['CONNECTOR_ACCOUNT_IBAN'] = (string)$xml->Transaction->Connector->Account->Iban : '';
			$xml->Transaction->Connector->Account->Bic == true ? $xmlData['CONNECTOR_ACCOUNT_BIC'] = (string)$xml->Transaction->Connector->Account->Bic : '';
			$xml->Transaction->Connector->Account->Number == true ? $xmlData['CONNECTOR_ACCOUNT_NUMBER'] = (string)$xml->Transaction->Connector->Account->Number : '';
			$xml->Transaction->Connector->Account->Bank == true ? $xmlData['CONNECTOR_ACCOUNT_BANK'] = (string)$xml->Transaction->Connector->Account->Bank : '';
			$xml->Transaction->Account->Brand == true ? $xmlData['ACCOUNT_BRAND'] = (string)$xml->Transaction->Account->Brand : '';
			$xml->Transaction->Analysis->SESS == true ? $xmlData['CRITERION_SESS'] = (string)$xml->Transaction->Analysis->SESS : '';
			$xml->Transaction->Analysis->SHOP_ID == true ? $xmlData['CRITERION_SHOP_ID'] = (string)$xml->Transaction->Analysis->SHOP_ID : '';
			$xml->Transaction->Analysis->TEMPORDER == true ? $xmlData['CRITERION_TEMPORDER'] = (string)$xml->Transaction->Analysis->TEMPORDER : '';
			$xml->Transaction->Analysis->IVBRAND == true ? $xmlData['CRITERION_IVBRAND'] = (string)$xml->Transaction->Analysis->IVBRAND : '';
			$xml->Transaction->Analysis->SECRET == true ? $xmlData['SECRET'] = (string)$xml->Transaction->Analysis->SECRET : '';
			$xmlData['TRANSACTION_SOURCE'] = 'PUSH';

			if(empty($xml->Transaction->Identification->ReferenceID) && !empty($xml->Transaction->Analysis->ACCOUNT_REGISTRATION)){
				$xmlData['IDENTIFICATION_REFERENCEID'] = (string)$xml->Transaction->Analysis->ACCOUNT_REGISTRATION;
			}

//			$orgHash = $this->createSecretHash($xmlData['IDENTIFICATION_TRANSACTIONID']);
            // Anpassung weil bei EasyCredit die SessionId als TransactionId benutzt wird
            if($xmlData['PAYMENT_CODE'] == 'HP.PA' || $xmlData['PAYMENT_CODE'] == 'HP.IN'){
                $orgHash = $this->createSecretHash($xmlData['CRITERION_SESS']);
            } else {
                $orgHash = $this->createSecretHash($xmlData['IDENTIFICATION_TRANSACTIONID']);
            }
			$crit_Secret = $xmlData['SECRET'];
			if($crit_Secret != $orgHash){
				Shopware()->Session()->HPError = '';
				$this->hgw()->Logging(
						"Hash verification error, suspecting manipulation.".
						"<br />TransactionID: " . $xmlData['IDENTIFICATION_TRANSACTIONID'].
						"<br />IP: " . $_SERVER['REMOTE_ADDR'] .
						"<br />Hash: " .htmlspecialchars($orgHash) .
						"<br />ResponseHash: " .htmlspecialchars($crit_Secret)
						);
				header('HTTP/1.1 200 OK');
				exit;
			}

			$this->hgw()->createTransactionsTable();
			try{
				$this->hgw()->saveRes($xmlData);
			}catch(Exception $e){
				// message 1062: Duplicate entry '%s' for key %d | https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
				if($e->getPrevious()->errorInfo['1'] == '1062'){
					// check if PUSH 'result', 'statuscode', 'return' or 'returncode' differ from DB entry and update, if so.
					$sql = '
						SELECT * FROM `s_plugin_hgw_transactions`
						WHERE `transactionid` = ?
						AND `uniqueid` = ?
					';

					$params = array($xmlData['IDENTIFICATION_TRANSACTIONID'], $xmlData['IDENTIFICATION_UNIQUEID']);
					$data = Shopware()->Db()->fetchRow($sql, $params);

					if(
						($data['result'] != $xmlData['PROCESSING_RESULT']) ||
						($data['statuscode'] != $xmlData['PROCESSING_STATUS_CODE']) ||
						($data['return'] != $xmlData['PROCESSING_RETURN']) ||
						($data['returncode'] != $xmlData['PROCESSING_RETURN_CODE']) ){
						$sql = '
							UPDATE `s_plugin_hgw_transactions`
							SET `result` = ?,
							`statuscode` = ?,
							`return` = ?,
							`returncode` = ?,
							`jsonresponse` = ?,
							`source` = ?
							WHERE `transactionid` = ?
							AND `uniqueid` = ?
						';
						$params = array($xmlData['PROCESSING_RESULT'], $xmlData['PROCESSING_STATUS_CODE'], $xmlData['PROCESSING_RETURN'], $xmlData['PROCESSING_RETURN_CODE'], json_encode($xmlData), $xmlData['TRANSACTION_SOURCE'], $xmlData['IDENTIFICATION_TRANSACTIONID'], $xmlData['IDENTIFICATION_UNIQUEID']);
                        Shopware()->Db()->query($sql,$params);
					}

					if(($data['statuscode'] == '80') && ($data['statuscode'] != $xmlData['PROCESSING_STATUS_CODE']) && (strtoupper($xmlData['PROCESSING_RESULT']) == 'ACK')){
						goto updatestatus;
					}

					// Buchung bereits gefunden
					header('HTTP/1.1 200 OK');
					exit;
				}else{
					throw $e;
				}
			}

			updatestatus:
			$url = (string)$xml->Transaction->Analysis->RESPONSE_URL;
			$order = $this->getOrder($xmlData['IDENTIFICATION_TRANSACTIONID']);
			if(strtoupper((string)$xml->Transaction->Processing->Status) != 'WAITING'){
				if(empty($order)){
					if(!empty($url)){
						$this->hgw()->doRequest($xmlData, $url); // send response to shop via POST
					}else{
						if($e->getPrevious()->errorInfo['1'] != '1062'){
							$this->hgw()->Logging('rawnotifyAction | response_url missing');
						}
					}
				}else{
					// Do NOT set Paymentstatus to paid if an "FI" comes in
					if(stripos($xmlData['PAYMENT_CODE'],'.FI') > 0 && $xmlData['ACCOUNT_BRAND'] != 'BILLSAFE'){
						return;
					} else {
						$this->updateOrderStatus($xmlData, $order); // set status also.
					}
				}
			}

			header('HTTP/1.1 200 OK');
			$this->View()->MES = 'OK';
		}catch(Exception $e){
			Shopware()->Plugins()->HeidelGateway()->Logging('rawnotifyAction | '.$e->getMessage());
			//$this->hgw()->Logging('rawnotifyAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Notify action method.
	 * This action is necessary to send the the push data via post to the shop
	 * so it's possible to load the order by session
	 */
	public function notifyAction(){
		try{
			if($this->Request()->isPost()){
				$order			= array();
				$sessionID 		= $this->Request()->getPost('CRITERION_SESS');
				$postData 		= $this->Request()->getPost();
				$setComment = true;

				$orgHash = $this->createSecretHash($this->Request()->getPost('IDENTIFICATION_TRANSACTIONID'));
				$crit_Secret =$this->Request()->getPost('SECRET');

				if($crit_Secret != $orgHash){
					Shopware()->Session()->HPError = '';
					$this->hgw()->Logging(
							"Hash verification error, suspecting manipulation.".
							"<br />TransactionID: " . $this->Request()->getPost('IDENTIFICATION_TRANSACTIONID').
							"<br />IP: " . $_SERVER['REMOTE_ADDR'] .
							"<br />Hash: " .htmlspecialchars($orgHash) .
							"<br />ResponseHash: " .htmlspecialchars($crit_Secret)
							);
					header('HTTP/1.1 200 OK');
					exit;
				}

				if($sessionID != ''){
					$order = $this->getOrderBySession($sessionID);
					$setComment = false;
				}

				if(!empty($order))
				{
					$this->updateOrderStatus($postData, $order, $setComment);
				}else{
					$this->hgw()->Logging('updateOrderStatus() failed because $order was empty.');
				}
			}
		}catch(Exception $e){
			Shopware()->Plugins()->HeidelGateway()->Logging('notifyAction | '.$e->getMessage());
			return;
		}
	}

	/*
	 * wallet action method
	 * needed for wallet transactions
	 */
	public function walletAction(){
		if(Shopware()->Modules()->Admin()->sCheckUser()){
			// user is logged in
			try{

				$basketId = $this->getBasketId();
				$wallet = strtolower($this->Request()->getParam('wallet'));

				if($basketId['result'] == 'NOK'){
					return $this->forward('fail');
				}else{
					$basketId = $basketId['basketId'];
				}

				if(isset($wallet) && ($wallet == 'masterpass')){
					$pm			= 'mpa';
					$user		= Shopware()->Modules()->Admin()->sGetUserData();
					$basket		= Shopware()->Modules()->Basket()->sGetBasket();
					$amount		= Shopware()->Modules()->Basket()->sGetAmount();
					$amount		= $amount['totalAmount'];

					if(!empty($basket)){
						$tempID = Shopware_Controllers_Frontend_PaymentHgw::createPaymentUniqueId();
					}else{
						$tempID = Shopware()->SessionID();
					}

					$bookingMode = $this->hgw()->Config()->{'HGW_'.strtoupper($pm).'_BOOKING_MODE'};
					$basket['currency']	= Shopware()->Currency()->getShortName();
					$basket['amount']	= $amount;

					$ppd_crit['CRITERION.WALLET'] = '1';
					$ppd_crit['CRITERION.WALLET_PAYNAME'] = 'hgw_mpa';
					$ppd_crit['BASKET.ID'] = $basketId;
					$ppd_crit['PAYMENT.CODE'] = 'WT.IN';


					$getFormUrl = $this->getFormUrl($pm, $bookingMode, $user['additional']['user']['id'], $tempID, NULL, $basket, $ppd_crit, false);
					Shopware()->Session()->HPOrderID = $tempID;

					if((strtoupper($getFormUrl['PROCESSING_RESULT']) == 'ACK') && isset($getFormUrl['FRONTEND_REDIRECT_URL'])){
						//return $this->redirect($getFormUrl['PROCESSING_REDIRECT_URL'], array('code' => '302'));
						return $this->redirect($getFormUrl['FRONTEND_REDIRECT_URL'], array('code' => '302'));
					}else{
						return $this->forward('fail');
					}
					exit;
				}
			}catch(Exception $e){
				$this->hgw()->Logging('walletAction | '.$e->getMessage());
				return;
			}
		} else {
			// user NOT logged in
			return $this->forward('fail');
		}
	}

	/**
	 * Method to update the order status and set comments
	 * @param array $data - push data
	 * @param array $order - order data
	 * @param bool $setComment
	 */
	public function updateOrderStatus($data, $order, $setComment = true){
		try{
			$shortID			= $data['IDENTIFICATION_SHORTID'];
			$transactionID 		= $data['IDENTIFICATION_TRANSACTIONID'];
			$uniqueID			= $data['IDENTIFICATION_UNIQUEID'];
			$transChan			= $data['TRANSACTION_CHANNEL'];
			$accBrand 			= strtolower($data['ACCOUNT_BRAND']);
			$comment 			= "ShortID: ".$shortID;

			if($data['PROCESSING_RESULT'] == 'ACK'){
				$payType	= strtoupper(substr($data['PAYMENT_CODE'], 0, 2));
				$transType	= strtoupper(substr($data['PAYMENT_CODE'], 3, 2));

				if( ($transType == 'DB') || (($transType == 'PA') && ($payType != 'OT')) || ($transType == 'RC') || ($transType == 'CP') || ($accBrand == 'billsafe') ){
					if($order['transactionID'] == ''){
						$paymentStatus = 12 ; // default payment status is 12 - 'Komplett bezahlt'
						if($transType == 'PA'){ $paymentStatus = 18; } // 'Reserviert'
						if($data['PROCESSING_STATUS_CODE'] == "80"){ $paymentStatus = 21; } // 'Überprüfung notwendig'
						try{
                            $return = $this->saveOrder($transactionID, $uniqueID, $paymentStatus);
                        } catch (Exception $e){
                            $this->convertOrder($data);
//                            $this->convertOrder($data['CRITERION_SESS']);
                        }

						if($data['PROCESSING_STATUS_CODE'] != "80"){ $setComment = true; }
					}
				}

				if($setComment){
					$params			= array();
					$amount			= $data['PRESENTATION_AMOUNT'];
					$currency		= $data['PRESENTATION_CURRENCY'];
					$ori_amount	= $this->hgw()->formatNumber($order['invoice_amount']);
					$ori_currency	= $order['currency'];

					switch ($transType) {
						case 'PA':
							// do not change the status if PA Status came after RC
							$cleared = $this->getOrderPaymentStatus($transactionID);
							if($cleared == 0){
								$params['cleared'] = 18; // 'Reserviert'
							}else{
								$params['cleared'] = $cleared;
							}
							$params['internalcomment'] = 'Reservation '.$comment;
							break;

						case 'CP':
							$params['cleared'] = 12; // default payment status is 12 - 'Komplett bezahlt'
							$params['cleareddate'] = date('Y-m-d H:i:s');
							$params['o_attr1'] = $shortID;
							$params['o_attr2'] = $uniqueID;
							$params['internalcomment'] = 'Capture '.$comment;
							break;

						case 'RC':
							$params['cleared'] = 12; // default payment status is 12 - 'Komplett bezahlt'
							$params['cleareddate'] = date('Y-m-d H:i:s');
							$params['o_attr1'] = $shortID;
							$params['o_attr2'] = $uniqueID;
							$params['internalcomment'] = 'Receipt '.$comment;
							break;

						case 'DB':
							$params['cleared'] = 12; // default payment status is 12 - 'Komplett bezahlt'
							$params['cleareddate'] = date('Y-m-d H:i:s');
							$params['o_attr1'] = $shortID;
							$params['o_attr2'] = $uniqueID;
							$params['internalcomment'] = 'Debit '.$comment;
							break;

						case 'FI':
							if($accBrand == 'billsafe'){
								$params['cleared'] = 12; // default payment status is 12 - 'Komplett bezahlt'
							} else {
								$params['cleared'] = 21; // Ueberpruefung nowendig
							}

							$params['cleareddate'] = date('Y-m-d H:i:s');
							$params['o_attr1'] = $shortID;
							$params['o_attr2'] = $uniqueID;
							$params['internalcomment'] = 'Finalize '.$comment;
							break;

						case  'RB':
							$params['internalcomment'] = 'Rebill '.$comment;
							break;

						case 'RF':
							$params['internalcomment'] = 'Refund '.$comment;
							break;

						case 'RV':
							$params['internalcomment'] = 'Reversal '.$comment;
							$params['cleared'] = $this->hgw()->Config()->HGW_CHB_STATUS;
							break;

						case 'CB':
							$params['internalcomment'] = 'Chargeback '.$comment;
							$params['cleared'] = $this->hgw()->Config()->HGW_CHB_STATUS;
							break;
					}
					// add amount to comment
					$params['internalcomment'].= "\n".'Amount: '.$amount.' '.$currency."\n".'Original Amount: '.$ori_amount.' '.$ori_currency;
					// check amount
					if($transType == 'RC' && $amount > 0 && $ori_amount != $amount){
						$params['internalcomment'].= "\n".'!!! Amount mismatch !!!';
						$params['cleared'] = 11; // 'Teilweise bezahlt'
					}
					// check currency
					if(!empty($currency) && $ori_currency != $currency){
						$params['internalcomment'].= "\n".'!!! Currency mismatch !!!';
						$params['cleared'] = 11; // 'Teilweise bezahlt'
					}

					if( (!isset($params['cleared'])) || (is_int(strpos(strtolower($order['internalcomment']),'chargeback'))) ){
						$params['cleared'] = $this->getOrderPaymentStatus($transactionID);
					}

					// set status history
					Shopware()->Modules()->Order()->setPaymentStatus($order['id'], $params['cleared'], false);
					// add infos to order
					$this->addOrderInfos($transactionID, $params, $paymentStatus);
				}
			}
		}catch(Exception $e){
			$this->hgw()->Logging('updateOrderStatus | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method to get BasketId from Basket API
	 * needed for wallet transactions
	 * @return $response
	 */
	public function getBasketId(){
		try{

			#$sw = $this->hgw();
			$sw = self::hgw();

			$ta_mode = $this->hgw()->Config()->HGW_TRANSACTION_MODE;
			$origRequestUrl = $sw::$requestUrl;

			if(is_numeric($ta_mode) && (($ta_mode == 0) || ($ta_mode == 3))){
				$sw::$requestUrl = $sw::$live_url_basket;
			}else{
				$sw::$requestUrl = $sw::$test_url_basket;
			}

			$params['raw'] = $this->hgw()->prepareBasketData($this->getBasket(),$this->getUser());

			$response = $this->hgw()->doRequest($params);
			// switch back to post url, after basket request is sent
			$sw::$requestUrl = $origRequestUrl;
			return $response;

		}catch(Exception $e){
			$this->hgw()->Logging('getBasketId | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to generate BillSafe informations from basket
	 * @return array $params - criterion array for BillSafe
	 */
	public function getBasketDetails(){
		try{
			$user	= $this->getUser();
			$items	= $this->getBasket();
			$params = array();

			if($items){
				$i = 0;
				foreach($items['content'] as $item){
					if(!empty($user['additional']['charge_vat']) && !empty($item['amountWithTax'])){
						$price = round($item['amountWithTax'] / $item['quantity'], 2);
					}else{
						$price = str_replace(',', '.', $item['price']);
					}
					$i++;
					$prefix = 'CRITERION.POS_'.sprintf('%02d', $i);
					$params[$prefix.'.POSITION']			= $i;
					$params[$prefix.'.QUANTITY'] 			= (int)$item['quantity'];
					if(empty($item['packunit'])){ $item['packunit'] = "Stk."; }
					$params[$prefix.'.UNIT'] 				= $item['packunit'];
					$params[$prefix.'.AMOUNT_UNIT_GROSS']	= round($price * 100);
					$params[$prefix.'.AMOUNT_GROSS'] 		= round(($price * $item['quantity']) *100);
					$item['articlename'] = preg_replace('/%/','Proz.', $item['articlename']);
					$item['articlename'] = preg_replace('/("|\'|!|$|=)/',' ', $item['articlename']);
					$params[$prefix.'.TEXT']				= strlen($item['articlename']) > 100 ? substr($item['articlename'], 0, 90) . '...' : $item['articlename'];

					$params[$prefix.'.ARTICLE_NUMBER']		= $item['ordernumber'];
					$params[$prefix.'.PERCENT_VAT']			= $this->hgw()->formatNumber($item['tax_rate']);
					if($item['modus'] == 4){
						$article['type'] = 'goods';
					}else{
						$article['type'] = $price >= 0 ? 'goods' : 'voucher';
					}
					$params[$prefix.'.ARTICLE_TYPE'] 		= $article['type'];
				}
			}

			/*
			 * Shipping cost
			 */
			$shippingCost = round($this->getBillSafeShipment() * 100);
			if($shippingCost > 0){
				$i++;
				$prefix 							= 'CRITERION.POS_'.sprintf('%02d', $i);
				$params[$prefix.'.POSITION'] 		= $i;
				$params[$prefix.'.QUANTITY'] 		= '1';
				$params[$prefix.'.UNIT'] 			= 'Stk.';
				$params[$prefix.'.AMOUNT_UNIT_GROSS'] = $shippingCost;
				$params[$prefix.'.AMOUNT_GROSS']	= $shippingCost;
				$params[$prefix.'.TEXT'] 			= 'Shipping';
				$params[$prefix.'.ARTICLE_NUMBER']	= '0';
				$params[$prefix.'.PERCENT_VAT']		= $this->hgw()->formatNumber($this->getBillSafeTaxShipment());
				$params[$prefix.'.ARTICLE_TYPE']	= 'shipment'; // "goods" (Versandartikel), "shipment" (Versandkosten) oder "voucher" (Gutschein/Rabatt)
			}
			return $params;
		}catch(Exception $e){
			$this->hgw()->Logging('getBasketDetails | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get information for invoice data
	 * @params array $ppd_crit- criterion array
	 * @return array $params - criterion array
	 */
	public function getInvoiceDetails($ppd_crit){
		try{
			$user = $this->getUser();
			$gBasket = $this->getBasket();
			$params = $ppd_crit;
			$params['CRITERION.LANGUAGE'] 			= Shopware()->Locale()->getLanguage();
			$params['CRITERION.AMOUNT_NET']			= round($gBasket['AmountNetNumeric']*100);
			$params['CRITERION.PERCENT_VAT']		= $ppd_crit['CRITERION.POS_01.PERCENT_VAT'];
			$params['CRITERION.AMOUNT_VAT']			= round($gBasket['sAmountTax']*100);
			$params['CRITERION.AMOUNT_TOTAL']		= round($gBasket['AmountNumeric']*100);
			$params['CRITERION.CURRENCY'] 			= Shopware()->Currency()->getShortName();
			$params['CRITERION.CUSTOMER_ID']		= $user['billingaddress']['customernumber'];

			return $params;
		}catch(Exception $e){
			$this->hgw()->Logging('getInvoiceDetails | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get all country information by Iso Code
	 * @param string $isoCode - iso code (strtoupper)
	 * @return string country name
	 */
	public function getCountryInfoByIso($isoCode){
		try{
			$sql = 'SELECT `id` FROM `s_core_countries` WHERE countryiso = ?';
			$countryId = Shopware()->Db()->fetchOne($sql, array($isoCode));

			return Shopware()->Modules()->Admin()->sGetCountry($countryId);
		}catch(Exception $e){
			$this->hgw()->Logging('getCountryInfoByIso | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get Brandname by brand
	 * @param string $brand
	 * @return string $brandName - brand name
	 */
	public function getBrandName($brand){
		try{
			switch(strtolower($brand)){
				case 'master':
					$brandName = 'MasterCard'; break;
				case 'visa':
					$brandName = 'Visa'; break;
				case 'maestro':
					$brandName = 'Maestro'; break;
				case 'amax':
					$brandName = 'American Express '; break;
				case 'discover':
					$brandName = 'Discover'; break;
				case 'diners':
					$brandName = 'Diners Club'; break;
			}
			return $brandName;
		}catch(Exception $e){
			$this->hgw()->Logging('getBrandName | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get shipping data for BillSafe
	 * @return string shipping cost
	 */
	private function getBillSafeShipment(){
		try{
			$user = $this->getUser();
			$basket = $this->getBasket();
			if(!empty($user['additional']['charge_vat'])){
				return $basket['sShippingcostsWithTax'];
			}else{
				return str_replace(',', '.', $basket['sShippingcosts']);
			}
		}catch(Exception $e){
			$this->hgw()->Logging('getBillSafeShipment | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get shipping tax for BillSafe
	 * @return float shipping tax
	 */
	private function getBillSafeTaxShipment(){
		try{
			$user = $this->getUser();
			$basket = $this->getBasket();
			if(!empty($user['additional']['charge_vat'])){
				return round($basket['sShippingcostsWithTax'] / $basket['sShippingcostsNet'], 2) * 100 - 100;
			}else{
				return 0;
			}
		}catch(Exception $e){
			$this->hgw()->Logging('getBillSafeTaxShipment | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method for address validation for BillSafe.
	 * Billing- and shipping address must be the same
	 * @return boolean
	 */
	private function mergeAddress(){
		try{
			$user = $this->getUser();
			$mergeList = array(
					'firstname',
					'lastname',
					'street',
					'streetnumber',
					'zipcode',
					'city',
					'countryID',
			);
			foreach ($mergeList AS $Item){
				if($user['billingaddress'][$Item] !== $user['shippingaddress'][$Item]){
					return false;
				}
			}
			return true;
		}catch(Exception $e){
			$this->hgw()->Logging('mergeAddress | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to save BillSafe request data
	 * @param string $tempID - temporary order id
	 * @param array $params - post data from post api
	 */
	private function saveBillSafeRequest2DB($tempID, $params){
		try{
			$sql = 'INSERT INTO `s_plugin_hgw_billsafe`
			SET `temporaryID` = ?, `Request` = ?';
			Shopware()->Db()->query($sql, array($tempID, serialize($params)));
		}catch(Exception $e){
			$this->hgw()->Logging('saveBillSafeRequest2DB | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to send the bank informations to the customer
	 * @param string $order - order number
	 * @param string $customer - customre email address
	 * @param array $prepaymentData - data to add to email
	 * @param string $template - email template
	 */
	public function prepaymentMail($order, $customer, $prepaymentData, $template = 'prepaymentHeidelpay'){
		try{

            // HACK to set PAN for Santander istead of Short-Id for Santander payment
            // Shopware doesn#t pull right mail-template for santander
            if(array_key_exists("CONNECTOR_ACCOUNT_USAGE",$prepaymentData))
            {
                $prepaymentData["IDENTIFICATION_SHORTID"] = $prepaymentData["CONNECTOR_ACCOUNT_USAGE"];
            }

			$prepaymentData['ordernumber'] = $order;
			$mail = Shopware()->TemplateMail()->createMail($template, $prepaymentData);
			$mail->addTo($customer);
			$mail->send();

		}catch(Exception $e){
			$this->hgw()->Logging('prepaymentMail | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get form-URL for Heidelpay whitelabel solution
	 * @param string $pm - payment code
	 * @param string $bookingMode - booking mode
	 * @param string $userId - user id
	 * @param string $tempID - temp id
	 * @param string $uid - unique id
	 * @param array $basket - basket information
	 * @param array $ppd_crit - criterions
	 * @return array $response
	 */
	public function getFormUrl($pm, $bookingMode, $userId, $tempID, $uid=NULL, $basket=NULL, $ppd_crit=NULL, $fromBootstrap=false){
		try{

			$ppd_config = Shopware()->Plugins()->Frontend()->HeidelGateway()->ppd_config($bookingMode, $pm, $uid);
			$ppd_user = Shopware()->Plugins()->Frontend()->HeidelGateway()->ppd_user(NULL, $pm);
			$ppd_bskt['PRESENTATION.AMOUNT'] 	= Shopware()->Plugins()->Frontend()->HeidelGateway()->formatNumber($basket['amount']);
			$ppd_bskt['PRESENTATION.CURRENCY']	= $basket['currency'];
			$ppd_crit['CRITERION.USER_ID']		= $userId;
			$ppd_crit['IDENTIFICATION.TRANSACTIONID'] = $tempID;

			if((strtoupper($ppd_config['PAYMENT.TYPE']) == 'RR') && (!Shopware()->Session()->HPGateway)){
				$ppd_crit['CRITERION.DBONRG'] = "false";
				$ppd_crit['CRITERION.GATEWAY'] = "0";
			}

			if($fromBootstrap){
				$ppd_crit['CRITERION.SECRET'] = self::createSecretHash($tempID);

				$response = Shopware()->Plugins()->Frontend()->HeidelGateway()->doRequest(self::preparePostData($ppd_config, array(), $ppd_user, $ppd_bskt, $ppd_crit));
				$errorMsg = self::getHPErrorMsg($response['PROCESSING_RETURN_CODE'], $fromBootstrap);
			}else{
				$ppd_crit['CRITERION.SECRET'] = $this->createSecretHash($tempID);
				$response = Shopware()->Plugins()->Frontend()->HeidelGateway()->doRequest($this->preparePostData($ppd_config, array(), $ppd_user, $ppd_bskt, $ppd_crit));
				$errorMsg = $this->getHPErrorMsg($response['PROCESSING_RETURN_CODE'], $fromBootstrap);
			}

			if($response['PROCESSING_RESULT'] == 'ACK'){
				return $response;
			}else{
				if($uid == NULL){
					return $response;
				}else{
					$_SESSION['Shopware']['HPError'] = '<li>'.$errorMsg.'</li>';
					if($fromBootstrap){
						self::hgw()->Logging($pm.' | '.$response['PROCESSING_RETURN_CODE'].' | '.$response['PROCESSING_RETURN']);
					}else{
						$this->hgw()->Logging($pm.' | '.$response['PROCESSING_RETURN_CODE'].' | '.$response['PROCESSING_RETURN']);
					}
				}
			}

		}catch(Exception $e){
			if($fromBootstrap){
				self::hgw()->Logging('getFormUrl | '.$e->getMessage());
			}else{
				$this->hgw()->Logging('getFormUrl | '.$e->getMessage());
			}
			return;
		}

	}

	/**
	 * Method to prepare post data
	 * @param array $config - config params
	 * @param array $frontend - frontend params
	 * @param array $userData - userData params
	 * @param array $basketData - basket params
	 * @param array $criterion - criterions
	 * @return array $params
	 */
	public function preparePostData($config = array(), $frontend = array(), $userData = array(), $basketData = array(), $criterion = array(),$isRecurring = false){
		try{
			$params = array();
			// configurtation part of this function
			$params['SECURITY.SENDER']		= $config['SECURITY.SENDER'];
			$params['USER.LOGIN'] 			= $config['USER.LOGIN'];
			$params['USER.PWD'] 			= $config['USER.PWD'];
			$params['TRANSACTION.MODE']		= $config['TRANSACTION.MODE'];
			$params['TRANSACTION.CHANNEL']	= $config['TRANSACTION.CHANNEL'];
			$clientIP = explode(',', Shopware()->Front()->Request()->getclientIP(true));
			if(!filter_var($clientIP[0], FILTER_VALIDATE_IP)){ $clientIP[0] = '127.0.0.1'; }
			$params['CONTACT.IP'] 			= $clientIP[0];
			$params['FRONTEND.LANGUAGE'] 	= strtoupper(Shopware()->Locale()->getLanguage());
			$params['FRONTEND.MODE'] 		= "WHITELABEL";

			// set payment method
			switch($config['PAYMENT.METHOD']){
				/* prezlewy24 */
				case 'p24':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "OT.".$type;
					$params['ACCOUNT.BRAND'] 		= "PRZELEWY24";
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* sofort banking */
				case 'sue':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "OT.".$type;
					$params['ACCOUNT.BRAND'] 		= "SOFORT";
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* griopay */
				case 'gir':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "OT.".$type;
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* ideal */
				case 'ide':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "OT.".$type;
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* eps */
				case 'eps':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "OT.".$type;
					$params['FRONTEND.ENABLED'] 	= "true";
					// 					$params['ACCOUNT.BRAND'] 		= "EPS";
					break;
					/* postfinance */
				case 'pf':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "OT.".$type;
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* paypal */
				case 'va':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'DB' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "VA.".$type;
					$params['ACCOUNT.BRAND'] 		= "PAYPAL";
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* prepayment */
				case 'pp':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "PP.".$type;
					$params['FRONTEND.ENABLED'] 	= "false";
					break;
					/* invoce */
				case 'iv':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "IV.".$type;
					$params['FRONTEND.ENABLED'] 	= "false";
					break;
					/* cms / universum / invoice with insurance */
				case 'papg':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "IV.".$type;
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
					/* santander */
				case 'san':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "IV.".$type;
					$params['ACCOUNT.BRAND'] 		= "SANTANDER";
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
                /* payolution direct */
                case 'ivpd':
                    $type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
                    $params['PAYMENT.CODE'] 		= "IV.".$type;
                    $params['ACCOUNT.BRAND'] 		= "PAYOLUTION_DIRECT";
                    $params['FRONTEND.ENABLED'] 	= "true";
                    break;
					/* billsafe */
				case 'bs':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "IV.".$type;
					$params['ACCOUNT.BRAND']		= "BILLSAFE";
					$params['FRONTEND.ENABLED']		= "false";
					break;
					/* mangirkart */
				case 'mk':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "PC.".$type;
					$params['ACCOUNT.BRAND'] 		= "MANGIRKART";
					$params['FRONTEND.ENABLED']		= "false";
					break;
					/* masterpass */
				case 'mpa':
					$type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
					$params['PAYMENT.CODE'] 		= "WT.".$type;
					$params['ACCOUNT.BRAND'] 		= "MASTERPASS";
					$params['FRONTEND.ENABLED']		= "true";

					break;
                    /* EasyCredit */
                case 'hpr':
                    $type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
                    $params['PAYMENT.CODE'] 		= "HP.".$type;
                    $params['TRANSACTION.RESPONSE']	= "SYNC";
                    break;
					/* credit- & debitcard */
				case 'cc':
				case 'dc':
					$params['PAYMENT.CODE'] = strtoupper($config['PAYMENT.METHOD']).'.'.$config['PAYMENT.TYPE'];
					$params['FRONTEND.ENABLED'] = "true";

					$url = parse_url(Shopware()->Front()->Router()->assemble(array('forceSecure' => 1)));
					$params['FRONTEND.PAYMENT_FRAME_ORIGIN']	= $url['scheme'] .'://'. $url['host'];
					$params['FRONTEND.PREVENT_ASYNC_REDIRECT'] = 'TRUE';
					// path to CSS
					$cssVar = 'HGW_HPF_'.strtoupper($config['PAYMENT.METHOD']).'_CSS';
					$konfiguration = self::Config();
					if(empty($konfiguration->$cssVar)){
                        $konfiguration->$cssVar = $params['FRONTEND.PAYMENT_FRAME_ORIGIN'].Shopware()->Shop()->getBaseUrl()."/engine/Shopware/Plugins/Community/Frontend/HeidelGateway/Views/hpf_cc.css";
                    }
					$params['FRONTEND.CSS_PATH']	=	$konfiguration->$cssVar;
					break;
					/* default */
				default:
					$params['PAYMENT.CODE'] = strtoupper($config['PAYMENT.METHOD']).'.'.$config['PAYMENT.TYPE'];
					$params['FRONTEND.RETURN_ACCOUNT'] = "true";
					$params['FRONTEND.ENABLED'] 	= "true";
					break;
			}
//			$params['CRITERION.TEMPORDER'] = $this->getSession()->offsetGet('sessionId');
			$params['CRITERION.TEMPORDER'] = Shopware()->Session()->offsetGet('sessionId');

			// debit on registration
			if(array_key_exists('ACCOUNT.REGISTRATION',$config)){
				$params['ACCOUNT.REGISTRATION']	= $config['ACCOUNT.REGISTRATION'];
				$params['FRONTEND.ENABLED']		= "false";
			}

			if ($isRecurring == false) {
				// prepare User array to create shippingHash
				$userForShippingHash = Shopware()->Modules()->Admin()->sGetUserData();

				if (array_key_exists('CRITERION.SHIPPINGHASH', $params)){
					$params['CRITERION.SHIPPINGHASH'] = $params['CRITERION.SHIPPINGHASH'];
				} else {
					$params['CRITERION.SHIPPINGHASH'] = self::hgw()->createShippingHash($userForShippingHash, substr($params['PAYMENT.CODE'], 0,2));
				}
			}
			if(array_key_exists('SHOP.TYPE',$config)) $params['SHOP.TYPE'] = $config['SHOP.TYPE'];
			if(array_key_exists('SHOPMODULE.VERSION',$config)) $params['SHOPMODULE.VERSION'] = $config['SHOPMODULE.VERSION'];

			// frontend configuration  |  override FRONTEND.ENABLED if nessessary
			if(array_key_exists('FRONTEND.ENABLED',$frontend)){
				$params['FRONTEND.ENABLED'] = $frontend['FRONTEND.ENABLED'];
				unset($frontend['FRONTEND.ENABLED']);
			}
			$params = array_merge($params, $frontend);

			// costumer data configuration
			$params = array_merge($params, $userData);

			// basket data configuration
			$params = array_merge($params, $basketData);

			// criterion data configuration
			$params = array_merge($params, $criterion);
			$params['CRITERION.SHOP_ID']	= Shopware()->Shop()->getId();
			$params['CRITERION.PUSH_URL'] 	= Shopware()->Front()->Router()->assemble(array('forceSecure' => 1,'controller' => 'PaymentHgw','action' => 'rawnotify'));
			$params['REQUEST.VERSION'] 		= "1.0";

			if(
                $params['PAYMENT.CODE'] == "CC.DB" ||
                $params['PAYMENT.CODE'] == "CC.PA" ||
                $params['PAYMENT.CODE'] == "DC.DB" ||
                $params['PAYMENT.CODE'] == "DC.PA"
            )
			{
			    $params['FRONTEND.PREVENT_ASYNC_REDIRECT'] = "FALSE";
            }

			$payMethode = substr($params['PAYMENT.CODE'], 3);
			switch ($payMethode) {
				case 'RR':
				case 'RG':
					$params['CRITERION.DBONRG'] = true;
					$params['FRONTEND.RESPONSE_URL'] = Shopware()->Front()->Router()->assemble(array(
							'forceSecure'	=> 1,
							'controller' 	=> 'PaymentHgw',
							'action' 		=> 'responseReg'
					));
					break;
                case 'PA':
                    if ($params['PAYMENT.CODE'] == 'HP.PA') {
                        // if PA is for hire purchace take responseHpr-url
                        $params['FRONTEND.RESPONSE_URL'] = Shopware()->Front()->Router()->assemble(array(
                            'forceSecure'	=> 1,
                            'controller' 	=> 'PaymentHgw',
                            'action' 		=> 'responseHpr'
                        ));
                    } else {
                        // if PA is not from hire purchace take normal response-url
                        $params['FRONTEND.RESPONSE_URL'] = Shopware()->Front()->Router()->assemble(array(
                            'forceSecure'	=> 1,
                            'controller' 	=> 'PaymentHgw',
                            'action' 		=> 'response',
                        ));
                    }
                    break;

				default:
					$params['FRONTEND.RESPONSE_URL'] = Shopware()->Front()->Router()->assemble(array(
					'forceSecure'	=> 1,
					'controller' 	=> 'PaymentHgw',
					'action' 		=> 'response',
					));
					break;
			}

			if(!empty($config['IDENTIFICATION.REFERENCEID'])){
				$params['IDENTIFICATION.REFERENCEID'] = $config['IDENTIFICATION.REFERENCEID'];
			}

			return $params;

		}catch(Exception $e){
			self::hgw()->Logging('preparePostData() | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get error snippets if set
	 * @param string $prc - PROCESSING_RETURN_CODE
	 * @param bool $bs - methed called from bootstrap
	 * @return string $error
	 */
	public function getHPErrorMsg($prc = NULL, $bs = NULL){
		try{
			$locId = Shopware()->Shop()->getLocale()->getId();

			if($bs){
				$error = self::getSnippet('HPError-default', $locId, 'frontend/payment_heidelpay/error');
			}else{
				$error = $this->getSnippet('HPError-default', $locId, 'frontend/payment_heidelpay/error');
			}

			if($prc != NULL){
				if($bs){
					$error = self::getSnippet('HPError-'.$prc, $locId, 'frontend/payment_heidelpay/error') != '' ? self::getSnippet('HPError-'.$prc, $locId, 'frontend/payment_heidelpay/error') : $error;
				}else{
					$error = $this->getSnippet('HPError-'.$prc, $locId, 'frontend/payment_heidelpay/error') != '' ? $this->getSnippet('HPError-'.$prc, $locId, 'frontend/payment_heidelpay/error') : $error;
				}
			}
			/* Funktionalität verursacht unter PHP 7 noch Fehler */
			/*
			 $actionAndBrand = $this->Request()->getParams();
			 if ($actionAndBrand['wallet'] == 'masterpass' && $actionAndBrand['action'] == 'wallet') {
				$error = $this->getSnippet('HPError-login', $locId, 'frontend/payment_heidelpay/error');
				}
				*/
			return $error;
		}catch(Exception $e){
			$this->hgw()->Logging('getHPErrorMsg | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Create account action method.
	 * Method that prepares data for createAccount()
	 */
	public function createAccAction(){
		try{

			$transaction = $this->getHgwTransactions(Shopware()->Session()->HPOrderID);
			$parameters = json_decode($transaction['jsonresponse'],1);

			if((strtolower($parameters['NAME_SALUTATION']) == 'herr') || (strtolower($parameters['NAME_SALUTATION']) == 'mr') || ($parameters['NAME_SALUTATION'] == '')){
				$parameters['NAME_SALUTATION'] = 'mr';
			}elseif((strtolower($parameters['NAME_SALUTATION']) == 'frau') || (strtolower($parameters['NAME_SALUTATION']) == 'mrs') || (strtolower($parameters['NAME_SALUTATION']) == 'ms')){
				$parameters['NAME_SALUTATION'] = 'ms';
			}
			$country = $this->getCountryInfoByIso(strtoupper($parameters['ADDRESS_COUNTRY']));

			$address['shipping']['NAME_COMPANY']	= $parameters['NAME_COMPANY'];
			$address['shipping']['NAME_SALUTATION'] = $parameters['NAME_SALUTATION'];
			$address['shipping']['NAME_FAMILY'] 	= $parameters['NAME_FAMILY'];
			$address['shipping']['NAME_GIVEN'] 		= $parameters['NAME_GIVEN'];
			$address['shipping']['ADDRESS_STREET'] 	= $parameters['ADDRESS_STREET'];
			$address['shipping']['ADDRESS_CITY']	= $parameters['ADDRESS_CITY'];
			$address['shipping']['ADDRESS_ZIP'] 	= $parameters['ADDRESS_ZIP'];
			$address['shipping']['ADDRESS_COUNTRY'] = $country['countryname'];

			$address['billing'] = $address['shipping'];

			if($parameters['PROCESSING_RESULT'] == 'ACK'){
				Shopware()->Session()->HPWallet = true;
				if(Shopware()->Modules()->Admin()->sCheckUser()){
					// save Payment ID
					Shopware()->Modules()->Admin()->sSYSTEM->_POST['sPayment'] = $this->hgw()->getPaymentIdByName($parameters['CRITERION_WALLET_PAYNAME']);
					Shopware()->Modules()->Admin()->sUpdatePayment();
				}else{
					// buy as guest
					$data['auth']['email'] 			= $parameters['CONTACT_EMAIL'];
					$data['auth']['encoderName']	= 'md5';
					$data['auth']['password'] 		= md5($parameters['IDENTIFICATION_SHORTID'].$parameters['CRITERION_SECRET']);
					$data['auth']['accountmode']	= '1'; // set for guest account
					if(isset($parameters['NAME_COMPANY']) && $parameters['NAME_COMPANY'] != ''){
						$data['billing']['company']	= $parameters['NAME_COMPANY'];
					}
					$data['billing']['salutation'] 		= strtoupper($parameters['NAME_SALUTATION']);
					$data['billing']['firstname'] 		= $parameters['NAME_GIVEN'];
					$data['billing']['lastname'] 		= $parameters['NAME_FAMILY'];
					$data['billing']['street'] 			= $parameters['ADDRESS_STREET'];
					$data['billing']['streetnumber']	= ' ';
					$data['billing']['zipcode'] 		= $parameters['ADDRESS_ZIP'];
					$data['billing']['city']			= $parameters['ADDRESS_CITY'];
					$data['billing']['country']			= $country['id'];
					$data['billing']['phone']			= $parameters['CONTACT_PHONE'];

					$data['payment']['object'] = Shopware()->Modules()->Admin()->sGetPaymentMeanById($this->hgw()->getPaymentIdByName($parameters['CRITERION_WALLET_PAYNAME']));

					$this->createAccount($data);
					$user = Shopware()->Modules()->Admin()->sGetUserData();
					$parameters['CRITERION_USER_ID'] = $user['additional']['user']['id'];
				}

				// save payment data and save that id into session
				Shopware()->Session()->HPRegId = $this->saveRegData($parameters, '', '', $address, true);
				unset(Shopware()->Session()->sRegisterFinished);

				// redirect
				$this->redirect(array(
						'forceSecure' => 1,
						'controller' => 'checkout',
						'action' => 'confirm',
						'appendSession' => 'SESSION_ID'
				));
			}else{
				Shopware()->Session()->HPError = $parameters['PROCESSING_RETURN_CODE'];

				$this->redirect(array(
						'forceSecure' => 1,
						'controller' => 'PaymentHgw',
						'action' => 'fail',
						'appendSession' => 'SESSION_ID'
				));
			}

		}catch(Exception $e){
			$this->hgw()->Logging('createAccAction | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method that creates a (guest) account
	 * needed for express checkout (e.g. MasterPass)
	 */
	public function createAccount($data){
		try{
			/* Try to fix problem with masterpass-quick-checkout */

			Shopware()->Session()->sRegisterFinished = false;
			if(version_compare(Shopware::VERSION, '4.3.0', '>=') || Shopware::VERSION == '___VERSION___'){
				Shopware()->Session()->sRegister = $data;
			}else{
				Shopware()->Session()->sRegister = new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
			}

			Shopware()->Modules()->sAdmin()->sSaveRegister();

		}catch(Exception $e){

			$this->hgw()->Logging('createAccount | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to save the pay data of a registration transaction to the db
	 * @param array $resp - response
	 * @param string $ktn - account number
	 * @param string $blz - bank identification
	 * @param array $address - address / birthdate data
	 * @param bool $return
	 * @return last inserted id
	 */
	public static function saveRegData($resp, $kto, $blz, $address = NULL, $return = false){
		try{
			$payType = strtolower(substr($resp['PAYMENT_CODE'], 0,2));
			$transType = substr($resp['PAYMENT_CODE'], 3, 2);

			if(strtolower($payType) == 'wt' && is_int(strpos($resp['CRITERION_WALLET_PAYNAME'], 'mpa'))){ $payType = 'mpa'; }

			switch ($payType) {

				case 'iv':
					if ($resp['ACCOUNT_BRAND'] == 'SANTANDER') {
						//Santander
						$payType = 'san';
					} elseif ($resp['ACCOUNT_BRAND'] == 'PAYOLUTION_DIRECT'){
                        //Payolution
                        $payType = 'ivpd';
                        $resp['ACCOUNT_BRAND'] = 'PAYOLUTION_DIRECT';
                    } else {
						// case CMS / Universum
						$payType = 'papg';
						$resp['ACCOUNT_BRAND'] = 'CMS/PNO/UNIV';
					}
					break;
			}

			// save registration in db
			$sql = '
			INSERT INTO `s_plugin_hgw_regdata`(`userID`, `payType`, `uid`, `cardnr`, `expMonth`, `expYear`, `brand`, `owner`,
					`kto`, `blz`, `chan`, `shippingHash`, `email`, `payment_data`)
				VALUES (:userID, :payType , :uid, :cardnr, :expMonth, :expYear, :brand, :owner,
					:kto, :blz, :chan, :shippingHash, :email, :payment_data)
			ON DUPLICATE KEY UPDATE
					uid = :uidNew, cardnr = :cardnrNew, expMonth = :expMonthNew, expYear = :expYearNew, brand = :brandNew, owner = :ownerNew,
					kto = :ktoNew, blz = :blzNew, chan = :chanNew, shippingHash = :shippingHashNew, email = :emailNew, payment_data = :payment_dataNew';

			$params = array(
					'userID' 	=> $resp['CRITERION_USER_ID'],
					'payType' 	=> $payType,
					'uid' 		=> $resp['IDENTIFICATION_UNIQUEID'],
					'cardnr' 	=> isset($resp['ACCOUNT_NUMBER']) 			? $resp['ACCOUNT_NUMBER'] 		: ' ',
					'expMonth' 	=> isset($resp['ACCOUNT_EXPIRY_MONTH']) 	? $resp['ACCOUNT_EXPIRY_MONTH'] : ' ',
					'expYear' 	=> isset($resp['ACCOUNT_EXPIRY_YEAR'])		? $resp['ACCOUNT_EXPIRY_YEAR'] 	: ' ',
					'brand' 	=> isset($resp['ACCOUNT_BRAND'])			? $resp['ACCOUNT_BRAND']		: ' ',
					'owner' 	=> isset($resp['ACCOUNT_HOLDER'])			? $resp['ACCOUNT_HOLDER']		: ' ',
					'kto' 		=> $kto,
					'blz' 		=> $blz,
					'chan' 		=> isset($resp['TRANSACTION_CHANNEL'])		? $resp['TRANSACTION_CHANNEL'] 	: ' ',
					'shippingHash' => isset($resp['CRITERION_SHIPPINGHASH'])? $resp['CRITERION_SHIPPINGHASH']	: ' ',
					'email' 	=> isset($resp['CONTACT_EMAIL']) 			? $resp['CONTACT_EMAIL']			: ' ',
					'payment_data' => json_encode($address),

					'uidNew' 		=> isset($resp['IDENTIFICATION_UNIQUEID'])? $resp['IDENTIFICATION_UNIQUEID']: ' ',
					'cardnrNew' 	=> isset($resp['ACCOUNT_NUMBER']) 		? $resp['ACCOUNT_NUMBER'] 		: ' ',
					'expMonthNew' 	=> isset($resp['ACCOUNT_EXPIRY_MONTH']) ? $resp['ACCOUNT_EXPIRY_MONTH'] : ' ',
					'expYearNew' 	=> isset($resp['ACCOUNT_EXPIRY_YEAR'])	? $resp['ACCOUNT_EXPIRY_YEAR'] 	: ' ',
					'brandNew'		=> isset($resp['ACCOUNT_BRAND'])		? $resp['ACCOUNT_BRAND']		: ' ',
					'ownerNew'		=> isset($resp['ACCOUNT_HOLDER'])		? $resp['ACCOUNT_HOLDER']		: ' ',
					'ktoNew' 		=> $kto,
					'blzNew' 		=> $blz,
					'chanNew' 		=> isset($resp['TRANSACTION_CHANNEL'])	?$resp['TRANSACTION_CHANNEL'] 	: ' ',
					'shippingHashNew'=> isset($resp['CRITERION_SHIPPINGHASH'])? $resp['CRITERION_SHIPPINGHASH']	: ' ',
					'emailNew' 		=> isset($resp['CONTACT_EMAIL']) 		?$resp['CONTACT_EMAIL']			: ' ',
					'payment_dataNew' => json_encode($address)
			);

			try {
				Shopware()->Db()->query($sql, $params);
				$return = true;
			} catch (Exception $e) {
				self::hgw()->Logging('saveRegData DB-Query | '.$e->getMessage());
				return false;
			}


			if($return){
				return Shopware()->Db()->lastInsertId();
			}
		}catch(Exception $e){
			self::hgw()->Logging('saveRegData Function | '.$e->getMessage());
			return false;
		}
	}
	/** function to create a shipping hash with some User-Data
	 * @param array $user
	 * @param string $pm
	 */
	public function createShippingHash($userGiven = null, $pm) {
		if (empty($userGiven) ) {
			try {
				$user = Shopware()->Modules()->Admin()->sGetUserData();
			}
			catch (Exception $e) {
				self::hgw()->Logging('createShippingHash PaymentHgw  | bei Payment: '.$pm.' | '.$e->getMessage().' no user found');
			}
		} else {
			$user = $userGiven;
		}

		if (
		    empty($user['shippingaddress']['firstname']) ||
			empty($user['shippingaddress']['lastname']) ||
			empty($user['shippingaddress']['street']) ||
			empty($user['shippingaddress']['zipcode']) ||
			empty($user['shippingaddress']['countryID'])
			) {
			    self::hgw()->Logging('createShippingHash PaymentHgw Checkfunction  | bei Payment: '.$pm.' leeres UserArray');
				return false;
            }

            return 	hash('sha512',
			$user['shippingaddress']['firstname'].
			$user['shippingaddress']['lastname'].
			$user['shippingaddress']['street'].
			$user['shippingaddress']['zipcode'].
			$user['shippingaddress']['city'].
			$user['shippingaddress']['countryID']
			);
	}

	/** fetches a single transaction from hgw_transactions
	 *
	 * @param string $transactionId
	 * @return array
	 */
	public function getHgwTransactions($transactionId) {
		$sql= "SELECT * FROM `s_plugin_hgw_transactions` WHERE `transactionid` = ? ORDER BY `id` DESC LIMIT 1 ;";
		$params = array($transactionId);
		try {
			$transactionResult = Shopware()->Db()->fetchRow($sql, $params);
			if (empty($transactionResult) || $transactionResult == '') {
				self::hgw()->Logging('getHgwTransactions  | No Transaction found for '.$transactionId);
			}

		} catch (Exception $e){
			self::hgw()->Logging('getHgwTransactions failed | Message: '. $e->getMessage().' in file: '.$e->getFile());
		}
		return $transactionResult;
	}

	/** converts two given strings in a well formatted (YYYY-MM-DD) array
	 *
	 * @param string $birthdate
	 * @param string $salutation
	 *
	 * @return array $dataToSave['salut']
	 * @return array $dataToSave['day']
	 * @return array $dataToSave['month']
	 * @return array $dataToSave['year']
	 * @return array $dataToSave['formatted']
	 */
	public static function prepareBirthdate($birthdate,$salutation){
		$dataToSave = array();
		if (self::Config()->HGW_DD_GUARANTEE_MODE == 1){
			$dateOfBirth 			= explode('-',$birthdate);
			$dataToSave['NAME_SALUTATION'] 	= $salutation;
			$dataToSave['day']		        = $dateOfBirth[2];
			$dataToSave['month']	        = $dateOfBirth[1];
			$dataToSave['year']		        = $dateOfBirth[0];
			$dataToSave['NAME_BIRTHDATE']   = $birthdate;
		} else {
			$dataToSave = NULL;
		}

		return $dataToSave;
	}

	/**
	 * helper method
	 * returns the call to heidelGateway from bootstrap
	 *
	 * it's for short writing, so you can write:
	 * $this->hgw()->methodNAME(); insted of
	 * Shopware()->Plugins()->Frontend()->HeidelGateway()->methodNAME()
	 */
	public static function hgw(){
		return Shopware()->Plugins()->Frontend()->HeidelGateway();
	}

    /**
     * Function to cheat Shopware Session and redirect to Checkout-Confirm
     */
    public function afterEasyAction()
    {
        Shopware()->Session()->HPdidRequest = 'TRUE';
        $this->redirect(
            array(
                'controller' => 'checkout',
                'action' => 'confirm',
            )
        );
    }

    public function convertOrder($transactionData)
    {
        try {
            if (empty($transactionData)) {
                self::hgw()->Logging('convertOrder failed');
            } else {
                // Get user, shipping and billing
                $builder = Shopware()->Models()->createQueryBuilder();
                $builder->select(['orders', 'customer', 'billing', 'payment', 'shipping'])
                    ->from(\Shopware\Models\Order\Order::class, 'orders')
                    ->leftJoin('orders.customer', 'customer')
                    ->leftJoin('orders.payment', 'payment')
                    ->leftJoin('customer.defaultBillingAddress', 'billing')
                    ->leftJoin('customer.defaultShippingAddress', 'shipping')
                    ->where('orders.temporaryId = ?1')
                    ->setParameter(1, $transactionData['CRITERION_TEMPORDER']);
                $result = $builder->getQuery()->getArrayResult();
                $customerDbResult = $result;

                // Check required fields
                if (empty($customerDbResult) || $customerDbResult[0]['customer'] === null || $customerDbResult[0]['customer']['defaultBillingAddress'] === null) {
                    self::hgw()->Logging('convertOrder failed | no customer / order data found');
                    return;
                }

                // create Order-Object from abborded order in db
                $builder = Shopware()->Models()->createQueryBuilder();
                $builder->select('orders.id')
                    ->from(\Shopware\Models\Order\Order::class, 'orders')
                    ->where('orders.temporaryId = ?1')
                    ->setParameter(1, $transactionData['CRITERION_TEMPORDER']);
                $result = $builder->getQuery()->getArrayResult();
                $orderObject = Shopware()->Models()->find(\Shopware\Models\Order\Order::class,['id' => $result[0]['id']]);

                // create instance of ordernumber-model to create new ordernumber for order
                $numberRepository = Shopware()->Models()->getRepository(\Shopware\Models\Order\Number::class);
                $numberModel = $numberRepository->findOneBy(['name' => 'invoice']);
                if ($numberModel === null) {
                    self::hgw()->Logging('convertOrder failed | no ordernumber could be created');
                    return;
                }
                // fetch last ordernumber and add 1
                $newOrderNumber = $numberModel->getNumber() + 1;
                // Set new ordernumber
                $numberModel->setNumber($newOrderNumber);

                // setting ordernumber to orderobject
                $orderObject->setNumber($newOrderNumber);
;
                foreach ($orderObject->getDetails() as $detailModel) {
                    $detailModel->setNumber($newOrderNumber);
                }

                // If there is no shipping address, set billing address to be the shipping address
                if ($customerDbResult[0]['customer']['defaultShippingAddress'] === null) {
                    $customerDbResult[0]['customer']['defaultShippingAddress'] = $customerDbResult[0]['customer']['defaultBillingAddress'];
                }

                // get Customer-Model for Customer-Information
                $builder->select('kunde')
                    ->from(\Shopware\Models\Customer\Customer::class, 'kunde')
                    ->where('kunde.id = ?1')
                    ->setParameter(1, $customerDbResult[0]['customerId']);
                $customerData = $builder->getQuery()->getArrayResult();

                $customerObject = new Shopware\Models\Customer\Customer();
                $customerObject->setNumber($customerData[0]['number']);
                $customerObject->setPaymentId($customerData[0]['paymentId']);
                $customerObject->setGroup($customerData[0]['groupKey']);
                $customerObject->setPriceGroup($customerData[0]['priceGroupId']);
                $customerObject->setEncoderName($customerData[0]['encoderName']);
                $customerObject->setPassword($customerData[0]['hashPassword']);
                $customerObject->setActive($customerData[0]['active']);
                $customerObject->setEmail($customerData[0]['email']);
                $customerObject->setFirstLogin($customerData[0]['firstLogin']);
                $customerObject->setLastLogin($customerData[0]['lastLogin']);
                $customerObject->setAccountMode($customerData[0]['accountMode']);
                $customerObject->setConfirmationKey($customerData[0]['confirmationKey']);
                $customerObject->setSessionId($customerData[0]['sessionId']);
                $customerObject->setNewsletter($customerData[0]['newsletter']);
                $customerObject->setValidation($customerData[0]['validation']);
                $customerObject->setAffiliate($customerData[0]['affiliate']);
                $customerObject->setPaymentPreset($customerData[0]['paymentPreset']);
                $customerObject->setReferer($customerData[0]['referer']);
                $customerObject->setInternalComment($customerData[0]['internalComment']);
                $customerObject->setFailedLogins($customerData[0]['failedLogins']);
                $customerObject->setLockedUntil($customerData[0]['lockedUntil']);
                $customerObject->setSalutation($customerData[0]['salutation']);
                $customerObject->setTitle($customerData[0]['title']);
                $customerObject->setFirstname($customerData[0]['firstname']);
                $customerObject->setLastname($customerData[0]['lastname']);
                $customerObject->setBirthday($customerData[0]['birthday']);
                Shopware()->Models()->persist($customerObject);

                // copy customer number into billing address from customer
                // Casting null values to empty strings to fulfill the restrictions of the s_order_billingaddress table

                $billingAddress = [
                    'id'                        => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['id'])          ? $customerDbResult[0]['customer']['defaultBillingAddress']['id'] : ' ',
                    'company'                   => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['company'])     ? $customerDbResult[0]['customer']['defaultBillingAddress']['company'] : ' ',
                    'department'                => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['department'])  ? $customerDbResult[0]['customer']['defaultBillingAddress']['department'] : ' ',
                    'title'                     => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['title'])       ? $customerDbResult[0]['customer']['defaultBillingAddress']['title'] : ' ',
                    'salutation'                => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['salutation'])  ? $customerDbResult[0]['customer']['defaultBillingAddress']['salutation']: ' ',
                    'firstname'                 => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['firstname'])   ? $customerDbResult[0]['customer']['defaultBillingAddress']['firstname']: ' ',
                    'lastname'                  => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['lastname'])    ? $customerDbResult[0]['customer']['defaultBillingAddress']['lastName']: ' ',
                    'street'                    => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['street'])      ? $customerDbResult[0]['customer']['defaultBillingAddress']['street']: ' ',
                    'zipcode'                   => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['zipCode'])     ? $customerDbResult[0]['customer']['defaultBillingAddress']['zipCode']: ' ',
                    'city'                      => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['city'])        ? $customerDbResult[0]['customer']['defaultBillingAddress']['city']: ' ',
                    'phone'                     => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['phone'])       ? $customerDbResult[0]['customer']['defaultBillingAddress']['phone']: ' ',
                    'vatId'                     => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['vatId'])       ? $customerDbResult[0]['customer']['defaultBillingAddress']['vatId']: ' ',
                    'additionalAddressLine1'    => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine1']) ? $customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine1']: ' ',
                    'additionalAddressLine2'    => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine2']) ? $customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine2']: ' ',
                    'countryId'                 => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['countryId'])   ? $customerDbResult[0]['customer']['defaultBillingAddress']['countryId']: ' ',
                    'stateId'                   => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['stateId'])     ? $customerDbResult[0]['customer']['defaultBillingAddress']['stateId']: ' ',
                    'number'                    => !empty($customerDbResult[0]['customer']['number']) ? $customerDbResult[0]['customer']['number'] : ' ',
                ];
                
                // make an instance of Billingmodel
                $billingModel = new Shopware\Models\Order\Billing();
                $billingModel->fromArray($billingAddress);

                $builder->select('country')
                    ->from(\Shopware\Models\Country\Country::class,'country')
                    ->where('country.id = ?1')
                    ->setParameter(1, $customerDbResult[0]['customer']['defaultBillingAddress']['countryId']);
                $country = $builder->getQuery()->getArrayResult();
                $countryModel = new \Shopware\Models\Country\Country();
                $countryModel = $countryModel->fromArray($country[0]);
                Shopware()->Models()->persist($countryModel);
                // setting some Values for Billingmodel
                $billingModel->setCountry($countryModel);
                $billingModel->setCustomer($customerObject);
                $billingModel->setLastName($customerDbResult[0]['customer']['lastname']);
                $billingModel->setZipCode($customerDbResult[0]['customer']['defaultShippingAddress']['zipcode']);
                $billingModel->setOrder($orderObject);
                Shopware()->Models()->persist($billingModel);
                
                // Casting null values to empty strings to fulfill the restrictions of the s_order_shippingaddress table
                $shippingAddress = array_map(function ($value) {
                    return (string)$value;
                }, $result[0]['customer']['defaultShippingAddress']);
                $shippingAddress = [
                    'id'                        => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['id'])          ? $customerDbResult[0]['customer']['defaultBillingAddress']['id'] : ' ',
                    'company'                   => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['company'])     ? $customerDbResult[0]['customer']['defaultBillingAddress']['company'] : ' ',
                    'department'                => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['department'])  ? $customerDbResult[0]['customer']['defaultBillingAddress']['department'] : ' ',
                    'title'                     => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['title'])       ? $customerDbResult[0]['customer']['defaultBillingAddress']['title'] : ' ',
                    'salutation'                => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['salutation'])  ? $customerDbResult[0]['customer']['defaultBillingAddress']['salutation']: ' ',
                    'firstname'                 => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['firstname'])   ? $customerDbResult[0]['customer']['defaultBillingAddress']['firstname']: ' ',
                    'lastname'                  => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['lastname'])    ? $customerDbResult[0]['customer']['defaultBillingAddress']['lastname']: ' ',
                    'street'                    => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['street'])      ? $customerDbResult[0]['customer']['defaultBillingAddress']['street']: ' ',
                    'zipcode'                   => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['zipcode'])     ? $customerDbResult[0]['customer']['defaultBillingAddress']['zipCode']: ' ',
                    'city'                      => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['city'])        ? $customerDbResult[0]['customer']['defaultBillingAddress']['city']: ' ',
                    'phone'                     => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['phone'])       ? $customerDbResult[0]['customer']['defaultBillingAddress']['phone']: ' ',
                    'vatId'                     => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['vatId'])       ? $customerDbResult[0]['customer']['defaultBillingAddress']['vatId']: ' ',
                    'additionalAddressLine1'    => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine1']) ? $customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine1']: ' ',
                    'additionalAddressLine2'    => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine2']) ? $customerDbResult[0]['customer']['defaultBillingAddress']['additionalAddressLine2']: ' ',
                    'countryId'                 => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['countryId'])   ? $customerDbResult[0]['customer']['defaultBillingAddress']['countryId']: ' ',
                    'stateId'                   => !empty($customerDbResult[0]['customer']['defaultBillingAddress']['stateId'])     ? $customerDbResult[0]['customer']['defaultBillingAddress']['stateId']: ' ',
                    'number'                    => !empty($customerDbResult[0]['customer']['number']) ? $customerDbResult[0]['customer']['number'] : '',
                ];

                // make a new instance of Shippingmodel
                // Create new entry in s_order_shippingaddress
                $shippingModel = new Shopware\Models\Order\Shipping();
                $shippingModel->fromArray($shippingAddress);
                $shippingModel->setCountry($countryModel);
                $shippingModel->setCustomer($customerObject);
                $shippingModel->setOrder($orderObject);
                $shippingModel->setZipCode($customerDbResult[0]['customer']['defaultBillingAddress']['zipcode']);
                Shopware()->Models()->persist($shippingModel);

                
                $statusModel = Shopware()->Models()->find(\Shopware\Models\Order\Status::class, '0');
                
                // Finally set the order to be a regular order
                $orderObject->setOrderStatus($statusModel);
                $orderObject->setTemporaryId($transactionData['IDENTIFICATION_UNIQUEID']);
                $orderObject->setTransactionId($transactionData['IDENTIFICATION_TRANSACTIONID']);
                $orderObject->setInternalComment($transactionData['PROCESSING_TIMESTAMP'].'\n Short-Id: '.$transactionData['IDENTIFICATION_SHORTID']);
                $orderObject->setClearedDate($transactionData['PROCESSING_TIMESTAMP']);

                Shopware()->Models()->flush();
                $this->View()->assign(['success' => true]);
            }

        } catch (Exception $e){
            self::hgw()->Logging('convertOrder failed | Message:'.$e->getMessage());
        }
    }

	/**
	 * function to deactivate CSRF token
	 * validation for specified actions
	 * @return array of ignored actions
	 */
	public function getWhitelistedCSRFActions()
	{
		return array(
            'afterEasy',
            'response',
            'responseReg',
            'responseHpr',
            'alibi',
            'notify',
            'rawnotify',
            'wallet',
            'saveBirthdate',
            'savePayment',
            'succsess'
		);
	}
}