<?php
/**
 * Heidelpay
*
* @category Shopware
* @package Shopware_Plugins
* @subpackage Plugin
* @link http://www.heidelpay.com
* @copyright Copyright (c) 2016, heidelpay GmbH
* @author Jens Richter / Sascha Pflueger
*/

class Shopware_Controllers_Backend_BackendHgw extends Shopware_Controllers_Backend_ExtJs implements Enlight_Hook{
	var $showButtons = true;

	/**
	 * Action to load all data for the backend view
	 * and prepare the html output
	 */
	public function loadDataAction(){
		try{
			$transID = $this->Request()->getParam('transID');
			$payName = $this->Request()->getParam('payName');
			$payDesc = $this->Request()->getParam('payDesc');
			$beLocaleId = $this->getBeLocaleId();
			$this->setSubShop($transID);
				
			$transactions = $this->getTransactions($transID);
			$buttons = $this->getButtons($transactions, $payName, $beLocaleId);
			$action = $this->getActionTable($beLocaleId);
			$transTable = $this->getTransTable($transactions, $beLocaleId);

			$snipPay = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('pay', $beLocaleId, 'backend/heidelBackend');
			$snipRefresh = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('refreshPage', $beLocaleId, 'backend/heidelBackend');
			$snipNotrans = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('noTrans', $beLocaleId, 'backend/heidelBackend');
			$snipNote = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('note', $beLocaleId, 'backend/heidelBackend');
				
			$retArr['transID'] = $transID;
			$retArr['methName'] = $payDesc;
			$retArr['buttons'] = $buttons;
			$retArr['action'] = $action;
			$retArr['transTable'] = $transTable;
			$retArr['transCount'] = count($transactions);
			if(count($transactions) == '0'){
				$retArr['snippets']['notrans'] = $snipNotrans;
			}
			$retArr['snippets']['pay'] = $snipPay;
			$retArr['snippets']['refresh'] = $snipRefresh;
			$retArr['snippets']['note'] = $snipNote;

			print json_encode($retArr);
			exit;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('loadDataAction (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Action that checks for new transactions
	 * and regenerates the transaction table if necessary
	 */
	public function getUpdateAction(){
		try{
			$transID = $this->Request()->getParam('transID');
			$payName = $this->Request()->getParam('payName');
			$prevCount = $this->Request()->getParam('transCount');
			$beLocaleId = $this->getBeLocaleId();
			$this->setSubShop($transID);
			$transactions = $this->getTransactions($transID);
				
			if(count($transactions) > $prevCount){
				$buttons = $this->getButtons($transactions, $payName, $beLocaleId);
				$transTable = $this->getTransTable($transactions, $beLocaleId, true);
				$retArr['buttons'] = $buttons;
				$retArr['transTable'] = $transTable;
				$retArr['update'] = 'true';
			}else{
				$retArr['update'] = 'false';
			}
				
			print json_encode($retArr);
			exit;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getUpdateAction (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Action to do request from backend module
	 */
	public function requestAction(){
		try{
			$meth = $this->Request()->getParam('meth');
			$transID = $this->Request()->getParam('transID');
			$trans = json_decode($this->Request()->getParam('trans'));
			$amount = str_replace(',','.',$this->Request()->getParam('amount'));
			$modul = str_replace(',','.',$this->Request()->getParam('modul'));
			$modul = substr($modul,0,strpos($modul,'_'));
			$beLocaleId = $this->getBeLocaleId();
			$this->setSubShop($transID);

            $config = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config();

            $payName = '';
            if (isset($trans->payName) && (!empty($trans->payName))) {
                $payName = $trans->payName;
            } else {
                $payName = $this->Request()->getParam('modul');
                $payName = str_replace('hgw_', '', $payName);
            }

            switch($payName){
				case 'pay':
					$payName = 'va';
					break;
				case 'p24':
				case 'sue':
					$payName = 'ot';
					break;
				case 'bs':
                case 'san':
                case 'ivpd':
                case 'papg':
                case 'ivb2b':
					$payName = 'iv';
					break;
				case 'mpa':
					$payName = 'wt';
					break;
                case 'hpr':
                case 'hps':
                    $payName = 'hp';
                    break;
				default:
					$payName = $trans->payName;
					break;
			}

            if (isset($trans->uid) && (!empty($trans->uid)))
            {
                $transactions = $this->getTransactions($transID, $trans->uid, $meth);
            } else {
                $transactions = $this->getTransactions($transID,NULL, $meth);
            }

            $data = $transactions[0];
            $formerPaTransaction = $data;
			$data['SECURITY_SENDER'] = trim($this->FrontendConfigHGW()->HGW_SECURITY_SENDER);
			$data['USER_LOGIN'] = trim($this->FrontendConfigHGW()->HGW_USER_LOGIN);
			$data['USER_PWD'] = trim($this->FrontendConfigHGW()->HGW_USER_PW);
			$data['PAYMENT_CODE'] = strtoupper($payName).'.'.strtoupper($meth);
			$data['PRESENTATION_AMOUNT'] = $amount;
			$data['FRONTEND_ENABLED'] = 'false';
//			$data['FRONTEND_MODE'] = 'DEFAULT';
			$data['FRONTEND_MODE'] = 'WHITELABEL';
			$data['IDENTIFICATION_REFERENCEID'] = $data['IDENTIFICATION_UNIQUEID'];
            // specials for heidelpay factoring
			if(
                ($config->HGW_FACTORING_MODE == "1") &&
                (
                    ($this->Request()->getParam('modul') == "hgw_ivb2b")||
                    ($this->Request()->getParam('modul') == "hgw_papg")
                )
            ){
                //setting Invoice ID for factoring solution
                $data['IDENTIFICATION_INVOICEID'] = $this->getInvoiceidByTransactionId($transID);

                if(empty($data['IDENTIFICATION_INVOICEID']) || $data['IDENTIFICATION_INVOICEID'] ==""){
                    Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('requestAction (BE) | No invoice-id found for transaction '.$transID);
                    return false;
                }

                switch ($meth){
                    case "rv1":
                        $meth = "rv";
                        $data['PAYMENT_CODE'] = strtoupper($payName).'.'.strtoupper($meth);
                        $data["PAYMENT_REVERSALTYPE"] = "CANCEL";
                        break;
                    case "rv2":
                        $meth = "rv";
                        $data['PAYMENT_CODE'] = strtoupper($payName).'.'.strtoupper($meth);
                        $data["PAYMENT_REVERSALTYPE"] =  "RETURN";
                        break;
                    case "rv3":
                        $meth = "rv";
                        $data['PAYMENT_CODE'] = strtoupper($payName).'.'.strtoupper($meth);
                        $data["PAYMENT_REVERSALTYPE"] = "CREDIT";
                        break;
                    default:
                        break;
                }
            }

            // switching request-url
			$hgwBootstrapVariables = Shopware()->Plugins()->Frontend()->HeidelGateway();
			if(strtoupper($data['TRANSACTION_MODE']) == 'LIVE'){
                $hgwBootstrapVariables::$requestUrl = $hgwBootstrapVariables::$live_url;
			}else{
                $hgwBootstrapVariables::$requestUrl = $hgwBootstrapVariables::$test_url;
			}

            // setting Basket-Id for Payolution
            if(
                ($data['ACCOUNT_BRAND'] == 'PAYOLUTION_DIRECT')
             || ($data['ACCOUNT_BRAND'] == 'SANTANDER')
             || ($data['CRITERION_IVBRAND'] == 'PAYOLUTION')
             || ($data['CRITERION_IVBRAND'] == 'SANTANDER')
             || ($data['CRITERION_FACTORING'] == 'true')
            )
            {
                // call Heidelpay-Basket-Api
                switch ($data['PAYMENT_CODE'])
                {
                    case 'IV.FI':
                    case 'IV.RV':
                    case 'IV.RF':
                            // fetch all articles for Basket-Api-Call from order
                            $orderDetails = $this->fetchOrderDetailsByUniqueId($data['IDENTIFICATION_UNIQUEID']);

                            // prepare data for heidelpay-basket-api call
                            $dataForBasketApi = self::prepareBackendBasketData($orderDetails);

                            // send heidelpay-basket-api call to receive a BASKET.ID
                            $ta_mode = $this->FrontendConfigHGW()->HGW_TRANSACTION_MODE;
                            $origRequestUrl = $hgwBootstrapVariables::$requestUrl;

                            if(is_numeric($ta_mode) && (($ta_mode == 0) || ($ta_mode == 3))){
                                $hgwBootstrapVariables::$requestUrl = $hgwBootstrapVariables::$live_url_basket;
                            }else{
                                $hgwBootstrapVariables::$requestUrl = $hgwBootstrapVariables::$test_url_basket;
                            }
                            // do Basket-Api-Request
                            $params['raw']= $dataForBasketApi;
                            $response = $this->callDoRequest($params);

                            // switch back to post url, after basket request is sent
                            $hgwBootstrapVariables::$requestUrl = $origRequestUrl;

                            if(!empty($response['basketId']))
                            {
                                $data['BASKET_ID'] = $response['basketId'];
                            }
                        break;
                    default:
                        break;
                }
            }

            // deleting unneccessary Data from data
            unset($data['IDENTIFICATION_UNIQUEID']);unset($data['FRONTEND_RESPONSE_URL']);unset($data['FRONTEND_CSS_PATH']);
            unset($data['ACCOUNT_NUMBER']);unset($data['CRITERION_DBONRG']);unset($data['CRITERION_SHIPPAY']);unset($data['CRITERION_GATEWAY']);
            unset($data['CRITERION_WALLET']);unset($data['CRITERION_WALLET_PAYNAME']);unset($data['CRITERION_RESPONSE_URL']);
            unset($data['CRITERION_PUSH_URL']);unset($data['CRITERION_INSURANCE-RESERVATION']);unset($data['CUSTOMER_OPTIN']);
            unset($data['PROCESSING_RESULT']);unset($data['PROCESSING_RETURN']);unset($data['PROCESSING_CODE']);
            unset($data['PROCESSING_RETURN_CODE']);unset($data['PROCESSING_STATUS_CODE']);unset($data['PROCESSING_REASON_CODE']);
            unset($data['PROCESSING_REASON']);unset($data['PROCESSING_TIMESTAMP']);unset($data['PROCESSING_STATUS']);
            unset($data['CONNECTOR_ACCOUNT_BANK']);unset($data['CONNECTOR_ACCOUNT_BIC']);unset($data['CONNECTOR_ACCOUNT_NUMBER']);
            unset($data['CONNECTOR_ACCOUNT_IBAN']);unset($data['CONNECTOR_ACCOUNT_COUNTRY']);unset($data['CONNECTOR_ACCOUNT_HOLDER']);
            unset($data['CONNECTOR_ACCOUNT_USAGE']);unset($data['CLEARING_AMOUNT']);unset($data['CLEARING_CURRENCY']);
            unset($data['CLEARING_DESCRIPTOR']);unset($data['CUSTOMER_OPTIN_2']);unset($data['CONFIG_OPTIN_TEXT']);
            unset($data['var.Register']);unset($data['var.sTarget']);unset($data['var.sepa']);unset($data['._csrf_token']);

            // prepare parameters for sending and replace all "_" with "."
			foreach($data as $key => $value){
				if(is_int(strpos($key, 'CLEARING_'))){ unset($data[$key]); continue; }
				if(is_int(strpos($key, 'ACCOUNT_'))){ unset($data[$key]); continue; }
				if(is_int(strpos($key, 'AUTHENTICATION_'))){ unset($data[$key]); continue; }
				if(is_int(strpos($key, 'PROCESSING_'))){ unset($data[$key]); continue; }
				$pos = strpos($key, '_');
				$newKey = str_replace('_','.',substr($key, 0, $pos+1));
				$newKey .= substr($key, $pos+1);
				$data[$newKey] = $value;
				unset($data[$key]);
			}

			$resp = $this->callDoRequest($data);
            Shopware()->Plugins()->Frontend()->HeidelGateway()->saveRes($resp);

			// switch, to update right table, depending on used frontend module
			if(($trans->payName == 'bs') && ($meth == 'fi')){
				if(strtolower($modul) == 'heidelpay'){
					$sql = 'UPDATE `s_plugin_heidelpay_billsafe`';
				}else{
					$sql = 'UPDATE `s_plugin_hgw_billsafe`';
				}
				$sql .= 'SET `Request` = ? WHERE `temporaryID` = ?';
				Shopware()->Db()->query($sql, array(serialize($resp), $resp['IDENTIFICATION_TRANSACTIONID']));
			}

            // mark orders as paid in case of IV.FI or HP.FI
            if(
                ($resp["PROCESSING_RESULT"] == "ACK") &&
                (
                    ($resp["PAYMENT_CODE"] == "IV.FI") ||
                    ($resp["PAYMENT_CODE"] == "HP.FI")
                )
            ){
                try{
                    $this->updateOrderPaymentStatus($resp["IDENTIFICATION_REFERENCEID"]);
                } catch (Exception $e){

                }
            }

			$transactions = $this->getTransactions($transID);
			$transTable = $this->getTransTable($transactions, $beLocaleId, true);
			$retArr['transTable'] = $transTable;
			$retArr['reload'] = 'true';
				
			print json_encode($retArr);
			exit;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('requestAction (BE) | '.$e->getMessage());
			return;
		}
	}

    /*
	 * Method to get all transaction with the same IDENTIFICATION_TRANSACTIONID
	 * if second pram is set, the method returns just the selected transaction
	 * @param string $transID
	 * @param string $uiD
	 * @return array $transactions
	 */
    public function getTransactions($transID, $uid = NULL, $method = NULL){
        try{
            $table = $this->FrontendConfigHGW()->HGW_SECURITY_SENDER;

            // check new DB-Table for transactions

            $sql = '';
            $params[] = $transID;

            /* ********************* neuer Code ********************* */
            switch ($method)
            {
                case 'rf':
                    $sql = 'SELECT `jsonresponse` FROM `s_plugin_hgw_transactions` WHERE `transactionid` = ? '
                        .'AND (`payment_type` = "DB" '
                        .'OR `payment_type` = "CP" '
                        .'OR `payment_type` = "RB" '
                        .'OR `payment_type` = "RC" '
                        .') ';
                   /* if(($uid != NULL) && ($uid != '')){
                        $sql .= 'AND `uniqueid` = ? ';
                        $params[] = $uid;
                    }*/
                    $sql .= 'ORDER BY `datetime` DESC';
                    break;
                case 'rv':
                    $sql = 'SELECT `jsonresponse` FROM `s_plugin_hgw_transactions` WHERE `transactionid` = ?'
                        .'AND `payment_type` = "PA" ';
                    if(($uid != NULL) && ($uid != '')){
                        $sql .= 'AND `uniqueid` = ?';
                        $params[] = $uid;
                    }
                    $sql .= 'ORDER BY `datetime` DESC';
                    break;
                default:
                    $sql = 'SELECT `jsonresponse` FROM `s_plugin_hgw_transactions` WHERE `transactionid` = ?';
                    if(($uid != NULL) && ($uid != '')){
                        $sql .= 'AND `uniqueid` = ?';
                        $params[] = $uid;
                    }
                    $sql .= 'ORDER BY `datetime` DESC';
                    break;
            }
            /* ********************* Ende neuer Code ********************* */

            try{
                $data = Shopware()->Db()->fetchAll($sql, $params);
                // check old DB-Table for transactions
                unset($params);

                $sql = 'SHOW TABLES LIKE "'.$table.'"';
                $check = Shopware()->Db()->fetchAll($sql);
                if(!empty($check)){
                    $sql = '
						SELECT `SERIAL` FROM '.$table.'
						WHERE `IDENTIFICATION_TRANSACTIONID` = ?
					';
                    $params[] = $transID;
                    if(($uid != NULL) && ($uid != '')){
                        $sql .= 'AND `IDENTIFICATION_UNIQUEID` = ?';
                        $params[] = $uid;
                    }
                    $sql .= 'ORDER BY `created` DESC';
                    $data = array_merge($data, Shopware()->Db()->fetchAll($sql, $params));
                }
            }catch(Exception $e){
                if(count($data) == '0'){
                    Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getTransactions (BE) | '.$e->getMessage());
//                    return $transactions;
                    return $data;
                }
            }

            foreach($data as $key => $value){
                if(isset($value['jsonresponse'])){
                    $transactions[] = json_decode($value['jsonresponse'], true);
                }elseif(isset($value['SERIAL'])){
                    $transactions[] = unserialize($value['SERIAL']);
                }
            }

            foreach($transactions as $tKey => $transaction){
                foreach($transaction as $transKey => $transVal){
                    $transaction[$transKey] = urldecode($transVal);
                }
                $transactions[$tKey] = $transaction;
            }

            return $transactions;
        }catch(Exception $e){
            Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getTransactions (BE) | '.$e->getMessage());
            return;
        }
    }

	/*
	 * Method to generate the html code for the transaction buttons
	 * @param array $transactions
	 * @param string $payName
	 * @param int $beLocaleId
	 * @return array $buttonRet
	 */
	public function getButtons($transactions, $payName, $beLocaleId){
		try{
			$payName = substr($payName, strpos($payName,'_')+1);

			$btns['cp']['name']		= 'Capture';
			$btns['cp']['icon']		= 'fa-download';
			$btns['cp']['active']	= 'false';
				
			$btns['rf']['name']		= 'Refund';
			$btns['rf']['icon']		= 'fa-undo';
			$btns['rf']['active']	= 'false';
				
			$btns['rb']['name']		= 'Rebill';
			$btns['rb']['icon']		= 'fa-repeat';
			$btns['rb']['active']	= 'false';
				
			$btns['rv']['name']		= 'Reversal';
			$btns['rv']['icon']		= 'fa-reply';
			$btns['rv']['active']	= 'false';
            if(in_array($payName,["papg","ivb2b"]) /*&& Factoring ist aktiv*/){
                $btns['rv1']['name']	= 'Reversal Storno';
                $btns['rv1']['icon']	= 'fa-reply';
                $btns['rv1']['active']	= 'false';

                $btns['rv2']['name']	= 'Reversal Retoure';
                $btns['rv2']['icon']	= 'fa-reply';
                $btns['rv2']['active']	= 'false';

                $btns['rv3']['name']	= 'Reversal Gutschrift';
                $btns['rv3']['icon']	= 'fa-reply';
                $btns['rv3']['active']	= 'false';
            }

			$btns['fi']['name']		= 'Finalize';
			$btns['fi']['icon']		= 'fa-truck';
			$btns['fi']['active']	= 'false';
			if($this->showButtons){
				foreach(array_reverse($transactions) as $key => $value){
                    if($payName == 'pay'){ $payName = 'va'; }

                    $payChan = 'HGW_'.strtoupper($payName).'_CHANNEL';
					if($value['TRANSACTION_CHANNEL'] != $this->FrontendConfigHGW()->$payChan){ break; }

					$payInfo = $this->getPayInfo($value['PAYMENT_CODE'], $beLocaleId);

					if($payName == 'papg'){ $payName = 'iv'; $papg = true; }
					if($payName == 'ivb2b'){ $payName = 'iv'; $ivb2b = true; }
					if($payName == 'san'){ $payName = 'iv'; $san = true; }
					if($payName == 'ivpd'){ $payName = 'iv'; $ivpd = true; }

					switch($payName){
						case 'cc':
						case 'dc':
						case 'dd':
						case 'mpa':
							if($payInfo['payType'] == 'pa'){
								$btns['cp']['active'] = $btns['rv']['active'] = 'true';

								$maxCp = $maxRv = $value['PRESENTATION_AMOUNT'];
								$btns['cp']['trans'][] = $btns['rv']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							if(($payInfo['payType'] == 'db') || ($payInfo['payType'] == 'cp')){
								$btns['cp']['active'] = $btns['rv']['active'] = 'false';
								$btns['rf']['active'] = $btns['rb']['active'] = 'true';
								if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
								$btns['rf']['trans'][] = $btns['rb']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							break;
						case 'va':
							if($payInfo['payType'] == 'pa'){
								$btns['cp']['active'] = $btns['rv']['active'] = 'true';

								$maxCp = $maxRv = $value['PRESENTATION_AMOUNT'];
								$btns['cp']['trans'][] = $btns['rv']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							if(($payInfo['payType'] == 'db') || ($payInfo['payType'] == 'cp')){
								$btns['cp']['active'] = $btns['rv']['active'] = 'false';
								$btns['rf']['active'] = 'true';

								if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
								$btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							break;
						case 'sue':
						case 'p24':
							if($payInfo['payType'] == 'rc'){
								$btns['rf']['active'] = 'true';

								if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
								$btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							break;
						case 'bs':
						case 'iv':
                            if($ivpd || $san)
						    {
                                if($payInfo['payType'] == 'pa'){
                                    $btns['rv']['active'] = $btns['fi']['active'] = 'true';

                                    $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
                                    $btns['rv']['trans'][] = $btns['fi']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
                                }
                                if($payInfo['payType'] == 'rc'){
                                    $btns['rv']['active'] = $btns['fi']['active'] = 'false';
                                    $btns['rf']['active'] = 'true';

                                    if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
                                    $btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
                                }
                                if($payInfo['payType'] == 'fi') {
                                    $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];

                                    if ($ivpd || $san) {
                                        $btns['fi']['active'] = $btns['rv']['active'] = 'false';
//                                        $btns['rf']['active'] = 'true';
                                        if (!isset($maxRf)) {
                                            $maxRf = $value['PRESENTATION_AMOUNT'];
                                        }
                                        $btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
                                    } else {

                                        $btns['fi']['active'] = 'false';
                                        $btns['rv']['active'] = 'true';
                                    }
                                }
                            } elseif ($papg || $ivb2b){
                                if ($payInfo['payType'] == 'pa') {
                                    $btns['rv']['active'] =
                                    $btns['fi']['active'] = 'true';

                                    $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
                                    $btns['fi']['trans'][] =
                                    $btns['rf']['trans'][] = $this->storeTrans($value,$payName, $payInfo);

                                    // activates reversal-buttons for invoice secured with factoring
                                    if($this->FrontendConfigHGW()->HGW_FACTORING_MODE == "1"){
                                        $btns['rv1']['active'] =
                                        $btns['rv2']['active'] =
                                        $btns['rv3']['active'] = 'true';

                                        $btns['rv1']['trans'][] =
                                        $btns['rv2']['trans'][] =
                                        $btns['rv3']['trans'][] = $this->storeTrans($value,$payName, $payInfo);
                                        $maxRv1 = $maxRv2 = $maxRv3 = $value['PRESENTATION_AMOUNT'];
                                    unset($btns['rv']);
                                    }

                                }

                                if (
                                    ($payInfo['payType'] == 'fi') &&
                                    ($value['PROCESSING_RESULT'] == "ACK")
                                ) {
                                    $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
                                    $btns['rv']['active'] = 'true';
                                    $btns['fi']['active'] =	'false';
//                                    $btns['rf']['active'] = 'true';
//                                    if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
                                } else {
                                    $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
                                    $btns['rv']['active'] =
                                    $btns['fi']['active'] =	'true';
                                }

                                if ($payInfo['payType'] == 'rc') {
                                    $btns['rv']['active'] =
                                    $btns['rv1']['active'] =
                                    $btns['rv2']['active'] =
                                    $btns['rv3']['active'] =
                                    $btns['fi']['active'] = 'false';
                                    $btns['rf']['active'] = 'true';

                                    if (!isset($maxRf)) {
                                        $maxRf = $value['PRESENTATION_AMOUNT'];
                                    }
                                    $btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
                                }

                                if ($payInfo['payType'] == 'rv') {
                                    $btns['fi']['active'] =
                                    $btns['rv']['active'] = 'true';
//                                    $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
                                    $maxRv = $maxFi = $btns['fi']['trans']['0']['amount'];
                                }

                            }
                            else {
                                $btns['rv']['active'] = $btns['fi']['active'] = 'false';
								$btns['fi']['active'] = 'false';
								$btns['rv']['active'] = 'true';
                            }

//                            if($payInfo['payType'] == 'pa'){
//								$btns['rv']['active'] = $btns['fi']['active'] = 'true';
//
//								$maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
//								$btns['rv']['trans'][] = $btns['fi']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
//							}
//							if($payInfo['payType'] == 'rc'){
//								$btns['rv']['active'] = $btns['fi']['active'] = 'false';
//								$btns['rf']['active'] = 'true';
//
//								if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
//								$btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
//							}
//							if($payInfo['payType'] == 'fi'){
//                                $maxRv = $maxFi = $value['PRESENTATION_AMOUNT'];
//
//							    if(
//							        $ivpd
////                                || $san
//                                ){
//                                    $btns['fi']['active'] =  $btns['rv']['active'] ='false';
//                                    $btns['rf']['active'] = 'true';
//
//                                    if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
//                                    $btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
//                                } else {
//                                    $btns['fi']['active'] = 'false';
//                                    $btns['rv']['active'] = 'true';
//                                }
								//$btns['rv']['active'] = $btns['fi']['active'] = 'false';
//								$btns['fi']['active'] = 'false';
//								$btns['rv']['active'] = 'true';
//							}
							break;
						case 'pp':
							if($payInfo['payType'] == 'pa'){
								$btns['rv']['active'] = 'true';

								$maxRv = $value['PRESENTATION_AMOUNT'];
								$btns['rv']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							if($payInfo['payType'] == 'rc'){
								$btns['rv']['active'] = 'false';
								$btns['rf']['active'] = 'true';

								if(!isset($maxRf)){	$maxRf = $value['PRESENTATION_AMOUNT']; }
								$btns['rf']['trans'][] = $this->storeTrans($value, $payName, $payInfo);
							}
							break;
                        case 'hpr':
                            $maxFi = $value['PRESENTATION_AMOUNT'];
                            if($payInfo['payType'] == 'pa'){
                                $btns['fi']['active'] = 'true';
                            }
                            if($payInfo['payType'] == 'fi'){
                                $btns['fi']['active'] = 'false';
                            }
                            break;
                        case 'hps':
                            $maxFi = $value['PRESENTATION_AMOUNT'];
                            if($payInfo['payType'] == 'pa'){
                                $btns['fi']['active'] = 'true';
                            }
                            if($payInfo['payType'] == 'fi'){
                                $btns['fi']['active'] = 'false';
                            }
                            break;
						default:
							break;
					}

					if(strtoupper($value['PROCESSING_RESULT']) == 'ACK'){
						if($payInfo['payType'] == 'rf'){ $maxRf = number_format($maxRf, 2,'.','') - $value['PRESENTATION_AMOUNT']; }
						if($payInfo['payType'] == 'cp'){ $maxCp = number_format($maxCp, 2,'.','') - $value['PRESENTATION_AMOUNT']; }
						if($payInfo['payType'] == 'rv'){
							$maxRv = number_format($maxRv, 2,'.','') - $value['PRESENTATION_AMOUNT'];
							$maxCp = number_format($maxCp, 2,'.','') - $value['PRESENTATION_AMOUNT'];
							$maxFi = number_format($maxFi, 2,'.','') - $value['PRESENTATION_AMOUNT'];
                            $maxRv1 = number_format($maxRv1, 2,'.','') - $value['PRESENTATION_AMOUNT'];
                            $maxRv2 = number_format($maxRv2, 2,'.','') - $value['PRESENTATION_AMOUNT'];
                            $maxRv3 = number_format($maxRv3, 2,'.','') - $value['PRESENTATION_AMOUNT'];
						}
						if($maxCp <= 0){ $btns['cp']['active'] = 'false'; }else{ $btns['cp']['active'] = 'true'; }
						if($maxRf <= 0){ $btns['rf']['active'] = 'false'; }
						if($maxRv <= 0){ $btns['rv']['active'] = 'false'; }
						if($maxRv <= 0){ $btns['rv']['active'] = 'false'; }
                        if($maxFi <= 0){ $btns['fi']['active'] = 'false'; }
						if($maxRv1 <= 0){ $btns['rv1']['active'] = 'false'; }
						if($maxRv2 <= 0){ $btns['rv2']['active'] = 'false'; }
						if($maxRv3 <= 0){ $btns['rv3']['active'] = 'false'; }

					}
					if($papg)   { $payName = 'papg'; $papg = false; }
					if($ivb2b)   { $payName = 'ivb2b'; $ivb2b = false; }
					if($san)    { $payName = 'san'; $san = false; }
					if($ivpd)   { $payName = 'ivpd'; $ivpd = false; }
				}

				$btns['rf']['trans'][0]['maxRf'] = number_format($maxRf, 2,'.','');
				$btns['rv']['trans'] = array_reverse($btns['rv']['trans']);
				$btns['rv']['trans'][0]['maxRv'] = number_format($maxRv, 2,'.','');

                $btns['rv1']['trans'] = array_reverse($btns['rv1']['trans']);
				$btns['rv1']['trans'][0]['maxRv1'] = number_format($maxRv1, 2,'.','');
                $btns['rv2']['trans'] = array_reverse($btns['rv2']['trans']);
				$btns['rv2']['trans'][0]['maxRv2'] = number_format($maxRv2, 2,'.','');
                $btns['rv3']['trans'] = array_reverse($btns['rv3']['trans']);
				$btns['rv3']['trans'][0]['maxRv3'] = number_format($maxRv3, 2,'.','');
				$btns['cp']['trans'] = array_reverse($btns['cp']['trans']);
				$btns['cp']['trans'][0]['maxCp'] = number_format($maxCp, 2,'.','');
				$btns['fi']['trans'][0]['maxFi'] = number_format($maxFi, 2,'.','');
			}

			if($this->showButtons){
				$buttonTable = '';
			}else{
				$snipNoaction = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('noAction', $beLocaleId, 'backend/heidelBackend');
				$buttonTable = '<div class="note">'.$snipNoaction.'</div>';
			}

			if(
                (($payName == 'papg') || ($payName == 'ivb2b'))
            && ($this->FrontendConfigHGW()->HGW_FACTORING_MODE == "1")
//            && (false)
            ){
                $buttonTable .= '
                    <table id="buttontable">
                    <colgroup>
                        <col width="117px"/>
                        <col width="117px"/>
                        <col width="117px"/>
                        <col width="118px"/>
                        <col width="118px"/>
                        <col width="118px"/>
                        <col width="118px"/>
                    </colgroup><tr>';
                unset($btns['rv']);

            } else {
                $buttonTable .= '<table id="buttontable"><colgroup><col width="165px"/><col width="165px"/><col width="165px"/><col width="165px"/><col width="165px"/></colgroup><tr>';
                unset($btns['rv1']);unset($btns['rv2']);unset($btns['rv3']);
			}

			foreach($btns as $key => $btn){
				if($btn['active'] == 'true'){
					$btnClass = 'active '.$key;
					$reference[$key] = $btn;
				}else{
					$btnClass = 'inactive';
				}
				$buttonTable .= '<td class="'.$btnClass.'"><span class="fa '.$btn['icon'].' fa-2x"></span><br/>'.$btn['name'].'</td>';
			}
				
			$buttonTable .= '</tr></table>';
			$buttonRet['ref'] = $reference;
			$buttonRet['table'] = $buttonTable;
			return $buttonRet;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getButtons (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method to store current transaction
	 * @param array $value
	 * @param string $payName
	 * @param array $payInfo
	 * @return array $storeTrans
	 */
	public function storeTrans($value, $payName, $payInfo){
		try{
			$storeTrans['uid'] = $value['IDENTIFICATION_UNIQUEID'];
			$storeTrans['sid'] = $value['IDENTIFICATION_SHORTID'];
			$storeTrans['rid'] = $value['IDENTIFICATION_REFERENCEID'];
			$storeTrans['payName'] = $payName;
			$storeTrans['payType'] = $payInfo['payType'];
			$storeTrans['amount'] = $value['PRESENTATION_AMOUNT'];
			$storeTrans['currency'] = $value['PRESENTATION_CURRENCY'];
				
			return $storeTrans;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('storeTrans (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method to generate the html code for the transaction actions
	 * @param string $beLocaleId - backend locale id
	 * @return string $actionTable
	 */
	public function getActionTable($beLocaleId){
		try{
			$snipAction = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('action', $beLocaleId, 'backend/heidelBackend');
			$snipAmount = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('amount', $beLocaleId, 'backend/heidelBackend');

			$actionTable = '<table id="actiontable">
			<colgroup><col width="100px"/><col width="600px"/><col width="125px"/></colgroup>
			<tr>
				<td><div id="typename"></td>
				<td>'.$snipAmount.': <input type="text" id="amount" /></td>
				<td><div id="submit">'.$snipAction.'</div></td>
			</tr></table>';
				
			return $actionTable;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getActionTable (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method to generate the html code for the transaction table
	 * @param array $transactions
	 * @param string $beLocaleId - backend locale id
	 * @param bool $update
	 * @return string $transTable
	 */
	public function getTransTable($transactions, $beLocaleId, $update = false){
		try{
			$snipDate = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('date', $beLocaleId, 'backend/heidelBackend');
			$snipResult = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('result', $beLocaleId, 'backend/heidelBackend');
			$snipShortid = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('shortid', $beLocaleId, 'backend/heidelBackend');
			$snipType = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('type', $beLocaleId, 'backend/heidelBackend');
			$snipAmount = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('amount', $beLocaleId, 'backend/heidelBackend');
			$snipCurr = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('currency', $beLocaleId, 'backend/heidelBackend');
			$snipTotal = Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('total', $beLocaleId, 'backend/heidelBackend');

			$sum = (float) 0;
			$curr = '';
			$transTable = '';
				
			if(!$update){ $transTable .= '<table id="transtable">'; }
			$transTable .= '<colgroup><col width="250px"/><col width="75px"/><col width="175px"/><col width="125px"/><col width="100px"/><col width="100px"/></colgroup>';
			$transTable .= '<tr class="gray"><th>'.$snipDate.'</th><th>'.$snipResult.'</th><th>'.$snipShortid.'</th><th>'.$snipType.'</th><th>'.$snipAmount.'</th><th>'.$snipCurr.'</th></tr>';
				
			foreach($transactions as $key => $value){
				$payInfo = $this->getPayInfo($value['PAYMENT_CODE'], $beLocaleId);
				$minus = '';

				if((($payInfo['payType'] == 'rf') || ($payInfo['payType'] == 'cb')) && (strtolower($value['PROCESSING_RESULT']) == 'ack')){
					$amoutClass = 'red';
					$sum = $sum - $value['PRESENTATION_AMOUNT'];
					$curr = $value['PRESENTATION_CURRENCY'];
					$minus = '- ';
				}elseif((($payInfo['payType'] == 'cp') || ($payInfo['payType'] == 'rc') || ($payInfo['payType'] == 'db') || ($payInfo['payType'] == 'rb')) && (strtolower($value['PROCESSING_RESULT']) == 'ack')){
					$amoutClass = 'blue';
					$sum = $sum + $value['PRESENTATION_AMOUNT'];
					$curr = $value['PRESENTATION_CURRENCY'];
				}else{
					$amoutClass = '';
				}

				if($value['PROCESSING_RESULT'] == 'ACK'){
					$icon = 'fa-check';
					$iconClass = 'blue';
				}else{
					$icon = 'fa-remove';
					$iconClass = 'red';
				}

				if(!isset($value['PROCESSING_TIMESTAMP'])){
					$timestamp = '-';
				}else{
					$timestamp = date('d.m.Y - H:i',strtotime($value['PROCESSING_TIMESTAMP'].' UTC'));
				}

				$transTable .= '<tr>';
				$transTable .= '<td>'.$timestamp.'</td>';
				$transTable .= '<td class="center '.$iconClass.'"><span class="fa '.$icon.'" title="'.$value['PROCESSING_RETURN'].'"></span>';
				$transTable .= '<td>'.$value['IDENTIFICATION_SHORTID'].'</td>';
				$transTable .= '<td>'.$payInfo['typeName'].'</td>';
				$transTable .= '<td class="right '.$amoutClass.'">'.$minus.number_format($value['PRESENTATION_AMOUNT'], 2,'.','').'</td>';
				$transTable .= '<td>'.$value['PRESENTATION_CURRENCY'].'</td>';
				$transTable .= '</tr>';
			}
				
			if($sum > 0){ $sumClass = 'blue'; }
			elseif($sum < 0){ $sumClass = 'red'; }
			else{ $sumClass = ''; }
				
			$transTable .= '<tr class="gray"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>'.$snipTotal.'</td><td class="right '.$sumClass.'">'.number_format($sum, 2,'.','').'</td><td>'.$curr.'</td></tr>';
			if(!$update){ $transTable .= '</table>'; }

			return $transTable;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getTransTable (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method that splits PAYMENT_CODE
	 * and returns additional payment information
	 * @param string $payCode
	 * @param string $beLocaleId - backend locale id
	 * @return array $retArr
	 */
	public function getPayInfo($payCode, $beLocaleId){
		try{
			$snipDb	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('db', $beLocaleId, 'backend/heidelBackend');
			$snipRb	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('rb', $beLocaleId, 'backend/heidelBackend');
			$snipPa	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('pa', $beLocaleId, 'backend/heidelBackend');
			$snipCp	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('cp', $beLocaleId, 'backend/heidelBackend');
			$snipRc	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('rc', $beLocaleId, 'backend/heidelBackend');
			$snipRv	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('rv', $beLocaleId, 'backend/heidelBackend');
			$snipRf	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('rf', $beLocaleId, 'backend/heidelBackend');
			$snipCb	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('cb', $beLocaleId, 'backend/heidelBackend');
			$snipFi	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('fi', $beLocaleId, 'backend/heidelBackend');
			$snipIn	= Shopware()->Plugins()->Frontend()->HeidelGateway()->getSnippets('in', $beLocaleId, 'backend/heidelBackend');

			$payCode = explode('.', $payCode);
			/*
			 * payMeth is payment type and
			 * payType is payment method
			 * the keys are intercharged
			 */
			$retArr['payMeth'] = strtolower($payCode[0]);
			$retArr['payType'] = strtolower($payCode[1]);
				
			switch($retArr['payType']){
				case 'db':
					$retArr['typeName'] = $snipDb;
					break;
				case 'rb':
					$retArr['typeName'] = $snipRb;
					break;
				case 'pa':
					$retArr['typeName'] = $snipPa;
					break;
				case 'cp':
					$retArr['typeName'] = $snipCp;
					break;
				case 'rc':
					$retArr['typeName'] = $snipRc;
					break;
				case 'rv':
					$retArr['typeName'] = $snipRv;
					break;
				case 'rf':
					$retArr['typeName'] = $snipRf;
					break;
				case 'cb':
					$retArr['typeName'] = $snipCb;
					break;
				case 'fi':
					$retArr['typeName'] = $snipFi;
					break;
				case 'in':
					$retArr['typeName'] = $snipIn;
					break;
				default:
					$retArr['typeName'] = $retArr['payType'];
					break;
			}

			return $retArr;
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getPayInfo (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method that returns the locale id of the backend user
	 * @return string $user->locale->getId()
	 */
	public function getBeLocaleId(){
		try{
			$auth = Shopware()->Auth();
			$user = $auth->getIdentity();
				
			return $user->locale->getId();
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getBeLocaleId (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * This method provides backend usability in mulitshop installations.
	 *
	 * Method to set the (sub)shop-id (for the backend), to get the right configuration.
	 * Config is loaded per transaction, to avoid transactions with wrong data.
	 * @param string $transID - transactionID
	 */
	public function setSubShop($transID){
		try{
			$sql = '
				SELECT `storeid` FROM `s_plugin_hgw_transactions`
				WHERE `transactionid` = ?
				ORDER BY `datetime` DESC
			';
			$shopID = Shopware()->Db()->fetchOne($sql,$transID);
				
			$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
			$shop = $repository->getActiveById($shopID);
				
			if(is_object($shop)){
				$shop->registerResources(Shopware()->Bootstrap());
				$this->showButtons = true;
			}else{
				if((isset($shopID)) && ($shopID != '')){
					Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging("Backend |
						could't load subshop configuration for:".
							"<br/>TransactionID: ".$transID.
							"<br/>ShopID: ".$shopID
							);
					$this->showButtons = false;
				}
			}
		}catch(Exception $e){
			Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('setSubShop (BE) | '.$e->getMessage());
			return;
		}
	}

	/*
	 * Method that calles doRequest from bootstrap
	 * this method is seperated, so you can hook it
	 * @param string $data
	 */
	public function callDoRequest($data){
	    return Shopware()->Plugins()->Frontend()->HeidelGateway()->doRequest($data);
	}

	/**
	 * Method that returns the call to HeidelPayment config
	 */
	public function FrontendConfig(){
		return Shopware()->Plugins()->Frontend()->HeidelPayment()->Config();
	}

	/**
	 * Method that returns the call to HeidelGateway config
	 */
	public function FrontendConfigHGW(){
		return Shopware()->Plugins()->Frontend()->HeidelGateway()->Config();
	}

    /** fetchOrderByUniqueId()
     * fetches the id from datatable s_order for a specific temporaryId / UniqueId
     * @param $identificationUniqueId
     * @return array
     */
	protected function fetchOrderDetailsByUniqueId($identificationUniqueId)
    {
        $sql = 'SELECT * FROM `s_order` 
                INNER JOIN `s_order_details` 
                ON `s_order`.`ordernumber` = `s_order_details`.`ordernumber`
                WHERE `s_order`.`temporaryID` = ?
                ;';

        $uniqueId = [$identificationUniqueId];
        $orderdetails = Shopware()->Db()->fetchAll($sql,$uniqueId);

        return $orderdetails;
    }

    /**
     * prepareBackendBasketData prepares basket data for basket-api-call from a given array
     * @param $orderDetails
     * @return array
     */
    protected function prepareBackendBasketData($orderDetails)
    {
        // prepare Basicdata for Basket-Api-Call
        $shoppingCart['authentication'] = array(
            'sender' 		=> trim($this->FrontendConfigHGW()->HGW_SECURITY_SENDER),
            'login'			=> trim($this->FrontendConfigHGW()->HGW_USER_LOGIN),
            'password'		=> trim($this->FrontendConfigHGW()->HGW_USER_PW),
        );

        // prepare hole basket data
        // remove dot as seperator for thousands and formate value into cent with no decimals
        $amountNet      = number_format($orderDetails[0]["invoice_amount_net"], 4,".","");
        $amountNet      = bcmul($amountNet, 100, 0);

        $amountGross    = number_format($orderDetails[0]["invoice_amount"], 4,".","");
        $amountGross    = bcmul($amountGross, 100, 0);

        $amountVat 		= number_format(bcsub($amountGross,$amountNet),0,".","");

        $shoppingCart['basket'] = [
            'amountTotalNet' => $amountNet,
            'amountTotalVat' => $amountVat,
            'currencyCode'   => !empty($orderDetails[0]["currency"])  ? $orderDetails[0]["currency"]: "",
        ];

        //prepare item basket data
        $count = 1;
        foreach ($orderDetails as $singleArticle)
        {
            $shoppingCart['basket']['basketItems'][] = array(
                'position'				=> $count,
                'basketItemReferenceId' => $count,
                'articleId'				=> !empty($singleArticle['articleordernumber']) ? $singleArticle['articleordernumber'] : $singleArticle['id'],
                'unit'					=> $singleArticle['unit'],
                'quantity'				=> $singleArticle['quantity'],
                'vat'					=> $singleArticle['tax_rate'],
                'amountGross'			=> bcmul($singleArticle['price'], 100, 0),
                'amountNet'				=> bcmul(bcmul((bcdiv($singleArticle['price'],(bcadd(100,$singleArticle['tax_rate'],6)),6)),100,6), 100, 0),
                'amountVat'				=> bcmul(bcsub($singleArticle['price'],bcmul(bcdiv($singleArticle['price'],(bcadd($singleArticle['tax_rate'],100,6)),6),100,6),6),100,0),
                'amountPerUnit'			=> bcmul(($singleArticle['price']), 100, 0),
                'type'					=> $amountGross >= 0 ? 'goods' : 'voucher',
                'title'					=> strlen($singleArticle['name']) > 255 ? substr($singleArticle['name'], 0, 250).'...' : $singleArticle['name'],

            );

            if($shoppingCart['basket']['basketItems'][$count]['type'] == "voucher") {
                $shoppingCart['basket']['basketItems'][$count]['articleId'] = "voucher";
            }

            $count ++;

        }

        if(array_key_exists("0",$orderDetails))
        {
            $shoppingCart['basket']['basketItems'][] = array(
                'position'				=> $count,
                'basketItemReferenceId' => $count,
                'articleId'				=> "SwShipping",
                'unit'					=> "stk",
                'quantity'				=> "1",
                'vat'					=> $orderDetails[0]['tax_rate'],
                'amountGross'			=> bcmul($orderDetails[0]['invoice_shipping'], 100, 0),
                'amountNet'				=> bcmul($orderDetails[0]['invoice_shipping_net'] , 100, 0),
                'amountVat'				=> bcmul(bcsub($singleArticle['invoice_shipping'],$singleArticle['invoice_shipping_net'],6),100,0),
                'amountPerUnit'			=> bcmul(($orderDetails[0]['invoice_shipping']), 100, 0),
                'type'					=> "shipment",
                'title'					=> "Shipping Costs"

            );
        }
        $shoppingCart['basket']['itemCount'] = $count;
        $basketReturn = $shoppingCart;
        return $basketReturn;
    }

    protected function getInvoiceidByTransactionId($transactionId){
        $sql = 'SELECT `s_order_documents`.`docID` FROM `s_order` 
                INNER JOIN `s_order_documents` ON `s_order`.`id` = `s_order_documents`.`orderID` 
                WHERE `s_order`.`transactionID` = ?
                ;';

        $parameters = [$transactionId];

        try{
            $invoiceId = Shopware()->Db()->fetchAll($sql,$parameters);
            return $invoiceId[0]['docID'];
        } catch (Exception $e) {
            Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('getInvoiceidByTransactionId (BE) | '.$e->getMessage());
            return false;
        }

    }

    /**
     * Updatetes paymentstatus of an order
     * @param $heidelUniqueIdPa
     * @return bool
     */
    protected function updateOrderPaymentStatus($heidelUniqueIdPa){
        $sql =
            "UPDATE `s_order` 
              SET `cleared`='12'
              WHERE `temporaryID`=?";

        try{
            $result = Shopware()->Db()->executeUpdate($sql,[$heidelUniqueIdPa]);
            return $result;
        } catch (Exception $e){
            Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('updateOrderPaymentStatus (BE) | '.$e->getMessage());
            return false;
        }

    }


}