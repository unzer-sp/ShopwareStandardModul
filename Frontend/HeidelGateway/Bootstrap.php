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
require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

class Shopware_Plugins_Frontend_HeidelGateway_Bootstrap extends Shopware_Components_Plugin_Bootstrap{
	private static $_moduleDesc = 'Heidelpay CD-Edition';
	var $moduleType 		= 'CD-Edition';
	static $requestUrl 		= '';
	static $live_url 		= 'https://heidelpay.hpcgw.net/ngw/post';
	static $test_url		= 'https://test-heidelpay.hpcgw.net/ngw/post';
	static $live_url_basket	= 'https://heidelpay.hpcgw.net/ngw/basket/';
	static $test_url_basket	= 'https://test-heidelpay.hpcgw.net/ngw/basket/';

	/**
	 * Method to return Versionnumber
	 * @return string version number
	 */
	public function getVersion(){
		return '19.02.20';
	}

	/**
	 * Method to return label
	 * @return string - label text
	 */
	public function getLabel(){
		return 'Heidelpay CD-Edition';
	}

	/**
	 * Method that return availability capabilities
	 * @return array
	 */
	public function getCapabilities(){
		return array(
				'install'=> true,
				'update' => true,
				'enable' => true,
				'delete' => true
		);
	}

	/**
	 * Method to return information about the module
	 * @return array - Module information
	 */
	public function getInfo(){
		$prefix 	= substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/'));
//		$hp_logo 	= base64_encode(file_get_contents(dirname(__FILE__) . '/img/heidelpay.png'));
		$hp_logo 	= dirname(__FILE__) . '/img/heidelpay.png';
		return array(
				'version' => $this->getVersion(),
				'autor' => 'heidelpay GmbH (SP)',
				'label' => $this->getLabel(),
				'source' => "Default",
				'description' =>
                    '<p style="font-size:12px">
                        <img src="' .$hp_logo. '"/><br/>
                        Die heidelpay GmbH kurz: heidelpay bietet als BaFin-zertifizierter Payment Service Provider 
                        alles was zum Online-Payment geh&ouml;rt.<br><br>
                        <a href="http://testshops.heidelpay.de/contactform/?campaign=shopware4.0&shop=shopware" target="_blank" style="font-size: 12px; color: #000; font-weight: bold;">&gt;&gt;&gt; Informationen anfordern &lt;&lt;&lt;</a><br/>
                    </p>
                    <br />
                    <p style="font-size:12px">
                        Das Leistungsspektrum des PCI DSS zertifizierten Unternehmens reicht von weltweiten e-Payment L&ouml;sungen, 
                        inklusive eines vollst&auml;ndigen Debitorenmanagement-, Risk- und Fraud- Systems bis hin zu einem breiten 
                        Angebot alternativer Bezahlverfahren - schnell, sicher, einfach und umfassend - alles aus einer Hand.
                     </p>
                     <br/>
                     <a href="http://www.heidelpay.com" style="font-size: 12px; color: #000; font-weight: bold;">www.heidelpay.com</a><br/><br/>
                     <p style="font-size: 12px; color: #f00; font-weight: bold;">
                        Für Fragen zu der Funktion des Plugins oder der Zahlungsabwicklung wenden Sie sich bitte an<br/> 
                        E-Mail: <a href="mailto:support@heidelpay.com">support@heidelpay.com</a><br/>Telefon: <a href="tel:+4962216471100">+49 (0) 6221 64 71 100</a>.<br/> 
                        Bitte notieren Sie sich Sie sich vorher die URL Ihres e-Shops sowie die Version Ihres Shopware-Systems und teilen Sie uns diese dann mit, 
                        als Beispiel:<br/>
                        <b>https://www.meinshop.de/</b><br/>
                        <b>Shopware 5.3.5</b><br/>
                        Testdaten entnehmen Sie bitte unserer <a href="https://dev.heidelpay.de/testumgebung/" style="color: #000; font-weight: bold;" target="_blank">Dokumentation</a>.
                     </p>',
				'license' => 'commercial',
				'copyright' => 'Copyright © '.date("Y").', heidelpay GmbH',
				'support' => 'support@heidelpay.com',
				'link' => 'http://www.heidelpay.com/'
		);
	}

	/**
	 * Plugin install method
	 * @return bool
	 */
	public function install(){
        $msg = 'Installationsfehler: <br />';
		if(!$this->assertMinimumVersion("4.3.7")){
			throw new Enlight_Exception("This Plugin needs min shopware 4.3.7");
		}

        /* *************** Neuer Code ********************* */
        $swVersion = Shopware()->Config()->version;

        /* Major check Version */
        if(
            (version_compare($swVersion,"4.3.7",">"))
            && (version_compare($swVersion,"5.2.0","<"))
        ){
            if(!$this->assertRequiredPluginsPresent(array('Payment'))){
                $msg .= "This plugin requires the plugin payment<br />";
                $this->uninstall();
                throw new Enlight_Exception("This plugin requires the shopware plugin payment");
            }
        };
        /* *************** Ende neuer Code ********************* */
        /* Major check Version */
//		if ($this->assertVersionGreaterThen('5.1.6')) {
//			$swVersion = Shopware()->Config()->version;
//
//		} else {
//			$swVersion = Shopware()->Config()->version;
//			if(!$this->assertRequiredPluginsPresent(array('Payment'))){
//				$msg .= "This plugin requires the plugin payment<br />";
//				$this->uninstall();
//				throw new Enlight_Exception("This plugin requires the shopware plugin payment");
//			}
//		}

		if($this->assertRequiredPluginsPresent(array('HeidelActions'))){
			throw new Enlight_Exception("Please delete Heidelpay Backend Plugin (HeidelActions) from your Server");
		}

		try{
			$this->createEvents();
			$msg .= '* register event handler<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->createPayments();
			$msg .= '* install payments<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->createTable();
			$msg .= '* create payment table<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->createRGTable();
			$this->alterRGTable();
			$this->alterRGTable171012();
			$msg .= '* create / alter reg table<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->createForm();
			$msg .= '* create form<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->addSnippets();
			$msg .= '* add snippets<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			// PrepaymentMail
			$this->installPrepaymentMail();
    		// DirectDebitMail
			$this->installDirectDebitMail();
			// Santander Invoice Mail
            $this->installInvoiceSanMail();
            // Payolution Invoice Mail
            $this->installInvoiceIvpdMail();
			$msg .= '* install mail templates<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->createTransactionsTable();
			$msg .= '* create transaction table<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}
		try{
			$this->createLoggingUser();
			$msg .= '* create backend user for logs<br />';
		}catch(Exception $e){
			$this->logError($msg, $e);
		}

		// overwrite $msg if install was successful
		$msg = 'Installation erfolgreich: '.$this->getVersion();
		$this->Logging($msg);

		return array('success' => true, 'invalidateCache' => array('frontend'));
	}

    /**
     * Plugin update method
     * @param string $oldVersion - old module version
     * @return bool
     */
    public function update($oldVersion){
        $msg = 'Update Fehler. Alte Modulversion: '.$oldVersion.'<br />';
        $form = $this->Form();
        switch($oldVersion){
            case '14.05.08':
            case '14.05.15':
            case '14.05.22':
            case '14.06.03':
                try{
                    $msg .= '* update 14.06.03<br />';
                    $form->setElement('select', 'HGW_MOBILE_CSS', array(
                        'label' => 'activate mobile CSS',
                        'value' => 0,
                        'store' => array(array(0, 'No'), array(1, 'Yes')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.06.10':
            case '14.06.18':
                try{
                    $msg .= '* update 14.06.18<br />';
                    $this->createEvents();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.06.27':
            case '14.07.07':
            case '14.07.08':
            case '14.07.09':
                try{
                    $msg .= '* update 14.07.09<br />';
                    $this->installDirectDebitMail();
                    $this->addSnippets();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.08.11':
                try{
                    $msg .= '* update 14.08.11<br />';
                    $this->installPrepaymentMail();
                    $this->installBarPayMail();
                    $this->installDirectDebitMail();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.08.13':
                try{
                    $msg .= '* update 14.08.13<br />';
                    $this->createPayments();
                    $form->setElement('text', 'HGW_PF_CHANNEL', array(
                        'label'=>'PostFinance Channel',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.08.15':
                try{
                    $msg .= '* update 14.08.15<br />';
                    $this->getInfo();
                    $this->createLoggingUser();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.08.22':
                try{
                    $msg .= '* update 14.08.22<br />';
                    $form->setElement('select', 'HGW_PP_MAIL', array(
                        'label' => 'Send pay data via mail',
                        'value' => 0,
                        'store' => array(array(0, 'No'), array(1, 'Yes')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Send payment information in additional email. Available for BarPay, Prepayment and Direct Debit.'
                    ));
                    $form->setElement('select', 'HGW_IBAN', array(
                        'label' => 'Show IBAN?',
                        'value' => 2,
                        'store' => array(array(0, 'No'), array(1, 'Yes'), array(2, 'Both')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Show IBAN or Account / Bank no.? Valid for Direct Debit and Sofort Banking.'
                    ));
                    $form->setElement('select', 'HGW_TRANSACTION_MODE', array(
                        'label' => 'Sandbox mode',
                        'value' => 1,
                        'store' => array(array(0, 'No'), array(1, 'Yes')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'If enabled, all transaction will be send to Heidelpay Sandbox. Otherwise all transactions are real transactions and each transaction is charged.'
                    ));
                    $form->setElement('select', 'HGW_VA_BOOKING_MODE', array(
                        'label' => 'PayPal booking mode',
                        'value' => 1,
                        'store' => array(array(1, 'debit'), array(2, 'reservation'), array(3, 'registration with debit'), array(4, 'registration with reservation')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => '<b>Please note: PayPal Account needs to be configured for recurring transactions, if you want use the registration feature.<b>'
                    ));
                    $this->installPrepaymentMail();
                    $this->installBarPayMail();
                    $this->installDirectDebitMail();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.08.25':
                // moved getFormUrl and preparePostData to controller
            case '14.09.02':
            case '14.09.17':
            case '14.10.15':
                try{
                    $msg .= '* update 14.10.15<br />';
                    $this->addSnippets();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '14.11.19':
            case '14.12.10':
            case '14.12.22':
                try{
                    $msg .= '* update 14.12.22<br />';
                    $this->createEvents();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.01.30':
                try{
                    $msg .= '* update 15.01.30<br />';
                    if($this->assertRequiredPluginsPresent(array('HeidelActions'))){
                        throw new Enlight_Exception("Please uninstall Heidelpay Backend Plugin (HeidelActions)");
                    }
                    $this->addSnippets();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.02.12':
            case '15.02.23':
            case '15.03.04':
            case '15.03.06':
            case '15.03.12':
                try{
                    $msg .= '* update 15.03.12<br />';
                    $this->createPayments();
                    $form->setElement('text', 'HGW_GIR_CHANNEL', array(
                        'label'=>'Giropay Channel',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.03.17':
                try{
                    $msg .= '* update 15.03.17<br />';
                    $this->createPayments();
                    $form->setElement('text', 'HGW_IDE_CHANNEL', array(
                        'label'=>'Ideal Channel',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.03.19':
            case '15.04.09':
                try{
                    $msg .= '* update 15.04.09<br />';
                    $this->createTransactionsTable();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.04.20':
            case '15.04.22':
                try{
                    $msg .= '* update 15.04.22<br />';
                    $this->addSnippets();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.04.28':
            case '15.05.04':
            case '15.05.11':
            case '15.05.13':
            case '15.05.18':
                try{
                    $msg .= '* update 15.05.18<br />';
                    $this->createPayments();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.05.27':
            case '15.05.29':
            case '15.06.03':
            case '15.06.10':
            case '15.06.17':
            case '15.06.30':
            case '15.07.07':
            case '15.08.28':
            case '15.09.02':
                try{
                    $msg .= '* update 15.09.02<br />';
                    $this->createPayments();
                    $form->setElement('text', 'HGW_MPA_CHANNEL', array(
                        'label'=>'MasterPass Channel',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
                    $form->setElement('select', 'HGW_MPA_BOOKING_MODE', array(
                        'label' => 'MasterPass booking mode',
                        'value' => 1,
                        'store' => array(array(1, 'debit'), array(2, 'reservation')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP));
                    $this->alterRGTable();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.09.07':
            case '15.09.08':
            case '15.09.10':
                try{
                    $msg .= '* update 15.09.10<br />';
                    $this->addPluginTranslation();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.09.14':
            case '15.09.15':
                try{
                    $msg .= '* update 15.09.15<br />';
                    $this->createPayments();
                    $this->addSnippets();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.09.18':
            case '15.09.21':
            case '15.09.22':
            case '15.09.25':
            case '15.10.07':
            case '15.10.08':
            case '15.10.14':
            case '15.10.16':
            case '15.10.19':
                try{
                    $msg .= '* update 15.10.19<br />';
                    $form->setElement('select', 'HGW_CHB_STATUS', array(
                        'label' => 'Chargeback State',
                        'value' => 35,
                        'store' => 'base.PaymentStatus',
                        'displayField' => 'description',
                        'valueField' => 'id',
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'This state is set if a direct debit is bounced or at a credit card chargeback'
                    ));
                    $this->addPluginTranslation();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.10.27':
            case '15.11.02':
            case '15.11.17':
                try{
                    $msg .= '* update 15.11.17<br />';
                    $this->createTable();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '15.11.30':
            case '15.12.02':
            case '16.02.22':
            case '16.03.10':
            case '16.03.22':
                try{
                    $msg .= '* update 16.03.22<br />';
                    $this->createEvents();
                    $this->addSnippets();
                    $this->alterRGTable();
                    $this->deactivateYapital();
                    $form->setElement('text', 'HGW_HPF_CC_CSS', array(
                        'label'=>'Path to hPF CSS for creditcard',
                        'value'=> '',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Please enter the absolute path to the CSS, starting with "http(s)://". This CSS applies to our creditcard form.'
                    ));
                    $form->setElement('text', 'HGW_HPF_DC_CSS', array(
                        'label'=>'Path to hPF CSS for debitcard',
                        'value'=> '',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Please enter the absolute path to the CSS, starting with "http(s)://". This CSS applies to our debitcard form.'
                    ));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '16.04.08':
                try{
                    $msg .= '* update 16.04.08<br />';
                    $this->addSnippets();
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '16.04.25':
                try{
                    $msg .= '* update 16.04.25<br />';
                    $this->addSnippets();
                    $this->createPayments();
                    $this->addPluginTranslation();
                    $this->alterRGTable();
                    $form->setElement('text', 'HGW_PAPG_CHANNEL', array(
                        'label'=>'Gesicherter Rechnungskauf',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP
                    ));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '16.06.06':
            case '16.06.16':
                try {
                    $msg .= '* Update 16.06.16';
                    $this->createPayments();
                    $form->setElement('text', 'HGW_P24_CHANNEL', array(
                        'label'=>'Przelewy24 Channel',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '16.07.01':
                try {
                    $msg .= '* Update 16.07.01';
                    $this->createPayments();
                    $form->setElement('select', 'HGW_DD_GUARANTEE_MODE', array(
                        'label' => 'Direct Debit guarantee mode',
                        'value' => 2,
                        'store' => array( array(1, 'Yes'), array(2, 'No')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Please consider, that a special contract is needed'
                    ));

                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '16.07.15':
                try {
                    $this->createPayments();
                    $msg .= '* Update 16.07.15';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '16.08.01':
            case '16.08.06':
            case '16.08.08':
                try {
                    $msg .= '* Update 16.08.08';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '16.08.30':
            case '16.09.14':
            case '16.09.21':
                try {
                    $msg .= '* Update 16.09.14';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '16.09.30':
            case '16.10.01':
                try{
                    $msg .= '* update 16.10.01<br />';
                    $this->createPayments();
                    $this->addSnippets();
                    $form->setElement('text', 'HGW_EPS_CHANNEL', array(
                        'label'=>'EPS Channel',
                        'value'=>'',
                        'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '16.10.11':
                try{
                    $msg .= '* update 16.10.11<br />';
                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            case '16.10.31':
                try{
                    $msg .= '* update 16.10.31 <br />';
                    $form = $this->update161028($form);

                }catch(Exception $e){
                    $this->logError($msg, $e);
                }
            // new method responseRegAction added and changed precedure after sending transaction to payment
            case '16.11.18':
                try {
                    $msg .= '* update 16.11.18 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '16.11.29':
                try {
                    $msg .= '* update 16.11.18 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '16.12.04':
                // Fix for reregistration in Emotiontemplate
                try {
                    $msg .= '* update 16.12.04 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '17.01.09':
                // only version number changed
                try {
                    $msg .= '* update 17.01.09 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '17.01.20':
            case '17.01.25':
            case '17.01.31':
            case '17.02.21':
            case '17.02.23':
                // issues in Emotion-template with redirection after registration
                // recreated checkoutflow for both templates
                // added Basket-API for Santander-Payment
                try {
                    $msg .= '* update 17.02.21 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.03.13':
            case '17.03.15':
            case '17.03.16':
            case '17.04.01':
            case '17.04.10':
                // changes for invoice payment - set payment-status after ACK-transaction to "review necessary"
                // changes for prepayment - to show accountinfo to customers and merchant
                // changes for prepayment - removed view of Short ID uppon the prepayment text in view "Orders" for customers
                // changes in heidelpays Basket-Api use
                // Fix for AboCommerce users
                // IV-payments will not longer marked as paid by push RESs, only RC will mark an order to paid
                // set HP-Basket-Api-calls for santander, invoice with gurantee (papg) and direct debit with insurance
                // did some code refactoring from if/elseif to switch/case
                // changed some responseparameter names
                // added 3 new switches for sending prepayment-, invoice- and directDebit-Emails or not
                // changed font-size of Invoice-PDF-Template
                try {
                    $form->setElement('select', 'HGW_IV_MAIL', array(
                        'label' => 'Zahlungsinformationen für Rechnung per Mail senden',
                        'value' => 0,
                        'store' => array(array(0, 'No'), array(1, 'Yes')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Zahlungsinformationen für Rechnung, gesicherte Rechnung und Santander in einer zusätzlicher Mail versenden.'
                    ));
                    $form->setElement('select', 'HGW_DD_MAIL', array(
                        'label' => 'Zahlungsinformationen für Lastschrift per Mail senden',
                        'value' => 0,
                        'store' => array(array(0, 'No'), array(1, 'Yes')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Zahlungsinformationen für Lastschrift in einer zusätzlicher Mail versenden.'
                    ));
                    $form->setElement('select', 'HGW_PP_MAIL', array(
                        'label' => 'Zahlungsinformationen für Vorkasse per Mail senden',
                        'value' => 0,
                        'store' => array(array(0, 'No'), array(1, 'Yes')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Zahlungsinformationen für Vorkasse in einer zusätzlicher Mail versenden.'
                    ));
                    $this->updateInvoiceTemplates();
                    $msg .= '* update 17.04.10 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '17.04.19':
            case '17.07.26':
                // fix for missing articleId for shippment-article for secured invoice-payment
                // fix for missing articleId for voucher for secured invoive-payment
                // fix in JS for parsing error while setting CSRF-Token in JS of hp_payment.tpl
                try {
                    $msg .= '* update 17.07.26 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.08.20':
                //Compatibility for Shopware 5.3
                try {
                    $msg .= '* update 17.08.20 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '17.09.18':
                // Compatibility for Shopware 5.3
                // changed name of Logging-User for Heidelpay-Plugin
                // Integration of Ratepay by easyCredit
                // Bugfix "Shopware()->Shop()"
                // used shopwareArticleNumber ($basketItem['ordernumber']) if there´s no EAN for BasketApi use
                try {
                    $this->updateLoggingUser();
                    $this->createPayments();
                    $this->addSnippets();
                    $this->createEvents();
                    $form->setElement('text', 'HGW_HPR_CHANNEL',
                        array(
                            'label'=>'EasyCredit Channel',
                            'value'=>'',
                            'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP
                        )
                    );
                    $msg .= '* update 17.09.18 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.09.19':
            case '17.09.25':
                // Compatibility for Shopware 4.3.6 - 5.3.3
                // Some changes in Js
                try {
                    $msg .= '* update 17.09.19 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '17.09.30':
                // Compatibility improvements for Shopware 4.3.6 - 5.3.3
                // Some changes in Js
                // Santander-Refactoring
                try {
                    $this->addSnippets();
                    $this->installInvoiceSanMail();
                    $msg .= '* update 17.09.30 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.10.10':
                // updatefix 17.09.19
                try {
                    $msg .= '* update 17.10.10 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.10.11':
                // hotfix for unneccassary payment calls for EasyCredit
                try {
                    $msg .= '* update 17.10.11 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.10.12':
            case '17.10.26':
            case '17.11.07':
            case '17.11.08':
            case '17.11.28':
            case '17.12.12':
            case '17.12.15':
                // resolves a problem while generating Santander-PDF-invoice
                // Introducing Paymentmethod "Payolution direct"
                // fixes Errors with SW 5.3.4 jQueryAsync-Functionality
                // adding phonenumber to each payment request if available
                try{
                    $this->addSnippets();
                    $this->createPayments();
                    $this->update171012();
                    $this->installInvoiceIvpdMail();
                    $this->installInvoiceSanMail();
                    $form->setElement('text', 'HGW_IVPD_CHANNEL',
                        array(
                            'label'=>'Payolution Direct Channel',
                            'value'=>'',
                            'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP
                        )
                    );

                    $msg .= '* update 17.12.15 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '17.12.19':
                // Hotfix for switching to other PaymentMethods
                // Fix in BackendHgw for fin-transactions by payolution and santander and IVPG
                try{
                    $msg .= '* update 17.12.15 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '18.01.10':
                // Hotfix for switching to other PaymentMethods on Account-Site
                try{
                    $msg .= '* update 18.01.10 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '18.02.12':
            case '18.02.15':
            case '18.03.01':
            case '18.03.06':
                // Changes for sending Basket-Api-Request for Santander and Payolution in BackendHgw
                // function convertOrder() converts a abborded order into a regular order from a pushmessage
                // Paypal direct redirect without entering e-mail-address in shop
                // prevents a payment request for Santander and Payolution in case of no birthdate is stored
                // fixes for payment method santander for Emotion templates
                // changes in Santander templates for both templatefiles
                // rebranding of heidelpay Company
                try{
                    $this->addSnippets();
                    $msg .= '* update 18.03.06 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '18.03.12':
            case '18.03.23':
            case '18.04.09':
            case '18.04.25':
            case '18.04.27':
            case '18.05.11':
          case '18.05.23':
            // added Checkbox for Payolution
            // refactoring request for EasyCredit in Responsive-Template of SW 5.1.6
            // refactoring EasyCredit max limit
            // some JS-changes for validation of checkboxes and paymentmethods for all Sw-versions and templates
            // change of addSnippets() to install DE and EN textsnippets
            // changes for Santander-variable CONFIG_OPTIN_TEXT from NGW
            // added Css-File to adjust buttons and inputs to Shopware's
            // fixed an issue with registration of direct debit registration for Shopware 5.4.x

            try{
                $this->addSnippets();
                $msg .= '* update 18.05.23 <br />';
            } catch (Exception $e) {
                $this->logError($msg, $e);
            }

          case '18.05.24':
                // fixed bug in valPayment.js which could cause an error while clicking on AGB-link
                try{
                    $msg .= '* update 18.05.24 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '18.06.05':
                // set minimum / maximum amount for easyCredit
                try{
                    $form->setElement('text', 'HGW_EASYMINAMOUNT', array(
                        'label' => 'Minimum amount for easyCredit',
                        'value' => 200,
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'change minimum amount for easyCredit only after consultation with heidelpay'
                    ));
                    $form->setElement('text', 'HGW_EASYMAXAMOUNT', array(
                        'label' => 'Maximum amount for easyCredit',
                        'value' => 3000,
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'change maximum amount for easyCredit only after consultation with heidelpay'
                    ));
                    $this->addSnippets();
                    $msg .= '* update 18.06.05 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }
            case '18.07.11':
                // fixes for Emotion template for Santander invoice, Payolution invoice and direct debit
                // fixes a bug in direct debit with registration
                // changed query for birthdates for all payment methods so that there are no preallocated values
                // fixed an issue for saving regdata for Santander and Payolution
                try{
                    $this->addSnippets();
                    $msg .= '* update 18.07.11 <br />';

                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '18.08.08':
                // fixed an issue for saving regdata for Santander and Payolution
                // refactored easyCredit events
                // tested for SW 5.4.6
                try{
                    $this->addSnippets();
                    $msg .= '* update 18.08.08 <br />';
                } catch (Exception $e) {
                    $this->logError($msg, $e);
                }

            case '18.10.10':
                // integration of Santander HP
                // tested for SW 5.5.1
                try{
                    $this->addSnippets();
                    $this->createPayments();
                    $form->setElement('text', 'HGW_HPS_CHANNEL',
                        array(
                            'label'=>'Ratenkauf von Santander Channel',
                            'value'=>'',
                            'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP
                        )
                    );
                    $msg .= '* update 18.10.10<br />';
                } catch (Exception $e) {
                    $this->logError($msg,$e);
                }
            case '18.11.19':
                try{
                    // Integration of Invioce B2B
                    $this->addSnippets();
                    $this->createPayments();
                    $form->setElement('text', 'HGW_IVB2B_CHANNEL',
                        array(
                            'label'=>'gesicherter B2B-Rechnungskauf Channel',
                            'value'=>'',
                            'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP
                        )
                    );
                    $msg .= '* update 18.11.19<br />';
                } catch (Exception $e) {
                    $this->logError($msg,$e);
                }
            case '19.01.14':
                try{
                    // Integration of Invoice B2B factoring
                    // Integration of Invoice B2C factoring
                    // after a finalize orders will be marked as paid for all paymentmethods (IV and HP)
                    // fixed bug for easyCredit to show interests on checkout/finish
                    // tested for Sw 5.1.6 - 5.5.4
                    $this->addSnippets();
                    $form->setElement('select', 'HGW_FACTORING_MODE', array(
                        'label' => 'heidelpay Factoring Modus aktiv',
                        'value' => 2,
                        'store' => array( array(1, 'Yes'), array(2, 'No')),
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                        'description' => 'Bitte beachten Sie, dass zur Nutzung ein spezieller heidelpay-Vertrag nötig ist'
                    ));
                    $msg .= '* update 19.01.14<br />';
                } catch (Exception $e) {
                    $this->logError($msg,$e);
                }
            case '19.02.20':
                try{
                    // refactoring ob birthdate inputs for better testing
                    // Fix for creating Basket while showing prices without tax in frontend for IV B2B
                    // tested for Sw 5.1.6 - 5.5.7

                    $msg .= '* update 19.02.20<br />';
                } catch (Exception $e) {
                    $this->logError($msg,$e);
                }

                // overwrite $msg if update was successful
                $msg = 'Update auf Version '.$this->getVersion().' erfolgreich.';
        }

//        $form->save();
        $this->Logging($msg);
        return array(
            'success' => true,
            'message' => $msg,
            'invalidateCache' => array('frontend'),
        );
    }

	protected function renamePayments()
    {
        // getting paymentmeanname for Frontend
        $sql = "SELECT * FROM `s_core_paymentmeans` WHERE `name` = 'hgw_sue';";
        $hgwSufuData = Shopware()->Db()->fetchRow($sql);

        if($hgwSufuData['description'] == "Heidelpay CD-Edition Sofort&uuml;berweisung")
        {
            // changing name of Sofü to "Sofort"
            $sql = "UPDATE s_core_paymentmeans 
              SET description = 'Heidelpay CD-Edition Sofort' 
              WHERE description = 'Heidelpay CD-Edition Sofort&uuml;berweisung'";
            try{
                Shopware()->Db()->query($sql);
            } catch (Exception $e)
            {
                $this->logError('renamePayments Failure SoFu | Message:', $e);
            }
        }
    }

	protected function update171012()
    {
        try {
            // checks weather the regdata-table can receive Santander transactions
            $alterRegTable = false;
            $sql = 'DESCRIBE `s_plugin_hgw_regdata`';
            $data = Shopware()->Db()->fetchAll($sql);

            foreach ($data as $spalte => $eigenschaften) {
                foreach ($eigenschaften as $NameEigenschaft => $wert) {
                    if(strpos($wert,'ivpd')) {

                    } else {
                        $alterRegTable = true;
                    }
                }
            }

            if ($alterRegTable) {
                $this->alterRGTable171012();
            }
            $alterRegTable = false;

            // inserting IVPD - Mail
            $sql = "
				INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`)
					SELECT '1', 'Hgw_IVPD_Content_Info', ?, ?
					FROM `s_core_documents_box`
					WHERE NOT EXISTS (SELECT `name` FROM `s_core_documents_box` WHERE name='Hgw_IVPD_Content_Info')
					LIMIT 1;
			";
            Shopware()->Db()->query($sql, array(
                '.payment_instruction, .payment_instruction td, .payment_instruction tr{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_note{ font-size: 10px; color: #333; } .payment_account{ margin: 5px 0 5px 5px; padding: 0; } .payment_account tr, .payment_account td{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_account td{ padding: 0 5px 0 0; }',
                '<br/><div>Bitte &uuml;berweisen Sie den Rechnungsbetrag mit Zahlungsziel innerhalb von 7 Tagen auf folgendes Konto:<table class="payment_account"><tr><td>Kontoinhaber:</td><td>{$instruction.holder}</td></tr><tr><td>IBAN:</td><td>{$instruction.iban}</td></tr><tr><td>BIC:</td><td>{$instruction.bic}</td></tr></table>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{$instruction.connectorAccountUsage}</strong></div>'
            ));
        } catch (Exception $e) {
            $this->logError('update171012() fehlgeschlagen bei Update 171014|', $e);
        }
    }

	/** Method to check which functions are necessary for this update
	 * and do uptdate
	 *
	 * @param Object Form $formObjekt
	 * @return Object Form
	 */
	protected function update161028($form){
		// checks if update have to be done
		$sql = "SELECT * FROM `s_core_plugins` WHERE `name` like 'HeidelGateway';";
		$dataPlugins = Shopware()->Db()->fetchRow($sql);
		// wenn altes Plugin installiert ist, dann installiere neues
		if ($dataPlugins['version'] <= '16.10.11') {
			// createPayments()
			try {
				$sql = 'SELECT id FROM `s_core_paymentmeans` WHERE name = "hgw_san";';
				$dataPaymentmeans = Shopware()->Db()->fetchRow($sql);

				if (empty($dataPaymentmeans)) {
					$this->createPayments();
				}
			} catch (Exception $e) {
				$this->logError('createPayments() fehlgeschlagen bei Update 161031|', $e);
			}

			// addSnippets()
			try {
				$this->addSnippets();
			} catch (Exception $e) {
				$this->logError('addSnippets() fehlgeschlagen bei Update 161031|', $e);
			}

			// addPluginTranslation()
			try {
				$sql = "select `id` from `s_core_config_elements` where name = 'HGW_SAN_CHANNEL'";
				$dataConfigElements = Shopware()->Db()->fetchRow($sql);

				if ($dataConfigElements['id']<= 0) {
					$this->addPluginTranslation();
				}
			} catch (Exception $e) {
				$this->logError('addPluginTranslation() fehlgeschlagen bei Update 161031|', $e);
			}

			// alterRGTable161027()
			try {
				// checks weather the regdata-table can receive Santander transactions
				$alterRegTable = false;
				$sql = 'DESCRIBE `s_plugin_hgw_regdata`';
				$data = Shopware()->Db()->fetchAll($sql);

				foreach ($data as $spalte => $eigenschaften) {
					foreach ($eigenschaften as $NameEigenschaft => $wert) {
						if(strpos($wert,'san')) {

						} else {
							$alterRegTable = true;
						}
					}
				}

				if ($alterRegTable) {
					$this->alterRGTable161027();
				}
				$alterRegTable = false;
			} catch (Exception $e) {
				$this->logError('alterRGTable161027() fehlgeschlagen bei Update 161031|', $e);
			}

			try {
				// checks wether in the "s_core_config_elements" or in the "s_core_config_element_translations"
				// is an entry for HGW_SAN_CHANNEL
				$sql = "select * from `s_core_config_elements` where name = 'HGW_SAN_CHANNEL'";
				$dataConfigElements = Shopware()->Db()->fetchRow($sql);
					
				if (empty($dataConfigElements)) {
					$form->setElement('text', 'HGW_SAN_CHANNEL', array(
							'label'=>'Santander Channel',
							'value'=>'',
							'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
				}
			} catch (Exception $e) {
				$this->logError('setElement fehlgeschlagen bei Update 161031|', $e);
			}
			return $form;
		} else {
			return $form;
		}
	}
	
	/**
	 * Method to update CSS data for Invoice-Templates for PDF
	 */
	protected function updateInvoiceTemplates() {
		$sql = 'UPDATE `s_core_documents_box`
				SET `style` = ".payment_instruction, .payment_instruction td, .payment_instruction tr{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_note{ font-size: 10px; color: #333; } .payment_account{ margin: 5px 0 5px 5px; padding: 0; } .payment_account tr, .payment_account td{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_account td{ padding: 0 5px 0 0; }"
				WHERE `name`= "Hgw_IV_Content_Info"';
		try {
			Shopware()->Db()->query($sql, array());
		} catch (Exception $e) {
			$this->Logging('updateInvoiceTemplates failed | ' . $e->getMessage());
		}
	}

	/**
	 * Method to create events or hooks for custom code
	 */
	protected function createEvents(){
		try{
			/*	Events	*/
			$this->subscribeEvent(
					'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentHgw',
					'onGetControllerPathFrontend'
					);

			$this->subscribeEvent(
					'Enlight_Controller_Action_PostDispatch',
					'onPostDispatch'
					);
            //event for HPR Paymentmethod in emotion-template to do HP.IN
            $this->subscribeEvent(
                'Enlight_Controller_Action_PostDispatch_Frontend_Account',
                'onPostDispatchFrontendCheckoutAccount'
            );

			$this->subscribeEvent(
					'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
					'onPostDispatchTemplate'
					);

			$this->subscribeEvent(
					'Enlight_Controller_Action_PostDispatch_Backend_Order',
					'loadHeidelBackend'
					);

			// Add path to backend-controller
			$this->subscribeEvent(
					'Enlight_Controller_Dispatcher_ControllerPath_Backend_BackendHgw',
					'onGetControllerPathBackend'
					);

			// Register your custom LESS files, so that they are processed into CSS and included in the module template (SW5)
			$this->subscribeEvent(
					'Theme_Compiler_Collect_Plugin_Less',
					'addLessFiles'
					);

            // Register your custom JS files, so that they are processed into JS and included in the module template (SW5)
//            $this->subscribeEvent(
//                'Theme_Compiler_Collect_Plugin_Javascript',
//                'addJsFiles'
//            );

			/*	Hooks	*/
			$this->subscribeEvent(
					'Shopware_Components_Document::assignValues::after',
					'onBeforeRenderDocument'
					);
		}catch(Exception $e){
			$this->Logging('createEvents | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create and save payments
	 */
	protected function createPayments(){
		try{
		    $dbErrors = '';
			$inst = $this->paymentMethod();
            $swVersion = Shopware()->Config()->version;
			/* get Shops and check if locale is en_GB */
			$sql = '
				SELECT `s_core_shops`.`id`, `s_core_locales`.`locale` FROM `s_core_shops`, `s_core_locales`
				WHERE `s_core_shops`.`locale_id` = `s_core_locales`.`id`
			';
			try {
				$shops = Shopware()->Db()->fetchAll($sql);
				$dbErrors = Shopware()->Db()->getErrorMessage();
			} catch (Exception $e) {
				if (empty($dbErrors)) {
					$this->Logging('createPayments get all shops| '.$e->getMessage());
					return;
				} else {
					$this->Logging('createPayments get all shops |'.$e->getMessage().' | DB-Errors: '.$dbErrors);
					return;
				}
			}

			foreach($shops as $shop){
				if($shop['locale'] == 'en_GB'){
					$shopId = $shop['id'];
					break;
				}
			}

			foreach ($inst as $key => $val){
				// search for old hgw-paymethods
				if (version_compare($swVersion,"5.2.0",">=")) {

					$sql = 'SELECT * FROM `s_core_paymentmeans` WHERE `name`="hgw_'.$val['name'].'";';
					try {
						$getOldPayments = Shopware()->Db()->fetchRow($sql);
						$dbErrors.= Shopware()->Db()->getErrorMessage();

					} catch (Exception $e) {
						if (empty($dbErrors)) {
							$this->Logging('createPayments Fetching old paymethods| '.$e->getMessage());
							return;
						} else {
							$this->Logging('createPayments Fetching old paymethods | '.$e->getMessage().' | DB-Errors: '.$dbErrors);
							return;
						}
					}
				} else {
					try {
						$getOldPayments = Shopware()->Payments()->fetchRow(array('name=?'=>"hgw_".$val['name']));
						$dbErrors .= Shopware()->Db()->getErrorMessage();
					} catch (Exception $e) {
						if (empty($dbErrors)) {
							$this->Logging('createPayments Fetching old paymethods| '.$e->getMessage());
							return;
						} else {
							$this->Logging('createPayments Fetching old paymethods | '.$e->getMessage().' | DB-Errors: '.$dbErrors);
							return;
						}
					}
				}

				if(empty($val['additionaldescription'])){ $val['additionaldescription'] = " "; }

				/* check for description translation */
				if(isset($val['trans_desc']) && $val['trans_desc'] != ''){
					$translations[$val['name']]['trans_desc'] = $val['trans_desc'];
				}
				if(isset($val['trans_addDesc']) && $val['trans_addDesc'] != ''){
					$translations[$val['name']]['trans_addDesc'] = $val['trans_addDesc'];
				}

				// if hgw-paymentmethod is in DB update otherwise enter a new entry to DB
				if(!empty($getOldPayments['id'])){
					if(isset($translations[$val['name']])){
						$translations[$val['name']]['payId'] = $getOldPayments['id'];
					}

					$newData	= array("pluginID" => (int)$this->getId(), "action" => 'PaymentHgw');
					$where		= array("id = ".(int)$getOldPayments['id']);


//					if ($this->assertVersionGreaterThen('5.2')) {
					if (version_compare($swVersion,"5.2.0",">=")) {
						try {
							$affRows = Shopware()->Db()->update('s_core_paymentmeans', $newData, $where);
							$dbErrors .= Shopware()->Db()->getErrorMessage();
						} catch (Exception $e) {
							if (empty($dbErrors)) {
								$this->Logging('createPayments updateing old paymethods| '.$e->getMessage());
								return;
							} else {
								$this->Logging('createPayments updateing old paymethods| '.$e->getMessage().' | DB-Errors: '.$dbErrors);
								return;
							}
						}
					} else {
						try {
							Shopware()->Payments()->update($newData, $where);
							$dbErrors .= Shopware()->Db()->getErrorMessage();
						} catch (Exception $e) {
							if (empty($dbErrors)) {
								$this->Logging('createPayments updateing old paymethods| '.$e->getMessage());
								return;
							} else {
								$this->Logging('createPayments updateing old paymethods| '.$e->getMessage().' | DB-Errors: '.$dbErrors);
								return;
							}
						}
					}
				} else {
					if (version_compare($swVersion,"5.2.0",">=")) {

						$bind = array(
								':name' 		=> "hgw_".$val['name'],
								':description' 	=> $val['description'],
								':action' 		=> 'PaymentHgw',
								':active' 		=> 0,
								':pluginID' 	=> $this->getId(),
								':position' 	=> "1".$key,
								':additionaldescription' => $val['additionaldescription']

						);
						$sql = 'INSERT INTO `s_core_paymentmeans` (name, description, action, active, pluginID, position, additionaldescription)
								VALUES (:name, :description, :action, :active, :pluginID, :position, :additionaldescription);';
						try {
							Shopware()->Db()->query($sql,$bind);
							$dbErrors .= Shopware()->Db()->getErrorMessage();
						} catch (Exception $e) {
							if (empty($dbErrors)) {
								$this->Logging('createPayments inserting new paymethods| '.$e->getMessage());
								return;
							} else {
								$this->Logging('createPayments inserting new paymethods| DB-Errors: '.$dbErrors.' | '.$e->getMessage());
								return;
							}
						}
					} else {
						/* SW-Version < 5.1.6 */
						$paymentRow = Shopware()->Payments()->createRow(array(
								'name' => "hgw_".$val['name'],
								'description' => $val['description'],
								'action' => 'PaymentHgw',
								'active' => 0,
								'pluginID' => $this->getId(),
								'position' => "1".$key,
								'additionaldescription' => $val['additionaldescription']
						))->save();
					}
				}
			}

			/* set translation */
			if(isset($shopId)){
				foreach($translations as $key => $translation){
					if(empty($translation['payId'])){
						$payment = Shopware()->Payments()->fetchRow(array('name=?'=>"hgw_".$key));
						$payId = $payment['id'];
					}else{
						$payId = $translation['payId'];
					}

					$translationObject = new Shopware_Components_Translation();
					$translationObject->write(
							$shopId, 'config_payment', $payId, array(
									'description' => $translation['trans_desc'],
									'additionalDescription' => $translation['trans_addDesc'],
							), true
							);
				}
			}
		}catch(Exception $e){
			$this->Logging('createPayments | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create database table for payment
	 */
	protected function createTable(){
		try{
			// set up billsafe table for report dispatch
			$sql = 'CREATE TABLE IF NOT EXISTS `s_plugin_hgw_billsafe`(
				`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`temporaryID` varchar(255) NOT NULL,
				`Request` blob NOT NULL,
				PRIMARY KEY (`ID`),
				KEY `temporaryID` (`temporaryID`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
			';
			Shopware()->Db()->exec($sql);

			// create db entry for additional invoice text (billsafe), if not already set
			$sql = "
				INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`)
					SELECT '1', 'Hgw_BS_Content_Info', ?, ?
					FROM `s_core_documents_box`
					WHERE NOT EXISTS (SELECT `name` FROM `s_core_documents_box` WHERE name='Hgw_BS_Content_Info')
					LIMIT 1;
			";
			Shopware()->Db()->query($sql, array(
					'.payment_instruction, .payment_instruction td, .payment_instruction tr{	margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; }.payment_note{ font-size: 10px; color: #333; }.payment_account, .payment_account td, .payment_account tr{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; }',
					'<br/><div>{$instruction.LEGALNOTE}</div><br/><div>{$instruction.NOTE}</div><br/><table class="payment_account"><tr> <td>Empf&auml;nger:</td><td>{$instruction.recipient}</td></tr><tr><td>Kontonr.:</td><td>{$instruction.accountNumber}</td></tr><tr> <td>BLZ:</td><td>{$instruction.bankCode}</td></tr><tr> <td>Bank:</td> <td>{$instruction.bankName}</td></tr><tr><td>IBAN:</td> <td>{$instruction.iban}</td></tr><tr><td>BIC:</td><td>{$instruction.bic}</td></tr><tr><td>Betrag:</td><td>{$instruction.amount|currency}</td></tr><tr> <td>Verwendungszweck 1:</td><td>{$instruction.reference}</td></tr><tr><td>Verwendungszweck 2:</td><td>{config name=host}</td></tr></table>'
			));

			// create db entry for additional invoice text, if not already set
			$sql = "
				INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`)
					SELECT '1', 'Hgw_IV_Content_Info', ?, ?
					FROM `s_core_documents_box`
					WHERE NOT EXISTS (SELECT `name` FROM `s_core_documents_box` WHERE name='Hgw_IV_Content_Info')
					LIMIT 1;
			";
			Shopware()->Db()->query($sql, array(
					'.payment_instruction, .payment_instruction td, .payment_instruction tr{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_note{ font-size: 10px; color: #333; } .payment_account{ margin: 5px 0 5px 5px; padding: 0; } .payment_account tr, .payment_account td{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_account td{ padding: 0 5px 0 0; }',
					'<br/><div>Bitte &uuml;berweisen Sie uns den Rechnungsbetrag auf folgendes Konto:<table class="payment_account"><tr><td>Kontoinhaber:</td><td>{$instruction.holder}</td></tr><tr><td>IBAN:</td><td>{$instruction.iban}</td></tr><tr><td>BIC:</td><td>{$instruction.bic}</td></tr></table>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{$instruction.shortId}</strong></div>'
			));

            // create db entry for additional Santander invoice text, if not already set
            $sql = "
				INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`)
					SELECT '1', 'Hgw_SAN_Content_Info', ?, ?
					FROM `s_core_documents_box`
					WHERE NOT EXISTS (SELECT `name` FROM `s_core_documents_box` WHERE name='Hgw_SAN_Content_Info')
					LIMIT 1;
			";
            Shopware()->Db()->query($sql, array(
                '.payment_instruction, .payment_instruction td, .payment_instruction tr{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_note{ font-size: 10px; color: #333; } .payment_account{ margin: 5px 0 5px 5px; padding: 0; } .payment_account tr, .payment_account td{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_account td{ padding: 0 5px 0 0; }',
                '<br/><div>Bitte &uuml;berweisen Sie den Rechnungsbetrag mit Zahlungsziel innerhalb von 30 Tagen auf folgendes Konto:<table class="payment_account"><tr><td>Kontoinhaber:</td><td>{$instruction.holder}</td></tr><tr><td>IBAN:</td><td>{$instruction.iban}</td></tr><tr><td>BIC:</td><td>{$instruction.bic}</td></tr></table>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{$instruction.connectorAccountUsage}</strong></div>'
            ));

            // create db entry for additional Santander invoice text, if not already set
            $sql = "
				INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`)
					SELECT '1', 'Hgw_IVPD_Content_Info', ?, ?
					FROM `s_core_documents_box`
					WHERE NOT EXISTS (SELECT `name` FROM `s_core_documents_box` WHERE name='Hgw_IVPD_Content_Info')
					LIMIT 1;
			";
            Shopware()->Db()->query($sql, array(
                '.payment_instruction, .payment_instruction td, .payment_instruction tr{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_note{ font-size: 10px; color: #333; } .payment_account{ margin: 5px 0 5px 5px; padding: 0; } .payment_account tr, .payment_account td{ margin: 0; padding: 0; border: 0; font-size:10px; font: inherit; vertical-align: baseline; } .payment_account td{ padding: 0 5px 0 0; }',
                '<br/><div>Bitte &uuml;berweisen Sie den Rechnungsbetrag mit Zahlungsziel innerhalb von 7 Tagen auf folgendes Konto:<table class="payment_account"><tr><td>Kontoinhaber:</td><td>{$instruction.holder}</td></tr><tr><td>IBAN:</td><td>{$instruction.iban}</td></tr><tr><td>BIC:</td><td>{$instruction.bic}</td></tr></table>Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{$instruction.connectorAccountUsage}</strong></div>'
            ));

        }catch(Exception $e){
			$this->Logging('createTable | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create database table for registration data
	 */
	protected function createRGTable(){
		try{
			$sql = "CREATE TABLE IF NOT EXISTS `s_plugin_hgw_regdata` (
				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`userID` bigint(20) UNSIGNED NOT NULL,
				`payType` enum('cc','dc','dd','va','mpa','papg','san','ivpd') NOT NULL,
				`uid` varchar(32) NOT NULL,
				`cardnr` varchar(25) NOT NULL,
				`expMonth` tinyint(2) UNSIGNED NOT NULL,
				`expYear` int(4) UNSIGNED NOT NULL,
				`brand` varchar(25) NOT NULL,
				`owner` varchar(100) NOT NULL,
				`kto` varchar(25) NOT NULL,
				`blz` varchar(25) NOT NULL,
				`chan` varchar(32) NOT NULL,
				`shippingHash` varchar(128) NOT NULL,
				`email` varchar(70) NOT NULL,
				`payment_data` blob NULL DEFAULT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
			CREATE UNIQUE INDEX userPm ON `s_plugin_hgw_regdata` (userID, payType);";

			return Shopware()->Db()->query($sql);
		}catch(Exception $e){
			$this->Logging('createRGTable | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to alter database table for registration data.
	 * Add column 'payment_data' - data type: blob
	 * and change column 'payType' - 'mpa' added
	 */
	protected function alterRGTable(){
		try{
			$sql = "
				ALTER TABLE `s_plugin_hgw_regdata`
				ADD COLUMN `payment_data` blob NULL DEFAULT NULL
				AFTER `email`
			";
			try{
				return Shopware()->Db()->query($sql);
			}catch(Exception $e){
				$this->Logging('alterRGTable | '.$e->getMessage());
				if($e->getPrevious()->errorInfo['1'] != '1060'){
					$this->Logging('alterRGTable | '.$e->getMessage());
				}
			}

		}catch(Exception $e){
			$this->Logging('alterRGTable | '.$e->getMessage());
			return;
		}
	}

	/** Method to change registrationtable hgw_regdata
	 * 	adds in field "payType" an enum-entry "san"
	 *
	 * @return
	 */
	protected function alterRGTable161027() {
		$sql = "
				ALTER TABLE `s_plugin_hgw_regdata`
				CHANGE `payType`
				`payType` ENUM( 'cc', 'dc', 'dd', 'va', 'mpa', 'papg', 'san' )
				CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
			";
		try{
			Shopware()->Db()->query($sql);
		}catch(Exception $e){
			$this->Logging('alterRGTable161027() | '.$e->getMessage());
		}
	}

	/** Method to change registrationtable hgw_regdata
	 * 	adds in field "payType" an enum-entry "ivpd"
	 *
	 * @return
	 */
	protected function alterRGTable171012() {
		$sql = "
				ALTER TABLE `s_plugin_hgw_regdata`
				CHANGE `payType`
				`payType` ENUM( 'cc', 'dc', 'dd', 'va', 'mpa', 'papg', 'san', 'ivpd' )
				CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
			";
		try{
			Shopware()->Db()->query($sql);
		}catch(Exception $e){
			$this->Logging('alterRGTable171012() | '.$e->getMessage());
		}
	}

	/**
	 * Method to create payment plugin config form (backend)
	 */
	protected function createForm(){
		try{
			$form = $this->Form();
			$form->setElement('text', 'HGW_SECURITY_SENDER', array('label'=>'Sender-ID', 'value'=>'', 'required' => true, 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_USER_LOGIN', array('label'=>'Login', 'value'=>'', 'required' => true, 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_USER_PW', array('label'=>'Password', 'value'=>'', 'required' => true, 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('select', 'HGW_TRANSACTION_MODE', array('label' => 'Sandbox mode', 'value' => 1, 'store' => array(array(0, 'No'), array(1, 'Yes')), 'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'If enabled, all transaction will be send to Heidelpay Sandbox. Otherwise all transactions are real transactions and each transaction is charged.'));
			$form->setElement('text', 'HGW_CC_CHANNEL', array('label'=> 'Credit Card Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_CC_ABO_CHANNEL', array('label'=> 'Credit Card Channel for subscriptions', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'Channel necessary for subscriptions (Abo Commerce Plug-In)'));
			$form->setElement('text', 'HGW_DC_CHANNEL', array('label'=>'Debit Card Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_DC_ABO_CHANNEL', array('label'=> 'Debit Card Channel for subscriptions', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'Channel necessary for subscriptions (Abo Commerce Plug-In)'));
			$form->setElement('text', 'HGW_DD_CHANNEL', array('label'=>'Direct Debit Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_PP_CHANNEL', array('label'=>'Prepayment Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_IV_CHANNEL', array('label'=>'Invoice Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('text', 'HGW_PAPG_CHANNEL', array('label'=>'Invoice with guarantee Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('text', 'HGW_IVB2B_CHANNEL', array('label'=>'Invoice for company customers', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_SAN_CHANNEL', array('label'=>'Santander Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('text', 'HGW_IVPD_CHANNEL', array('label'=>'Payolution branded Channel', 'value'=>'','scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('text', 'HGW_P24_CHANNEL', array('label'=>'Przelewy24 Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_SUE_CHANNEL', array('label'=>'Sofort Banking Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_GIR_CHANNEL', array('label'=>'Giropay Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_VA_CHANNEL', array('label'=>'PayPal Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_IDE_CHANNEL', array('label'=>'Ideal Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_EPS_CHANNEL', array('label'=>'EPS Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_BS_CHANNEL', array('label'=>'BillSafe Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_MK_CHANNEL', array('label'=>'MangirKart Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_PF_CHANNEL', array('label'=>'PostFinance Channel', 'value'=>'', 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('text', 'HGW_MPA_CHANNEL', array('label'=>'MasterPass Channel', 'value'=>'','scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('text', 'HGW_HPR_CHANNEL', array('label'=>'EasyCredit Channel', 'value'=>'','scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('text', 'HGW_HPS_CHANNEL', array('label'=>'Santander Ratenkauf Channel', 'value'=>'','scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
            $form->setElement('select', 'HGW_DD_GUARANTEE_MODE', array('label' => 'Gesicherte Lastschrift', 'value' => 1, 'store' => array(array(1, 'No'), array(2, 'Yes')), 'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'Please consider, that you need a special contract to use direct debit with guarantee.'));
            $form->setElement('select', 'HGW_FACTORING_MODE', array('label' => 'Factoring über heidelpay aktiv', 'value' => 1, 'store' => array(array(1, 'No'), array(2, 'Yes')), 'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'Please consider, that you need a special contract to use heidelpay factoring.'));

            $bookingModeDesc = 'Debit: The payment for the order happens right away<br />Reservation: The basket amout is reserved for a number of days and can be captured in a second step<br />Registration: Payment information is stored to reuse it for further orders';
			$form->setElement('select', 'HGW_CC_BOOKING_MODE', array(
					'label' => 'Credit Card booking mode',
					'value' => 1,
					'store' => array(array(1, 'debit'), array(2, 'reservation'), array(3, 'registration with debit'), array(4, 'registration with reservation')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => $bookingModeDesc
			));
			$form->setElement('select', 'HGW_DC_BOOKING_MODE', array(
					'label' => 'Debit Card booking mode',
					'value' => 1,
					'store' => array(array(1, 'debit'), array(2, 'reservation'), array(3, 'registration with debit'), array(4, 'registration with reservation')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => $bookingModeDesc
			));
			$form->setElement('select', 'HGW_DD_BOOKING_MODE', array(
					'label' => 'Direct Debit booking mode',
					'value' => 1,
					'store' => array(array(1, 'debit'), array(2, 'reservation'), array(3, 'registration with debit'), array(4, 'registration with reservation')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => $bookingModeDesc
			));
			$form->setElement('select', 'HGW_DD_GUARANTEE_MODE', array(
					'label' => 'Direct Debit guarantee mode',
					'value' => 2,
					'store' => array( array(1, 'Yes'), array(2, 'No')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Please consider, that a special contract is needed'

			));
            $form->setElement('select', 'HGW_FACTORING_MODE', array(
                'label' => 'heidelpay Factoring mode active',
                'value' => 2,
                'store' => array( array(1, 'Yes'), array(2, 'No')),
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Please consider, that a special heidelpay contract is needed'

            ));
			$form->setElement('select', 'HGW_VA_BOOKING_MODE', array(
					'label' => 'PayPal booking mode',
					'value' => 1,
					'store' => array(array(1, 'debit'), array(2, 'reservation'), array(3, 'registration with debit'), array(4, 'registration with reservation')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => '<b>Please note: PayPal Account needs to be configured for recurring transactions, if you want use the registration feature.<b>'
			));
			$form->setElement('select', 'HGW_MPA_BOOKING_MODE', array(
					'label' => 'MasterPass booking mode',
					'value' => 1,
					'store' => array(array(1, 'debit'), array(2, 'reservation')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => $bookingModeDesc
			));
			$form->setElement('select', 'HGW_CHB_STATUS', array(
					'label' => 'Chargeback State',
					'value' => 35,
					'store' => 'base.PaymentStatus',
					'displayField' => 'description',
					'valueField' => 'id',
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'This state is set if a direct debit is bounced or at a credit card chargeback'
			));
			$form->setElement('select', 'HGW_DEBUG', array(
					'label' => 'Debug Mode',
					'value' => 0,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
			));
			$form->setElement('select', 'HGW_MOBILE_CSS', array(
					'label' => 'activate mobile CSS',
					'value' => 1,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
			));
			$secret= strtoupper(sha1(mt_rand(10000, mt_getrandmax())));
			$form->setElement('text', 'HGW_SECRET', array('label'=>'Secret', 'value'=>''.$secret.'', 'required' => true, 'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP, 'description' => 'Secret to verify the server response. Change only if necessary'));
//			$form->setElement('text', 'HGW_ERRORMAIL', array('label'=>'Error E-Mail address', 'value'=>'','scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP));
			$form->setElement('select', 'HGW_DD_MAIL', array(
					'label' => 'Send pay data for direct debit via mail',
					'value' => 0,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Send payment information in additional email for Direct Debit.'
			));
			$form->setElement('select', 'HGW_IV_MAIL', array(
					'label' => 'Send pay data via mail for invoice',
					'value' => 0,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Send payment information in additional email for Invoice payment with or without guarantee.'
			));
			$form->setElement('select', 'HGW_PP_MAIL', array(
					'label' => 'Send pay data for prepayment via mail for prepayment',
					'value' => 0,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Send payment information in additional emailfor Prepayment.'
			));
			$form->setElement('select', 'HGW_INVOICE_DETAILS', array(
					'label' => 'Send invoice data',
					'value' => 0,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Send invoice data to Heidelpay. Heidelpay generates a PDF invoice for the customer. (Additional Heidelpay services needed)'
			));
			$form->setElement('select', 'HGW_IBAN', array(
					'label' => 'Show IBAN?',
					'value' => 2,
					'store' => array(array(0, 'No'), array(1, 'Yes'), array(2, 'Both')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Show IBAN or Account / Bank no.? Valid for Direct Debit and Sofort Banking.'
			));
			$form->setElement('select', 'HGW_SHIPPINGHASH', array(
					'label' => 'Recognition with different delivery address?',
					'value' => 0,
					'store' => array(array(0, 'No'), array(1, 'Yes')),
					'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Is the recognition disabled, the registered payment data will be discarded, if the customer changes the delivery address after the registration.'
			));
            $form->setElement('text', 'HGW_EASYMINAMOUNT', array(
                'label' => 'Minimum amount for easyCredit',
                'value' => 200,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'change maximum amount for easyCredit only after consultation with heidelpay'
            ));
            $form->setElement('text', 'HGW_EASYMAXAMOUNT', array(
                'label' => 'Maximum amount for easyCredit',
                'value' => 3000,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'change maximum amount for easyCredit only after consultation with heidelpay'
            ));
			$form->setElement('text', 'HGW_HPF_CC_CSS', array(
					'label'=>'Path to hPF CSS for creditcard',
					'value'=> '',
					'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Please enter the absolute path to the CSS, starting with "http(s)://". This CSS applies to our creditcard form.'
			));
			$form->setElement('text', 'HGW_HPF_DC_CSS', array(
					'label'=>'Path to hPF CSS for debitcard',
					'value'=> '',
					'scope'=>\Shopware\Models\Config\Element::SCOPE_SHOP,
					'description' => 'Please enter the absolute path to the CSS, starting with "http(s)://". This CSS applies to our debitcard form.'
			));

			/* set parent in backend menu */
			$repository = Shopware()->Models()->getRepository('Shopware\Models\Config\Form');
			$form->setParent($repository->findOneBy(array('name' => 'Payment')));

			$this->addPluginTranslation();
		}catch(Exception $e){
			$this->Logging('createForm | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to uninstall plugin
	 * @return bool
	 */
	public function uninstall(){
		try{
			$newData	= array("active" => 0);
			$where		= array("pluginID = ".(int)$this->getId());
            $swVersion  = Shopware()->Config()->version;
			// deactivate Plugin itself
			if (version_compare($swVersion,"5.2.0",">=")) {
				try {
					$where		= array("id = ".(int)$this->getId());

					$affRows = Shopware()->Db()->update('s_core_plugins', $newData, $where);
					$dbErrors = Shopware()->Db()->getErrorMessage();
				} catch (Exception $e) {
					if (empty($dbErrors)) {
						$this->Logging('uninstall deactivate plugin | '.$e->getMessage());
						return;
					} else {
						$this->Logging('uninstall deactivate plugin '.$e->getMessage().' | DB-Errors: '.$dbErrors);
						return;
					}
				}
					
			} else {
				Shopware()->Payments()->update($newData, $where);
			}

			// set all Heidelpay paymentmethods to inactive
            if (version_compare($swVersion,"5.2.0",">=")) {
				try {
					$where		= array("name LIKE 'hgw_%'");

					$affRows = Shopware()->Db()->update('s_core_paymentmeans', $newData, $where);
					$dbErrors = Shopware()->Db()->getErrorMessage();
				} catch (Exception $e) {
					if (empty($dbErrors)) {
						$this->Logging('uninstall deactivate plugin | '.$e->getMessage());
						return;
					} else {
						$this->Logging('uninstall deactivate plugin '.$e->getMessage().' | DB-Errors: '.$dbErrors);
						return;
					}
				}
					
			} else {
				Shopware()->Payments()->update($newData, $where);
			}

            $sql = 'DELETE FROM `s_core_documents_box` WHERE `name` LIKE ?';
			Shopware()->Db()->query($sql, array('Hgw_%'));
			$this->Logging('Deinstallation erfolgreich');

			return true;
		}catch(Exception $e){
			$this->Logging('uninstall failed | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Event for custom code
	 * @return path
	 */
	public static function onGetControllerPathFrontend(Enlight_Event_EventArgs $args){
		if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
			Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
		}else{
			Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/responsive/');
		}

		return dirname(__FILE__).'/Controllers/Frontend/PaymentHgw.php';
	}

	/**
	 * Hook for custom code before document is renderd
	 */
	public function onBeforeRenderDocument(Enlight_Hook_HookArgs $args){
		try{
			$document = $args->getSubject();
			$view = $document->_view;

			if($document->_order->payment['name'] == 'hgw_bs'){
				$orderData = $view->getTemplateVars('Order');
				$containers = $view->getTemplateVars('Containers');

				$rawFooter = $this->getInvoiceContentInfo($containers, $orderData, 'BS');
				$containers['Hgw_BS_Content_Info']['value'] = $rawFooter['value'];
				// is necessary to get the data in the invoice template
				$view->assign('Containers', $containers);

				$res = $this->getBillSafeRequestFromDB($document->_order->order['transactionID']);

				$paymentInstruction['LEGALNOTE']		= htmlentities($res['CRITERION_BILLSAFE_LEGALNOTE'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['NOTE'] 			= htmlentities($res['CRITERION_BILLSAFE_NOTE'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['recipient'] 		= htmlentities($res['CRITERION_BILLSAFE_RECIPIENT'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['accountNumber']	= htmlentities($res['CRITERION_BILLSAFE_ACCOUNTNUMBER'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['bankCode'] 		= htmlentities($res['CRITERION_BILLSAFE_BANKCODE'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['bankName'] 		= htmlentities($res['CRITERION_BILLSAFE_BANKNAME'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['reference'] 		= htmlentities($res['CRITERION_BILLSAFE_REFERENCE'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['amount'] 			= htmlentities($res['CRITERION_BILLSAFE_AMOUNT'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['currency']			= htmlentities($res['CRITERION_BILLSAFE_CURRENCY'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['iban']				= htmlentities($res['CRITERION_BILLSAFE_IBAN'], ENT_QUOTES, 'UTF-8');
				$paymentInstruction['bic']				= htmlentities($res['CRITERION_BILLSAFE_BIC'], ENT_QUOTES, 'UTF-8');

				$document->_template->addTemplateDir(dirname(__FILE__) . '/Views/');
				$document->_template->assign('instruction', (array)$paymentInstruction);

				$containerData = $view->getTemplateVars('Containers');
				$containerData['Content_Info'] = $containerData['Hgw_BS_Content_Info'];
				$containerData['Content_Info']['value'] = $document->_template->fetch('string:' . $containerData['Content_Info']['value']);
				$view->assign('Containers', $containerData);
			}elseif(
			    ($document->_order->payment['name'] == 'hgw_iv') ||
                ($document->_order->payment['name'] == 'hgw_papg') ||
                ($document->_order->payment['name'] == 'hgw_san') ||
                ($document->_order->payment['name'] == 'hgw_ivb2b') ||
                ($document->_order->payment['name'] == 'hgw_ivpd')
//                || ($document->_order->payment['name'] == 'hgw_pp')
				){
				    $orderData = $view->getTemplateVars('Order');
					$containers = $view->getTemplateVars('Containers');

                    if($document->_order->payment['name'] == 'hgw_san')
                    {
                        $rawFooter = $this->getInvoiceContentInfo($containers, $orderData, 'SAN');
                        $containers['Hgw_SAN_Content_Info']['value'] = $rawFooter['value'];
                    } elseif (
                        $document->_order->payment['name'] == 'hgw_ivpd' ||
                        $document->_order->payment['name'] == 'hgw_ivb2b'
                    ){
                        $rawFooter = $this->getInvoiceContentInfo($containers, $orderData, 'IVPD');
                        $containers['Hgw_IVPD_Content_Info']['value'] = $rawFooter['value'];
                    } else {
                        $rawFooter = $this->getInvoiceContentInfo($containers, $orderData, 'IV');
                        $containers['Hgw_IV_Content_Info']['value'] = $rawFooter['value'];
                    }
					// is necessary to get the data into the invoice template
					$view->assign('Containers', $containers);

					$trans = $this->getTransactionByTransType($document->_order->order['transactionID'], 'PA');
					$transData = json_decode($trans['jsonresponse'], true);

					$paymentInstruction['amount'] 			= htmlentities($transData['CLEARING_AMOUNT'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['currency'] 		= htmlentities($transData['CLEARING_CURRENCY'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['country'] 			= htmlentities($transData['CONNECTOR_ACCOUNT_COUNTRY'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['holder'] 			= htmlentities($transData['CONNECTOR_ACCOUNT_HOLDER'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['accountNumber']	= htmlentities($transData['CONNECTOR_ACCOUNT_NUMBER'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['bankCode'] 		= htmlentities($transData['CONNECTOR_ACCOUNT_BANK'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['iban'] 			= htmlentities($transData['CONNECTOR_ACCOUNT_IBAN'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['bic'] 				= htmlentities($transData['CONNECTOR_ACCOUNT_BIC'], ENT_QUOTES, 'UTF-8');
					$paymentInstruction['shortId'] 			= htmlentities($transData['IDENTIFICATION_SHORTID'], ENT_QUOTES, 'UTF-8');
                    $paymentInstruction['connectorAccountUsage'] = htmlentities($transData['CONNECTOR_ACCOUNT_USAGE'], ENT_QUOTES, 'UTF-8');

					$document->_template->addTemplateDir(dirname(__FILE__) . '/Views/');
					$document->_template->assign('instruction', (array) $paymentInstruction);

					$containerData = $view->getTemplateVars('Containers');

                    if($document->_order->payment['name'] == 'hgw_san')
                    {
                        $containerData['Content_Info'] = $containerData['Hgw_SAN_Content_Info'];
                        $containerData['Content_Info']['value'] = $document->_template->fetch('string:' . $containerData['Content_Info']['value']);
                        $view->assign('Containers', $containerData);
                    } elseif(
                        ($document->_order->payment['name'] == 'hgw_ivpd') ||
                        ($document->_order->payment['name'] == 'hgw_ivb2b')
                    ) {
                        $containerData['Content_Info'] = $containerData['Hgw_IVPD_Content_Info'];
                        $containerData['Content_Info']['value'] = $document->_template->fetch('string:' . $containerData['Content_Info']['value']);
                        $view->assign('Containers', $containerData);
                    } else {
                        $containerData['Content_Info'] = $containerData['Hgw_IV_Content_Info'];
                        $containerData['Content_Info']['value'] = $document->_template->fetch('string:' . $containerData['Content_Info']['value']);
                        $view->assign('Containers', $containerData);
                    }
			}
		}catch(Exception $e){
			$this->Logging('onBeforeRenderDocument | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Event for custom code
	 */
	public function onPostDispatch(Enlight_Event_EventArgs $args){

        $request = $args->getSubject()->Request();
		$response = $args->getSubject()->Response();
		$config = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config();
		$view = $args->getSubject()->View();
		$action = $request->getActionName();
		if(!$view->hasTemplate()){ return; }
		// SSL Problem?! http://forum.shopware.com/allgemein-f25/nach-update-auf-4-3-1-resource-shop-not-found-klarna-t22933.html
        if($request->getModuleName() == 'frontend'){
            $realpath 		= realpath(dirname(__FILE__));
			$pluginPath 	= substr($realpath,strpos($realpath, '/engine'));
			$basepath		= Shopware()->System()->sCONFIG['sBASEPATH'];
			$shopPath		= substr($basepath,strpos($basepath, '/'));

            if (Shopware()->Container()->initialized('Shop')) {
                if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                    $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
                }else{
                    $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
                }
            }

            // Setting Smarty-variables for easyCredit for emotion-template (not neccessary for responsive template in this function)
            if (Shopware()->Shop()->getTemplate()->getVersion() < 3)
            {

                $basket	= Shopware()->Modules()->Basket()->sGetBasket();
                $basketAmount = str_replace(',', '.', $basket['AmountNumeric']);
                $shipping	= Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
                $shippingAmount = $shipping['value'];

                if (
                    $basketAmount+$shippingAmount >= $config->HGW_EASYMINAMOUNT &&
                    $basketAmount+$shippingAmount <= $config->HGW_EASYMAXAMOUNT
                ) {
                    $view->activeEasy = "TRUE";
                    $view->easyAmount = $basketAmount+$shippingAmount;
                    $view->HGW_EASYMINAMOUNT = $config->HGW_EASYMINAMOUNT;
                    $view->HGW_EASYMAXAMOUNT = $config->HGW_EASYMAXAMOUNT;
                } else {
                    $view->activeEasy = "FALSE";
                    $view->easyAmount = $basketAmount+$shippingAmount;
                    $view->HGW_EASYMINAMOUNT = $config->HGW_EASYMINAMOUNT;
                    $view->HGW_EASYMAXAMOUNT = $config->HGW_EASYMAXAMOUNT;
                }
            }

			if(($request->getControllerName() == 'account'
					&& ($action == 'payment' || $action == 'savePayment'
							|| $action == 'saveBilling' || $action == 'orders' || $action == 'stornoOrder'))
					|| ($request->getControllerName() == 'checkout'
							&& ($action == 'confirm' || $action == 'savePayment' || $action == 'saveBilling' || $action == 'shippingPayment'))
					|| ($request->getControllerName() == 'register'
							&& ($action == 'confirm' || $action == 'savePayment' || $action == 'saveBilling'))
					|| $action == 'cart'
					){
						// Booking Mode: 3 = Registierung mit Sofortbuchung | 4 = Registierung mit Reservierung
						$view->heidel_bm_cc = false;
						$view->heidel_bm_dc = false;
						$view->heidel_bm_dd = false;
						$view->heidel_bm_va = false;
						if(($config->HGW_CC_BOOKING_MODE == '3') || ($config->HGW_CC_BOOKING_MODE == '4')){ $view->heidel_bm_cc = true; }
						if(($config->HGW_DC_BOOKING_MODE == '3') || ($config->HGW_DC_BOOKING_MODE == '4')){ $view->heidel_bm_dc = true; }
						if(($config->HGW_DD_BOOKING_MODE == '3') || ($config->HGW_DD_BOOKING_MODE == '4')){ $view->heidel_bm_dd = true; }
						if(($config->HGW_VA_BOOKING_MODE == '3') || ($config->HGW_VA_BOOKING_MODE == '4')){ $view->heidel_bm_va = true; }

						setlocale(LC_TIME, Shopware()->Locale()->getLanguage(), Shopware()->Shop()->getLocale()->getLocale());
						
						$view->heidel_iban	= $config->HGW_IBAN;
						$view->action 		= $action;
						$view->lang			= Shopware()->Locale()->getLanguage();
						$view->swVersion	= Shopware()->Config()->Version;

						if(Shopware()->Config()->Version >= 5.5){
                            $view->extendsTemplate('register/hp_payment55.tpl');
                        }elseif(
                            (Shopware()->Config()->Version < 5.5) &&
                            (Shopware()->Config()->Version >= 5.3)
                        ){
                            $view->extendsTemplate('register/hp_payment53.tpl');
                        } else{
                            $view->extendsTemplate('register/hp_payment.tpl');
                        }

                        $file = realpath(dirname(__FILE__)).'/Controllers/Frontend/PaymentHgw.php';
						if(file_exists($file)){
							require_once($file);
							$basket	= Shopware()->Modules()->Basket()->sGetBasket();
							$amount	= Shopware()->Modules()->Basket()->sGetAmount();
							$amount	= $amount['totalAmount'];
							if(!empty($basket)){
								$tempID = Shopware_Controllers_Frontend_PaymentHgw::createPaymentUniqueId();
							}else{
								$tempID = Shopware()->SessionID();
							}

							if(
									(($request->getControllerName() == 'account') && ($action == 'payment')) ||
									(($request->getControllerName() == 'checkout') && (($action == 'confirm') || ($action == 'shippingPayment')))
								){

										$avPayments = Shopware()->Modules()->Admin()->sGetPaymentMeans();

										$user = Shopware()->Modules()->Admin()->sGetUserData();

										foreach($avPayments as $key => $avPayment){
											$prefix = 'hgw_';
											$pos = strpos($avPayment['name'], $prefix);
											if(is_int($pos)){
											    $pm = substr($avPayment['name'],$pos+strlen($prefix));
												if($pm == 'pay'){ $pm = va; }
												$bookingMode = $config->{'HGW_'.strtoupper($pm).'_BOOKING_MODE'};
												$data = $this->getRegData($user['additional']['user']['id'], $pm);
												$last = mktime(23,59,00,$data['expMonth']+1,0,$data['expYear']);	//timestamp: last day of registration month

                                               if(!empty($user)){
                                                   $shippingHash = $this->createShippingHash($user , $pm);
                                               } else {
//                                                   $user['shippingaddress']['firstname'] = "Payment";
//                                                   $user['shippingaddress']['lastname'] = "Heidelpay";
//                                                   $user['shippingaddress']['street'] = "Heidelstrasse 18";
//                                                   $user['shippingaddress']['zipcode']= "69115";
//                                                   $user['shippingaddress']['countryID']= "2";
                                                   return false;
                                               }

												if(!empty($data)){
													$regData[$pm] = $data;
													$chanName = 'HGW_'.strtoupper($pm).'_CHANNEL';
													$channel = $this->Config()->$chanName;

													if($data['chan'] != $channel){
														$this->removeReg($data['id']);
														// unset nur bei ausgewählter Zahlart
														unset($regData[$pm]);
													}
												}

                                                if($pm == 'san'){
                                                    $regData = $this->getRegData($user['additional']['user']['id'], $pm);
                                                    $getFormUrl = Shopware_Controllers_Frontend_PaymentHgw::getFormUrl($pm, $bookingMode, $user['additional']['user']['id'], $tempID, Null, NULL, NULL, true);

                                                    setlocale(LC_TIME, Shopware()->Locale()->getLanguage(), Shopware()->Shop()->getLocale()->getLocale());
                                                    if(!empty($regData)){
                                                        $dobSan = json_decode($regData['payment_data'], true);
                                                    }

                                                    if((isset($dobSan)) && ($dobSan['NAME_BIRTHDATE'] != '')){
                                                        $ppd_crit['NAME.BIRTHDATE'] = $dobSan['NAME_BIRTHDATE'];
                                                        $view->salutation_san	= $dobSan['NAME_SALUTATION'];
                                                        $view->birthdate_san	= $dobSan['NAME_BIRTHDATE'];
                                                    }

                                                    $recoveryLogoUrl ='https://www.santander.de/media/bilder/logos/logos_privatkunden/logo.gif';
                                                    $recoveryTextOptin = '<strong>Ja, ich bin damit einverstanden, dass meine Daten an die Santander Consumer Bank AG („Santander“)
                                                                                weitergegeben werden. Die Santander darf diese Daten gerne dazu nutzen, um mich über Produkte der
                                                                                Santander zu informieren. Natürlich kann ich meine Einwilligung jederzeit mit Wirkung für die Zukunft
                                                                                widerrufen. Ausführliche Informationen zu dieser Einwilligung sowie die Möglichkeit zum Widerruf
                                                                                finde ich </strong><a href="https://www.santander.de/applications/rechnungskauf/werbewiderspruch/" target="_blank">hier</a>.
                                                                            </strong>';
                                                    $recoveryTextPrivacyPolicy = '<strong>Ich willige in die Übermittlung meiner personenbezogenen Daten an die Santander Consumer Bank AG
                                                                                    gemäß den näheren Bestimmungen des beigefügten <a href="https://www.santander.de/applications/rechnungskauf/datenschutzbestimmungen" target="_blank">Einwilligungserklärungstextes</a> sowie an die darin
                                                                                    genannten Auskunfteien und in die Durchführung einer automatisierten Entscheidung ein.</strong>
                                                                                    </br>
                                                                                    Nähere Informationen finden Sie in den <a href="https://www.santander.de/applications/rechnungskauf/datenschutzbestimmungen" target="_blank">Datenschutzhinweisen</a>
                                                                                ';

                                                    $sanJson 			= json_decode($getFormUrl['CONFIG_OPTIN_TEXT'],true);

                                                    $view->optin_San_logoUrl 		= empty($sanJson['logolink'])       ? $recoveryLogoUrl          : $sanJson['logolink'];
                                                    $view->optin_San_adv		    = empty($sanJson['optin'])          ? $recoveryTextOptin        : $sanJson['optin'];
                                                    $view->optin_San_privpol		= empty($sanJson['privacy_policy']) ? $recoveryTextPrivacyPolicy: $sanJson['privacy_policy'];

                                                    $view->accountHolder_San	    = $getFormUrl['ACCOUNT_HOLDER'];
                                                    $view->checkOptin_San           = strtoupper($dobSan['CUSTOMER_OPTIN']);
                                                    $view->checkPrivacyPolicy_San   = strtoupper($dobSan['CUSTOMER_ACCEPT_PRIVACY_POLICY']);

                                                    $view->logoLink_San             = isset($sanJson['santander_iv_logo_link']) ? $sanJson['santander_iv_logo_link'] : $sanJson['santander_iv_img_link'];

                                                }


                                                if($pm == 'ivpd')
                                                {   // do initial request to get infotext
                                                    $regData = $this->getRegData($user['additional']['user']['id'], $pm);
                                                    $getFormUrl = Shopware_Controllers_Frontend_PaymentHgw::getFormUrl($pm, $bookingMode, $user['additional']['user']['id'], $tempID, Null, NULL, NULL, true);
                                                    // get registrated data from database
                                                    $registratedData = json_decode($regData['payment_data'],true);

                                                    if((isset($registratedData)) && ($registratedData != '')){
                                                        $view->salutation_ivpd	= $registratedData['NAME_SALUTATION'];
                                                        $view->birthdate_ivpd	= $registratedData['NAME_BIRTHDATE'];
                                                        $view->phonenumber_ivpd	= $registratedData['CONTACT_PHONE'];
                                                    }
                                                    // show telephone-entry if customer is from NL
                                                    if($user['additional']['countryShipping']['countryiso'] == "NL")
                                                    {
                                                        $view->showPhoneEntry = "TRUE";
                                                    }
                                                    $payolutionText = $getFormUrl['CONFIG_OPTIN_TEXT'];

                                                    $replaceText = '<p id="payolutiontext"><input type="checkbox" id="hgw_privpol_ivpd" name="cbIvpd" class="checkbox">  ';
                                                    $searchText = '<p id="payolutiontext">';
                                                    $textToShow = str_ireplace($searchText,$replaceText,$payolutionText);

                                                    $view->optinText        = $textToShow;
                                                    $view->accountHolder    = $getFormUrl['ACCOUNT_HOLDER'];
                                                }

                                                if($pm == "dd" && $config->HGW_DD_GUARANTEE_MODE == 1){
                                                    $regDataDD          = $this->getRegData($user['additional']['user']['id'], $pm);
                                                    $registratedDataDD  = json_decode($regDataDD['payment_data'],true);

                                                    if((isset($registratedDataDD)) && ($registratedDataDD != '')){
                                                        $view->salutation_dd	= $registratedDataDD['NAME_SALUTATION'];
                                                        $view->birthdate_dd	    = $registratedDataDD['NAME_BIRTHDATE'];
                                                    }

                                                    if((isset($regDataDD['kto'])) && ($regDataDD['kto'] != '')){
                                                        $view->iban_heidel_dd	= $regDataDD['kto'];
                                                    }


                                                }

                                                if(((isset($bookingMode)) && (($bookingMode == '3') || ($bookingMode == '4'))) && Shopware()->Modules()->Admin()->sCheckUser()){

													$getFormUrl = Shopware_Controllers_Frontend_PaymentHgw::getFormUrl($pm, $bookingMode, $user['additional']['user']['id'], $tempID, $regData[$pm]['uid'], NULL, NULL, true);
													$frame[$pm] = false;

													if(!empty($getFormUrl) && $getFormUrl['PROCESSING_RESULT'] == 'ACK'){
														$formUrl[$pm] = $getFormUrl['FRONTEND_REDIRECT_URL'];
														$cardBrands[$pm] = json_decode($getFormUrl['CONFIG_BRANDS'], true);
														$bankCountry[$pm] = json_decode($getFormUrl['CONFIG_BANKCOUNTRY'], true);

														if(isset($getFormUrl['FRONTEND_PAYMENT_FRAME_URL']) && ($getFormUrl['FRONTEND_PAYMENT_FRAME_URL'] != '')){
															$formUrl[$pm] = $getFormUrl['FRONTEND_PAYMENT_FRAME_URL'];
															$frame[$pm] = true;
														}
													}
													if(!empty($getFormUrl) && $getFormUrl['PROCESSING_RESULT'] == 'NOK'){
                                                        $_SESSION['Shopware']['HPError'] = Shopware_Controllers_Frontend_PaymentHgw::getHPErrorMsg($getFormUrl['PROCESSING_RETURN_CODE'], true);
														$this->Logging($pm.' | '.$getFormUrl['PROCESSING_RETURN_CODE'].' | '.$getFormUrl['PROCESSING_RETURN']);
													}
												}

												if((!empty($data)) && (($data['expMonth'] != '0') && ($data['expYear'] != '0') && ($last < time())) || (($data['shippingHash'] != $shippingHash) && $config->HGW_SHIPPINGHASH == 0) || (($bookingMode == 1) || ($bookingMode == 2))){
													unset($regData[$pm]);
												}
											}
										}

										$view->cardBrands	= $cardBrands;
										$view->bankCountry	= $bankCountry;
										$view->formUrl 		= $formUrl;
										$view->regData 		= $regData;
										$view->user 		= $user;
										$view->swfActive 	= $this->swfActive();
										$view->frame		= $frame;

										if ($config->HGW_DD_GUARANTEE_MODE == 1 ) {
											$view->ddWithGuarantee 		= true;
                                        } else {
											$view->ddWithGuarantee 		= false;
										}

										if($config->HGW_MOBILE_CSS){
											if(
											    (strtolower(Shopware()->Shop()->getTemplate()->getAuthor()) == 'shopware ag') ||
												(is_int(strpos(strtolower(Shopware()->Shop()->getTemplate()->getName()),'emotion'))) ||
												(is_int(strpos(strtolower(Shopware()->Shop()->getTemplate()->getTemplate()),'responsive'))) ||
												(is_int(strpos(strtolower(Shopware()->Shop()->getTemplate()->getTemplate()),'bare')))
                                            ){
												$view->isMobile = false;
											}else{
												$view->isMobile = $this->isMobile();
											}
										}

										if($_SESSION['Shopware']['HPError'] != ''){
											$view->sErrorMessages = array($_SESSION['Shopware']['HPError']);
											unset($_SESSION['Shopware']['HPError']);
										}

										$view->tPath	= $pluginPath; // $this->Path() possible alternative for tpl include
										$pref = 'http://';
										if(isset($_SERVER['HTTPS'])){
											if($_SERVER['HTTPS'] == 'on'){ $pref = 'https://'; }
										}

										$view->pluginPath = $pref .$basepath .$pluginPath;
							}
						}
			}

            // Case for Santander to save Values to DB to use them in Request on gatewayAction()
            if(
                ($request->getControllerName() == 'checkout' &&  $action == 'saveShippingPayment') ||
                ($request->getControllerName() == 'checkout' &&  $action == 'payment') ||
                ($request->getControllerName() == 'account' &&  $action == 'savePayment')
            ){
                //load Userdata to check payment method
                $user = Shopware()->Modules()->Admin()->sGetUserData();

                // check if IVPD is active
                if(
                    ($request->getPost("BRAND") == "PAYOLUTION_DIRECT") &&
                    ($user['additional']['payment']['name'] == "hgw_ivpd")
                )
                {
                    //save Birthdate to DB
                    $flag = ENT_COMPAT;
                    $enc = 'UTF-8';
                    $salutation         = $request->getPost('NAME_SALUTATION') == true ? htmlspecialchars($request->getPost('NAME_SALUTATION'), $flag, $enc) : '';
                    $nameBirthdate      = $request->getPost('NAME_BIRTHDATE') == true ? htmlspecialchars($request->getPost('NAME_BIRTHDATE'), $flag, $enc) : '';
                    $contactPhone       = $request->getPost('CONTACT_PHONE') == true ? htmlspecialchars($request->getPost('CONTACT_PHONE'), $flag, $enc) : '';
                    //daten in DB Speichern
                    // Benoetigte User-Indexe bei SW.516 anders vergeben
                    $user = self::formatUserInfos($user);

                    $payment_data = [
                        "NAME_BIRTHDATE"  => $nameBirthdate,
                        "NAME_SALUTATION" => $salutation,
                        "CONTACT_PHONE"   => $contactPhone
                    ];

                    $sql = '
			          INSERT INTO `s_plugin_hgw_regdata`(`userID`, `payType`, `uid`, `cardnr`, `expMonth`, `expYear`, `brand`, `owner`,
					  `kto`, `blz`, `chan`, `shippingHash`, `email`, `payment_data`)
				      VALUES (:userID, :payType , :uid, :cardnr, :expMonth, :expYear, :brand, :owner,
					  :kto, :blz, :chan, :shippingHash, :email, :payment_data)
			          ON DUPLICATE KEY UPDATE
					  uid = :uidNew, cardnr = :cardnrNew, expMonth = :expMonthNew, expYear = :expYearNew, brand = :brandNew, owner = :ownerNew,
					  kto = :ktoNew, blz = :blzNew, chan = :chanNew, shippingHash = :shippingHashNew, email = :emailNew, payment_data = :payment_dataNew';

                    $params = array(
                        'userID' 	=> !empty($user['additional']['user']['userID']) && $user['additional']['user']['userID'] != "" ? $user['additional']['user']['userID'] : $user['additional']['user']['id'],
                        'payType' 	=> "ivpd",
                        'uid' 		=> "0",
                        'cardnr' 	=> "0",
                        'expMonth' 	=> "0",
                        'expYear' 	=> "0",
                        'brand' 	=> "PAYOLUTION_DIRECT",
                        'owner' 	=> $user['additional']['user']['lastname'].' '.$user['additional']['user']['firstname'],
                        'kto' 		=> "0",
                        'blz' 		=> "0",
                        'chan' 		=> $config->HGW_IVPD_CHANNEL,
                        'shippingHash' => "0",
                        'email' 	=> $user['additional']['user']['email'],
                        'payment_data' => json_encode($payment_data),

                        'uidNew' 		=> "0",
                        'cardnrNew' 	=> "0",
                        'expMonthNew' 	=> "0",
                        'expYearNew' 	=> "0",
                        'brandNew'		=> "PAYOLUTION_DIRECT",
                        'ownerNew'		=> $user['additional']['user']['lastname'].' '.$user['additional']['user']['firstname'],
                        'ktoNew' 		=> "0",
                        'blzNew' 		=> "0",
                        'chanNew' 		=> $config->HGW_IVPD_CHANNEL,
                        'shippingHashNew'=> "0",
                        'emailNew' 		=> $user['additional']['user']['email'],
                        'payment_dataNew' => json_encode($payment_data)
                    );

                    try {
                        Shopware()->Db()->query($sql, $params);
                    }
                    catch (Exception $e) {
                        $this->logError("IVPD saving to DB | ". $e->getMessage());
                    }
                }

                // Case for Santander
                if (
                    ($request->getPost("BRAND") == "SANTANDER") &&
                    ($user['additional']['payment']['name'] == "hgw_san")
                )
                {
                    $flag = ENT_COMPAT;
                    $enc = 'UTF-8';
                    $birthdate = $request->getPost("NAME_BIRTHDATE");
                    if(!empty($birthdate)){
                        $nameBirthdate = htmlentities($request->getPost('NAME_BIRTHDATE'), $flag, $enc);
                    } else {
                        $nameBirthdateYear  = $request->getPost('DateSan_Year') == true ? htmlentities($request->getPost('DateSan_Year'), $flag, $enc) : '';
                        $nameBirthdateMonth = $request->getPost('DateSan_Month') == true ? htmlspecialchars($request->getPost('DatSane_Month'), $flag, $enc) : '';
                        $nameBirthdateDay   = $request->getPost('DateSan_Day') == true ? htmlspecialchars($request->getPost('DateSan_Day'), $flag, $enc) : '';
                        $nameBirthdate = $nameBirthdateYear."-".$nameBirthdateMonth."-".$nameBirthdateDay;
                    }
                    //daten in DB Speichern
                    $user = Shopware()->Modules()->Admin()->sGetUserData();
                    
                    // Benoetigte User-Indexe bei SW.516 anders vergeben
                    $user = self::formatUserInfos($user);

                    $customerOptIn  = $request->getPost('CUSTOMER_OPTIN') == true ? htmlspecialchars(strtoupper($request->getPost('CUSTOMER_OPTIN')), $flag, $enc) : 'FALSE';
                    $customerOptIn2 = $request->getPost('CUSTOMER_ACCEPT_PRIVACY_POLICY') == true ? htmlspecialchars(strtoupper($request->getPost('CUSTOMER_ACCEPT_PRIVACY_POLICY')), $flag, $enc) : null;
                    if(empty($customerOptIn2) || $customerOptIn2 == null)
                    {
                        $customerOptIn2 = $request->getPost('CUSTOMER_OPTIN_2') == true ? htmlspecialchars(strtoupper($request->getPost('CUSTOMER_OPTIN_2')), $flag, $enc) : 'FALSE';
                    }
                    $payment_data = [
                        "NAME_BIRTHDATE"                    => $nameBirthdate,
                        "CUSTOMER_OPTIN"                    => $customerOptIn,
                        "CUSTOMER_ACCEPT_PRIVACY_POLICY"    => $customerOptIn2,
                        "NAME_SALUTATION"                   => $request->getPost('NAME_SALUTATION') == true ? htmlspecialchars($request->getPost('NAME_SALUTATION'), $flag, $enc) : 'MR',
                    ];

                    $sql = '
			                INSERT INTO `s_plugin_hgw_regdata`(`userID`, `payType`, `uid`, `cardnr`, `expMonth`, `expYear`, `brand`, `owner`,
					        `kto`, `blz`, `chan`, `shippingHash`, `email`, `payment_data`)
				            VALUES (:userID, :payType , :uid, :cardnr, :expMonth, :expYear, :brand, :owner,
					        :kto, :blz, :chan, :shippingHash, :email, :payment_data)
			                ON DUPLICATE KEY UPDATE
					        uid = :uidNew, cardnr = :cardnrNew, expMonth = :expMonthNew, expYear = :expYearNew, brand = :brandNew, owner = :ownerNew,
					        kto = :ktoNew, blz = :blzNew, chan = :chanNew, shippingHash = :shippingHashNew, email = :emailNew, payment_data = :payment_dataNew';

                    $params = array(
                        'userID' 	=> !empty($user['additional']['user']['userID']) && $user['additional']['user']['userID'] != "" ? $user['additional']['user']['userID'] : $user['additional']['user']['id'],
                        'payType' 	=> "san",
                        'uid' 		=> "0",
                        'cardnr' 	=> "0",
                        'expMonth' 	=> "0",
                        'expYear' 	=> "0",
                        'brand' 	=> "SANTANDER",
                        'owner' 	=> $user['additional']['user']['lastname'].' '.$user['additional']['user']['firstname'],
                        'kto' 		=> "0",
                        'blz' 		=> "0",
                        'chan' 		=> $config->HGW_SAN_CHANNEL,
                        'shippingHash' => "0",
                        'email' 	=> $user['additional']['user']['email'],
                        'payment_data' => json_encode($payment_data),

                        'uidNew' 		=> "0",
                        'cardnrNew' 	=> "0",
                        'expMonthNew' 	=> "0",
                        'expYearNew' 	=> "0",
                        'brandNew'		=> "SANTANDER",
                        'ownerNew'		=> $user['additional']['user']['lastname'].' '.$user['additional']['user']['firstname'],
                        'ktoNew' 		=> "0",
                        'blzNew' 		=> "0",
                        'chanNew' 		=> $config->HGW_SAN_CHANNEL,
                        'shippingHashNew'=> "0",
                        'emailNew' 		=> $user['additional']['user']['email'],
                        'payment_dataNew' => json_encode($payment_data)
                    );

                    try {
                        Shopware()->Db()->query($sql, $params);
                    }
                    catch (Exception $e) {
                        $this->logError("San saving to DB | ". $e->getMessage());
                    }
                }
            }

			if($request->getControllerName() == 'checkout'){
				$view->lang = Shopware()->Locale()->getLanguage();

				$lang = Shopware()->Locale()->getLanguage();
				if($lang == 'en'){
					$view->langStu = 'US';
				}else{
					$view->langStu = strtoupper($lang);
				}

				$sGetPaymentMeans = Shopware()->Modules()->Admin()->sGetPaymentMeans();
				foreach($sGetPaymentMeans as $key => $value){
					$avPayments[$value['name']] = $value;
				}

				// fix for missing csrf-token in SW 5.2 and greater
                $swVersion = Shopware()->Config()->version;

				if (version_compare($swVersion,"5.2.0",">=")) {
					if ($request->getPost('__csrf_token')!= false ) {
						$view->token = $request->getPost('__csrf_token');
					}
				}

				$view->avPayments = $avPayments;

				if($action == 'cart'){
					$view->isMobile = $this->isMobile();
					$view->extendsTemplate('register/hp_checkout.tpl');
				}

				if($action == 'confirm'){
					$user = Shopware()->Modules()->Admin()->sGetUserData();

                    $activePayment	= preg_replace('/hgw_/', '', $user['additional']['payment']['name']);
                    $regData = $this->getRegData($user['additional']['user']['id'], $activePayment);
                    $address = json_decode($regData['payment_data']);

                    $view->billingAdd 	= $address->billing;
                    $view->shippingAdd = $address->shipping;
                    $view->regData 		= $regData;
                    $view->tPath		= $pluginPath;

                    if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                        $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
                    }else{
                        $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
                    }
                    if(!empty($regData)){
                        switch ($user['additional']['payment']['name']){
                            case 'hgw_mpa':
                                $view->extendsTemplate('register/hp_checkout_confirm.tpl');
                                break;
                            case 'hgw_cc':
                            case 'hgw_dc':
                            case 'hgw_dd':
                                $view->extendsTemplate('register/hp_checkout_confirmreg.tpl');
                                break;
                        }
//                        if(
//                            ($user['additional']['payment']['name'] == 'hgw_mpa')
////                            ||
////                            ($user['additional']['payment']['name'] == 'hgw_cc') ||
////                            ($user['additional']['payment']['name'] == 'hgw_dc') ||
////                            ($user['additional']['payment']['name'] == 'hgw_dd')
//
//                        ){ // or every other wallet
//                            $view->extendsTemplate('register/hp_checkout_confirm.tpl');
//                        }
                    }

					if($_SESSION['Shopware']['HPWallet'] == '1'){
						$activePayment	= preg_replace('/hgw_/', '', $user['additional']['payment']['name']);
						$regData = $this->getRegData($user['additional']['user']['id'], $activePayment);

						$address = json_decode($regData['payment_data']);
						$view->billingAdd 	= $address->billing;
						$view->shippingAdd 	= $address->shipping;
						$view->regData 		= $regData;
						$view->tPath		= $pluginPath;

						if(!empty($regData)){
							if($user['additional']['payment']['name'] == 'hgw_mpa'){ // or every other wallet
								$view->extendsTemplate('register/hp_checkout_confirm.tpl');
							}
						}
					}
				}

				if($action == 'finish'){
					$view->tPath = $pluginPath;

					$this->removeReg($_SESSION['Shopware']['HPRegId']);
					unset($_SESSION['Shopware']['HPRegId']);
				}
			}

            if(
                (Shopware()->Shop()->getTemplate()->getVersion() < 3)
                && ($request->getControllerName() == 'checkout' &&  $action == 'confirm')
                && (array_key_exists('hgw_hps',$avPayments))
                && ((Shopware()->Session()->HPdidRequest != 'TRUE') || empty(Shopware()->Session()->HPdidRequest))
            ){
                //santander
                $view->assign('sanGenderVal',['MR', 'MRS']);
                $view->assign('sanGenderOut',['Herr', 'Frau']);
                $view->assign('genderShop_HpSan',$user['additional']['user']['salutation'] == 'mrs'? 'MRS' : 'MR');
                if(Shopware::VERSION == '5.1.6'){
                    $user = self::formatUserInfos($user);
                    $view->assign('accountHolder_HpSan',$user['billingaddress']['firstname'].' '.$user['billingaddress']['lastname']);
                } else {
                    $view->assign('accountHolder_HpSan',$user['additional']['user']['firstname'].' '.$user['additional']['user']['lastname']);
                }
                $view->assign('birthdate_hps',$user['additional']['user']['birthday'] ? $user['additional']['user']['birthday']: "0000-00-00");

                $view->extendsTemplate('register/hp_payment_hps.tpl');

                //expand template
                $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
            }

            if(
                (Shopware()->Shop()->getTemplate()->getVersion() < 3)
                && ($request->getControllerName() == 'checkout' &&  $action == 'payment')
                && (strtolower($user['additional']['payment']['name']) == 'hgw_hps')
                && ((Shopware()->Session()->HPdidRequest != 'TRUE') || empty(Shopware()->Session()->HPdidRequest))
            ){
                $paymentMethod = 'hps';
                $brand = "SANTANDER";

                $configData = $this->ppd_config('5', $paymentMethod);
                $userData 	= $this->ppd_user($user,strtolower($paymentMethod));
                $basketData = $this->getBasketId();
                $konfiguration = self::Config();
                $secret = $konfiguration['HGW_SECRET'];

                //fetching count of orders of customer
                $countOrderForCustomer = '';
                $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
                $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);

                $additional = array(
                    'NAME.BIRTHDATE'                => $request->getPost('NAME_BIRTHDATE'),
                    'PRESENTATION.AMOUNT'           => $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                    'PRESENTATION.CURRENCY'         => Shopware()->Currency()->getShortName(),
//                    'IDENTIFICATION.TRANSACTIONID'  => Shopware()->SessionID(),
                    'IDENTIFICATION.TRANSACTIONID' =>  Shopware_Controllers_Frontend_Payment::createPaymentUniqueId(),
                    'CRITERION.SECRET'              => hash('sha512', Shopware()->SessionID().$secret),
                    'CRITERION.SESS'                => Shopware()->Session()->sessionId,
                    'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE',
                    'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                    'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> $countOrderForCustomer['COUNT(id)'],
                );

                if(
                    ($additional['NAME.BIRTHDATE'] != "--") &&
                    ($additional['NAME.BIRTHDATE'] != "0000-00-00")
                    &&                    (!empty($additional['NAME.BIRTHDATE']))
                ){
                    $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,[],$additional,$brand);
                    $responseHps 	= $this->doRequest($requestData);

                    // redirect to santander / Gillardorn
                    if($responseHps['FRONTEND_REDIRECT_URL']){
                        Shopware()->Session()->HPdidRequest = 'TRUE';
                        return $args->getSubject()->redirect($responseHps['FRONTEND_REDIRECT_URL']);
                    } else {
                        return $args->getSubject()->redirect(array(
                            'forceSecure' => 1,
                            'controller' => 'checkout',
                            'action' => 'payment',
                        ));
                    }
                }
            }

		}
	}

    /**
	 * Event for custom code for nearly all Shopware-Events
	 */
    public function onPostDispatchTemplate(Enlight_Event_EventArgs $args){
        $request = $args->getSubject()->Request();
        $view = $args->getSubject()->View();

        if($request->getActionName() == 'finish')
        {
            if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
            }else{
                $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
            }
            $view->extendsTemplate('payment_hgw/finish.tpl');
        }

        // catch user to get saved Payment
        $user = Shopware()->Modules()->Admin()->sGetUserData();

        // functionality to show EasyCredit-Method if amount is fitting
        $basket	        = Shopware()->Modules()->Basket()->sGetBasket();
        $basketAmount   = str_replace(',', '.', $basket['AmountNumeric']);
        $shipping	    = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
        $shippingAmount = $shipping['value'];

        // Function to
        // - show EasyCredit-text on choose-payment-site
        // - assign variables for Santander HP ratepay
        if (
            ($request->getControllerName() == 'checkout') &&
            ($request->getActionName() == 'shippingPayment') &&
            ((strtolower($user['additional']['payment']['name']) == 'hgw_hpr') || (strtolower($user['additional']['payment']['name']) == 'hgw_hps'))
        )
        {
            if (
                $basketAmount+$shippingAmount >= Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT &&
                $basketAmount+$shippingAmount <= Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT
            ){
                $view->activeEasy = "TRUE";
                $view->easyAmount = $basketAmount+$shippingAmount;
                $view->HGW_EASYMINAMOUNT = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT;
                $view->HGW_EASYMAXAMOUNT = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT;
            } else {
                $view->activeEasy = "FALSE";
                $view->easyAmount = $basketAmount+$shippingAmount;
                $view->HGW_EASYMINAMOUNT = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT;
                $view->HGW_EASYMAXAMOUNT = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT;
            }

            // collect paymentdata for HP.IN
            $basket	= Shopware()->Modules()->Basket()->sGetBasket();
            $shipping	= Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();

            switch ($user['additional']['payment']['name']){
                case 'hgw_hpr':
                    $paymentMethod = 'hpr';
                    $brand = "EASYCREDIT";
                    break;
                case 'hgw_hps';
                    $paymentMethod = 'hps';
                    $brand = "SANTANDER";
                    break;
                default:
                    $paymentMethod = '';
                    break;
            }
//            $configData = $this->ppd_config('5', 'HPR');
//            $userData 	= $this->ppd_user($user,'hpr');
            $configData = $this->ppd_config('5', $paymentMethod);
            $userData 	= $this->ppd_user($user,strtolower($paymentMethod));

            $basketData = $this->getBasketId();
            $konfiguration = self::Config();
            $secret = $konfiguration['HGW_SECRET'];
            //fetching count of orders of customer
            $countOrderForCustomer = '';
            $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
            $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);

            if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                Shopware()->Session()->HPOrderId = $tranactId;
            } else {
                $tranactId = Shopware()->Session()->HPOrderId;
            }

            $additional = array(
                'PRESENTATION.AMOUNT' 	=> $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                'PRESENTATION.CURRENCY' => Shopware()->Currency()->getShortName(),
                'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                'CRITERION.SECRET' 		=> hash('sha512', $tranactId.$secret),
                'CRITERION.SESS'		=> Shopware()->Session()->sessionId,
                'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE',
                'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> $countOrderForCustomer['COUNT(id)'],

            );

            if(strtolower($user['additional']['payment']['name']) == 'hgw_hpr'){
                // prepare data and do request for both hire purchase
                $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,[],$additional,$brand);
                $responseHp 	= $this->doRequest($requestData);

                //preparing OptIn-text to show
                $optinText = $responseHp['CONFIG_OPTIN_TEXT'];
                $optinText = str_replace('{', '', $optinText);
                $optinText = str_replace('"optin": "', '', $optinText);
                $optinText = str_replace('%TESTSHOPVARIABLE%', 'dieser Onlineshop', $optinText);
                $optinText = str_replace('"', '', $optinText);
                $optinText = str_replace('}', '', $optinText);

                $view->configOptInText = $optinText;
                $view->extendsTemplate('register/hp_payment_hpr.tpl');
            }

            if(strtolower($user['additional']['payment']['name']) == 'hgw_hps'){
                //santander
                $view->assign('sanGenderVal',['MR', 'MRS']);
                $view->assign('sanGenderOut',['Herr', 'Frau']);
                $view->assign('genderShop_HpSan',$user['additional']['user']['salutation'] == 'mrs'? 'MRS' : 'MR');
                if(Shopware::VERSION == '5.1.6'){
                    $user = self::formatUserInfos($user);
                    $view->assign('accountHolder_HpSan',$user['billingaddress']['firstname'].' '.$user['billingaddress']['lastname']);
                } else {
                    $view->assign('accountHolder_HpSan',$user['additional']['user']['firstname'].' '.$user['additional']['user']['lastname']);
                }
                $view->assign('birthdate_hps',$user['additional']['user']['birthday'] ? $user['additional']['user']['birthday']: "0000-00-00");
                $view->extendsTemplate('register/hp_payment_hps.tpl');
            }

            //expand template
            if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
            }else{
                $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
            }
        }

//        if (
//            ($request->getControllerName() == 'checkout') &&
//            ($request->getActionName() == 'shippingPayment') &&
//            (strtolower($user['additional']['payment']['name']) == 'hgw_ivpd')
//        )
//        {
//        }
        //after chosen HPR or HPS redirect
        if (
            //case for Responsive template
            (
                ($request->getControllerName() == 'checkout') &&
                ($request->getActionName() == 'saveShippingPayment') &&
                ((strtolower($user['additional']['payment']['name']) == 'hgw_hpr') || (strtolower($user['additional']['payment']['name']) == 'hgw_hps')) &&
                ((Shopware()->Session()->HPdidRequest == 'FALSE') || empty(Shopware()->Session()->HPdidRequest))
            ) ||
            //case for Emotion template
            (
                (Shopware()->Shop()->getTemplate()->getVersion() < 3) &&
                ($request->getControllerName() == 'checkout') &&
                ($request->getActionName() == 'confirm') &&
                ((strtolower($user['additional']['payment']['name']) == 'hgw_hpr') || (strtolower($user['additional']['payment']['name']) == 'hgw_hps')) &&
                ((Shopware()->Session()->HPdidRequest == 'FALSE') || empty(Shopware()->Session()->HPdidRequest))
            )
        )
        {
            // do request HP.IN for Santander hire purchase and redirect to santander / Gilladorn
            if((strtolower($user['additional']['payment']['name']) == 'hgw_hps')){
                $paymentMethod = 'hps';
                $brand = "SANTANDER";

                $configData = $this->ppd_config('5', $paymentMethod);
                $userData 	= $this->ppd_user($user,strtolower($paymentMethod));
                $basketData = $this->getBasketId();
                $konfiguration = self::Config();
                $secret = $konfiguration['HGW_SECRET'];

                //fetching count of orders of customer
                $countOrderForCustomer = '';
                $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
                $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);

                $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                    $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                    Shopware()->Session()->HPOrderId = $tranactId;
                } else {
                    $tranactId = Shopware()->Session()->HPOrderId;
                }

                Shopware()->Session()->HPOrderId = $tranactId;
                $additional = array(
                    'NAME.BIRTHDATE'                => $request->getPost('DateSanHp_Year').'-'.$request->getPost('DateSanHp_Month').'-'.$request->getPost('DateSanHp_Day'),
                    'PRESENTATION.AMOUNT'           => $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                    'PRESENTATION.CURRENCY'         => Shopware()->Currency()->getShortName(),
                    'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                    'CRITERION.SECRET'              => hash('sha512', $tranactId.$secret),
                    'CRITERION.SESS'                => Shopware()->SessionID(),
                    'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE',
                    'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                    'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> $countOrderForCustomer['COUNT(id)'],
                );
                if(
                    ($additional['NAME.BIRTHDATE'] != "--") &&
                    ($additional['NAME.BIRTHDATE'] != "0000-00-00") &&
                    (!empty($additional['NAME.BIRTHDATE']))
                )
                {
                    $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,[],$additional,$brand);
                    $responseHps 	= $this->doRequest($requestData);
                    // redirect to santander / Gillardorn
                    if($responseHps['FRONTEND_REDIRECT_URL']){
                        Shopware()->Session()->HPdidRequest = 'TRUE';
                        return $args->getSubject()->redirect($responseHps['FRONTEND_REDIRECT_URL']);
                    } else {
                        return $args->getSubject()->redirect(array(
                            'forceSecure' => 1,
                            'controller' => 'checkout',
                            'action' => 'shippingPayment',
                        ));
                    }
                } else {
                    // redirect for Emotion
                    if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                        return $args->getSubject()->redirect(array(
                            'forceSecure' => 1,
                            'controller' => 'account',
                            'action' => 'payment',
                        ));
                    } else {
                    //redirect for Responsive
                        return $args->getSubject()->redirect(array(
                            'forceSecure' => 1,
                            'controller' => 'checkout',
                            'action' => 'shippingPayment',
                        ));
                    }
                }
            }

            if((strtolower($user['additional']['payment']['name']) == 'hgw_hpr')) {
                $paymentMethod = 'hpr';
                $brand = "EASYCREDIT";
                $configData = $this->ppd_config('5', $paymentMethod);
                $userData 	= $this->ppd_user($user,strtolower($paymentMethod));
                $basketData = $this->getBasketId();
                $konfiguration = self::Config();
                $secret = $konfiguration['HGW_SECRET'];

                //fetching count of orders of customer
                $countOrderForCustomer = '';
                $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
                $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);

                if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                    $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                    Shopware()->Session()->HPOrderId = $tranactId;
                } else {
                    $tranactId = Shopware()->Session()->HPOrderId;
                }
                Shopware()->Session()->HPOrderId = $tranactId;
                $additional = array(
                    'PRESENTATION.AMOUNT' 	=> $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                    'PRESENTATION.CURRENCY' => Shopware()->Currency()->getShortName(),
                    'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                    'CRITERION.SECRET' 		=> hash('sha512', $tranactId.$secret),
                    'CRITERION.SESS'		=> Shopware()->Session()->sessionId,
                    'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE',
                    'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                    'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> $countOrderForCustomer['COUNT(id)'],
                );
                $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,[],$additional,$brand);
                $responseHpr 	= $this->doRequest($requestData);

                // redirect to EasyCredit for Emotion-templates
                if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                    if (Shopware()->Session()->HPdidRequest == "TRUE") {
                        return $args->getSubject()->redirect($responseHpr['FRONTEND_REDIRECT_URL']);
                    } else {
                        return $args->getSubject()->redirect(array(
                            'forceSecure' => 1,
                            'controller' => 'account',
                            'action' => 'payment',
                            'sTarget' => 'checkout'
                        ));
                    }
                    exit();
                } else {
                    // redirect to EasyCredit for Responsive-template
                    if ($responseHpr['FRONTEND_REDIRECT_URL']) {
                            Shopware()->Session()->HPdidRequest = 'TRUE';
                            return $args->getSubject()->redirect($responseHpr['FRONTEND_REDIRECT_URL']);
                            exit();
//                        }

                    } else {
                       return $args->getSubject()->redirect(array(
                        'forceSecure' => 1,
                        'controller' => 'checkout',
                        'action' => 'shippingPayment',
                        ));
                    }
                }
            }
        }

        // expand template "checkout_confirm" and show hire purchase rates
        if (
            ($request->getControllerName() == 'checkout') &&
            ($request->getActionName() == 'confirm') &&
            ((strtolower($user['additional']['payment']['name']) == 'hgw_hpr') || (strtolower($user['additional']['payment']['name']) == 'hgw_hps')) &&
            (Shopware()->Session()->HPdidRequest == 'TRUE')
        ){
            if (
                !empty(Shopware()->Session()->HPOrderId)
            ){
                 // fetching transaction of INI from Db for EasyCredit or Santander HP
                $transaction = self::getHgwTransactions(Shopware()->Session()->HPOrderId);
                if ($transaction) {
                    $parameters = json_decode($transaction['jsonresponse']);
                }

                // case for EasyCredit
                if(strtolower($user['additional']['payment']['name']) == 'hgw_hpr'){
                    $view->activeEasy = "TRUE";
                    $view->easyAmount = $basketAmount+$shippingAmount;
                    $view->HGW_EASYMINAMOUNT = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT;
                    $view->HGW_EASYMAXAMOUNT = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT;

                    if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                        $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
                    }else{
                        $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
                    }
                    $view->extendsTemplate('register/hp_payment_hpr.tpl');

                   // setting texts for template
                   $view->amortisationText 	= $parameters->CRITERION_EASYCREDIT_AMORTISATIONTEXTT;
                   $view->linkPrecontactInfos 	= $parameters->CRITERION_EASYCREDIT_PRECONTRACTINFORMATIONURL;
                   $view->heidelHpBrand        = "EASYCREDIDIT";
                   $view->zinsen 				= str_replace('.', ',', $this->formatNumber($parameters->CRITERION_EASYCREDIT_ACCRUINGINTEREST));
                   $view->totalWithInterest	= str_replace('.', ',', $this->formatNumber($parameters->CRITERION_EASYCREDIT_TOTALAMOUNT));

                   if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                       $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
                       $view->extendsTemplate('payment_hgw/checkout.tpl');
                       $view->extendsTemplate('payment_hgw/checkout_confirm_footer.tpl');
                   }else{
                       $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
                       $view->extendsTemplate('payment_hgw/checkout.tpl');
                       $view->extendsTemplate('payment_hgw/checkout_footer.tpl');
                   }
                }

                //Showing pre-contract-infos to customer
                if((strtolower($user['additional']['payment']['name']) == 'hgw_hps')){
                    // check if submitted address is same as deliveryaddress and if amount sent is same as amount in basket
                    if (
                        $parameters->ADDRESS_STREET == $user['shippingaddress']['street']
                        && $parameters->ADDRESS_CITY == $user['shippingaddress']['city']
                        && $parameters->ADDRESS_ZIP == $user['shippingaddress']['zipcode']
                        && number_format($parameters->PRESENTATION_AMOUNT,2) == number_format($basketAmount + $shippingAmount, 2)
                    ) {
                        $view->linkPrecontactInfos = $parameters->CRITERION_SANTANDER_HP_PDF_URL;
                        $view->heidelHpBrand = "SANTANDER_HP";

                        if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                            $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
                            $view->extendsTemplate('payment_hgw/checkout.tpl');
                        } else {
                            $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
                            $view->extendsTemplate('payment_hgw/checkout.tpl');
                        }
                    } else {
                        Shopware()->Session()->HpHpsErrorAdress = true;
                        return $args->getSubject()->redirect(array(
                            'forceSecure' => 1,
                            'controller' => 'PaymentHgw',
                            'action' => 'fail'
                        ));
                    }
                }
             }
        }

        // helper if customer has chosen EasyCredit before entering checkout
        if (
            ($request->getControllerName() == 'checkout') &&
            ($request->getActionName() == 'confirm') &&
            ((strtolower($user['additional']['payment']['name']) == 'hgw_hpr') || (strtolower($user['additional']['payment']['name']) == 'hgw_hps')) &&
            (Shopware()->Session()->HPdidRequest == FALSE )
        )
        {
            if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                // redirect to account/payment only for registered customers, guest-customers have their payment-choose-form
                // on checkout/confirm
                if($user['additional']['user']['accountmode']){
                    $args->getSubject()->forward('payment', 'account');
                }
            }else {
                $args->getSubject()->forward('shippingPayment', 'checkout');
            }
        }

        // Setting template for checkout finish to show interest and ammount with interest for EasyCredit
        if (
            ($request->getControllerName() == 'checkout') &&
            ($request->getActionName() == 'finish') &&
            (strtolower($user['additional']['payment']['name']) == 'hgw_hpr')
        )
        {
            if(
                (!empty(Shopware()->Session()->HPOrderId))
                && (isset(Shopware()->Session()->HPOrderId))
                && Shopware()->Session()->HPOrderId != ""
            ){
                $transaction 	= self::getHgwTransactions(Shopware()->Session()->HPOrderId);
            } else {
                $transaction 	= self::getHgwTransactions($request->getParams()['txnId']);
            }

            $parameters 	= json_decode($transaction['jsonresponse']);

            $view->zinsen 				= str_replace('.', ',', $this->formatNumber($parameters->CRITERION_EASYCREDIT_ACCRUINGINTEREST));
            $view->totalWithInterest	= str_replace('.', ',', $this->formatNumber($parameters->CRITERION_EASYCREDIT_TOTALAMOUNT));

            if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
                $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
            }else{
                $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
            }
            $view->extendsTemplate('payment_hgw/checkout_footer.tpl');
        }

        //after chosen HPR redirect to EasyCredit
        if (
            (
                ($request->getControllerName() == 'checkout') &&
                ($request->getActionName() == 'saveShippingPayment') &&
                (strtolower($user['additional']['payment']['name']) == 'hgw_hpr') &&
                (Shopware()->Shop()->getTemplate()->getVersion() < 3)
            )
            ||
            (
                ($request->getControllerName() == 'checkout') &&
                ($request->getActionName() == 'saveShippingPayment') &&
                (strtolower($user['additional']['payment']['name']) == 'hgw_hpr') &&
                (Shopware()->Shop()->getTemplate()->getVersion() > 3)
            )

        )
        {
            // redirect to EasyCredit
            return $args->getSubject()->redirect($responseHpr['FRONTEND_REDIRECT_URL']);
        }
    }

    /**
     * Event for EasyCredit in Emotion-template
     * @param Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function onPostDispatchFrontendCheckoutAccount(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $request = $args->getSubject()->Request();
        $user = Shopware()->Modules()->Admin()->sGetUserData();

        // check which paymentmethods are available
        $allPayments = Shopware()->Modules()->Admin()->sGetPaymentMeans();
        foreach($allPayments as $key => $value){
            $avPayments[$value['name']] = $value;
        }
        // only do Request if EasyCredit is active
        if(
            // case for Emotion templates
            (Shopware()->Shop()->getTemplate()->getVersion() < 3) &&
            ($request->getControllerName() == "account") &&
            ($request->getActionName() == "payment") &&
            (array_key_exists('hgw_hpr',$avPayments))
        ){
            //collect data
            $user = Shopware()->Modules()->Admin()->sGetUserData();
            $basket	= Shopware()->Modules()->Basket()->sGetBasket();
            $shipping	= Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
            $configData = $this->ppd_config('5', 'HPR');
            $userData 	= $this->ppd_user($user,'hpr');
            $konfiguration = self::Config();
            $secret = $konfiguration['HGW_SECRET'];

            if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                Shopware()->Session()->HPOrderId = $tranactId;
            } else {
                $tranactId = Shopware()->Session()->HPOrderId;
            }

            $additional = array(
                'PRESENTATION.AMOUNT' 	=> $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                'PRESENTATION.CURRENCY' => Shopware()->Currency()->getShortName(),
                'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                'CRITERION.SECRET' 		=> hash('sha512', $tranactId.$secret),
                'CRITERION.SESS'		=> Shopware()->Session()->sessionId,
                'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ? 'TRUE' : 'FALSE',
                'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> empty($countOrderForCustomer['COUNT(id)']) ? "0" : $countOrderForCustomer['COUNT(id)'],
            );

            $basketData = $this->getBasketId();
            // prepare data and do request
            $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,$additional);
            $responseHps 	= $this->doRequest($requestData);

            //preparing OptIn-text to show
            $optinText = $responseHps['CONFIG_OPTIN_TEXT'];

            $optinText = str_replace('{', '', $optinText);
            $optinText = str_replace('"optin": "', '', $optinText);
            $optinText = str_replace('%TESTSHOPVARIABLE%', 'dieser Onlineshop', $optinText);
            $optinText = str_replace('"', '', $optinText);
            $optinText = str_replace('}', '', $optinText);

            //expand template
            $view->configOptInText = $optinText;
            Shopware()->Session()->HPdidRequest = true;
        }

        // redirect to easyCredit on checkout/confirm call
        if(
            // case for Emotion templates
            (Shopware()->Shop()->getTemplate()->getVersion() < 3) &&
            ($request->getControllerName() == "account") &&
            ($request->getActionName() == "payment") &&
            (strtolower($user['additional']['payment']['name']) == 'hgw_hpr')&&
            ($request->getPost("WantEasy")== "TRUE")
        ){
            //collect data
            $user = Shopware()->Modules()->Admin()->sGetUserData();
            $basket	= Shopware()->Modules()->Basket()->sGetBasket();
            $shipping	= Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
            $configData = $this->ppd_config('5', 'HPR');
            $userData 	= $this->ppd_user($user,'hpr');
            $konfiguration = self::Config();
            $secret = $konfiguration['HGW_SECRET'];

            if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                Shopware()->Session()->HPOrderId = $tranactId;
            } else {
                $tranactId = Shopware()->Session()->HPOrderId;
            }

            $additional = array(
                'PRESENTATION.AMOUNT' 	=> $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                'PRESENTATION.CURRENCY' => Shopware()->Currency()->getShortName(),
                'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                'CRITERION.SECRET' 		=> hash('sha512', Shopware()->Session()->HPOrderId.$secret),
                'CRITERION.SESS'		=> Shopware()->Session()->sessionId,
                'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ? 'TRUE' : 'FALSE',
                'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> empty($countOrderForCustomer['COUNT(id)']) ? "0" : $countOrderForCustomer['COUNT(id)'],
            );

            $basketData = $this->getBasketId();
            // prepare data and do request
            $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,$additional);
            $responseHps 	= $this->doRequest($requestData);

            if($responseHps['FRONTEND_REDIRECT_URL']){
                return $args->getSubject()->redirect($responseHps['FRONTEND_REDIRECT_URL']);
            } else {
                return $args->getSubject()->redirect('fail');
            }

        }

        if(Shopware()->Shop()->getTemplate()->getVersion() < 3){
            $view->addTemplateDir(dirname(__FILE__) . '/Views/frontend/');
        }else{
            $view->addTemplateDir(dirname(__FILE__) . '/Views/responsive/frontend/');
        }
        $view->extendsTemplate('register/hp_payment_hpr.tpl');

        // case for santander hire purchase in emotion templates to set theme variables
        if(
            // case for Emotion templates
            (Shopware()->Shop()->getTemplate()->getVersion() < 3) &&
            ($request->getControllerName() == "account") &&
            ($request->getActionName() == "payment") &&
            (array_key_exists('hgw_hps',$avPayments))
        ){
            unset(Shopware()->Session()->HPdidRequest);
            $view->sanGenderVal = ['MR', 'MRS'];
            $view->sanGenderOut = ['Herr', 'Frau'];
            $view->genderShop_HpSan = ($user['additional']['user']['salutation'] == 'mrs'? 'MRS' : 'MR');
            if(Shopware::VERSION == '5.1.6'){
                $user = self::formatUserInfos($user);
                $view->accountHolder_HpSan = $user['billingaddress']['firstname'].' '.$user['billingaddress']['lastname'];
            } else {
                $view->accountHolder_HpSan = $user['additional']['user']['firstname'].' '.$user['additional']['user']['lastname'];
            }
            $view->assign('birthdate_hps',$user['additional']['user']['birthday'] ? $user['additional']['user']['birthday']: "0000-00-00");

            $view->extendsTemplate('register/hp_payment_hps.tpl');
        }

        // redirect to santander on checkout/confirm call
        if(
            // case for Emotion templates
            (Shopware()->Shop()->getTemplate()->getVersion() < 3) &&
            ($request->getControllerName() == "account") &&
            ($request->getActionName() == "savePayment") &&
            ((strtolower($user['additional']['payment']['name']) == 'hgw_hps'))
            && ((Shopware()->Session()->HPdidRequest == 'FALSE') || empty(Shopware()->Session()->HPdidRequest))
        ){
            $paymentMethod = 'hps';
            $brand = "SANTANDER";

            $basket         = Shopware()->Modules()->Basket()->sGetBasket();
            $shipping       = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
            $configData     = $this->ppd_config('5', $paymentMethod);
            $userData       = $this->ppd_user($user,strtolower($paymentMethod));
            $basketData     = $this->getBasketId();
            $konfiguration  = self::Config();
            $secret         = $konfiguration['HGW_SECRET'];
            //fetching count of orders of customer
            $countOrderForCustomer = '';
            $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
            $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);

            if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                Shopware()->Session()->HPOrderId = $tranactId;
            } else {
                $tranactId = Shopware()->Session()->HPOrderId;
            }

            $additional = array(
                'NAME.BIRTHDATE'                => $request->getPost('NAME_BIRTHDATE'),
                'PRESENTATION.AMOUNT'           => $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                'PRESENTATION.CURRENCY'         => Shopware()->Currency()->getShortName(),
                'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                'CRITERION.SECRET'              => hash('sha512', $tranactId.$secret),
                'CRITERION.SESS'                => Shopware()->Session()->sessionId,
                'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE',
                'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> $countOrderForCustomer['COUNT(id)'],
            );
            if(
                ($request->getPost('NAME_BIRTHDATE') != "--") &&
                ($request->getPost('NAME_BIRTHDATE') != "0000-00-00") &&
                ($request->getPost('NAME_BIRTHDATE') != "")
            )
            {
                $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,[],$additional,$brand);
                $responseHps 	= $this->doRequest($requestData);

                // redirect to santander / Gillardorn
                if($responseHps['FRONTEND_REDIRECT_URL']){
                    Shopware()->Session()->HPdidRequest = 'TRUE';
                    return $args->getSubject()->redirect($responseHps['FRONTEND_REDIRECT_URL']);
                } else {
                    return $args->getSubject()->redirect(array(
                        'forceSecure' => 1,
                        'controller' => 'checkout',
                        'action' => 'shippingPayment',
                    ));
                }
            }
        }

        if(
            // case for Emotion templates
            (Shopware()->Shop()->getTemplate()->getVersion() < 3) &&
            ($request->getControllerName() == "account") &&
            ($request->getActionName() == "savePayment") &&
            ((strtolower($user['additional']['payment']['name']) == 'hgw_hpr'))
            && ((Shopware()->Session()->HPdidRequest == 'FALSE') || empty(Shopware()->Session()->HPdidRequest))
        ){
            if(!$responseHps['FRONTEND_REDIRECT_URL']){
                return $args->getSubject()->forward('payment', 'account');
            }
            $user = Shopware()->Modules()->Admin()->sGetUserData();
            $basket	= Shopware()->Modules()->Basket()->sGetBasket();
            $shipping	= Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
            $configData = $this->ppd_config('5', 'HPR');
            $userData 	= $this->ppd_user($user,'hpr');
            $konfiguration = self::Config();
            $secret = $konfiguration['HGW_SECRET'];

            if(empty(Shopware()->Session()->HPOrderId) || !isset(Shopware()->Session()->HPOrderId)){
                $tranactId = Shopware_Controllers_Frontend_Payment::createPaymentUniqueId();
                Shopware()->Session()->HPOrderId = $tranactId;
            } else {
                $tranactId = Shopware()->Session()->HPOrderId;
            }

            $additional = array(
                'PRESENTATION.AMOUNT' 	=> $this->formatNumber($basket['AmountNumeric']+$shipping['value']),
                'PRESENTATION.CURRENCY' => Shopware()->Currency()->getShortName(),
                'IDENTIFICATION.TRANSACTIONID' =>  $tranactId,
                'CRITERION.SECRET' 		=> hash('sha512', $tranactId.$secret),
                'CRITERION.SESS'		=> Shopware()->Session()->sessionId,
                'RISKINFORMATION.CUSTOMERGUESTCHECKOUT' => $user['additional']['user']['accountmode'] == '0' ? 'TRUE' : 'FALSE',
                'RISKINFORMATION.CUSTOMERSINCE' 		=> $user['additional']['user']['firstlogin'],
                'RISKINFORMATION.CUSTOMERORDERCOUNT' 	=> empty($countOrderForCustomer['COUNT(id)']) ? "0" : $countOrderForCustomer['COUNT(id)'],

            );
            $basketData = $this->getBasketId();

            // prepare data and do request
            $requestData 	= $this->prepareHprIniData($configData, NULL , $userData, $basketData,$additional);
            $responseHps 	= $this->doRequest($requestData);

            if($responseHps['FRONTEND_REDIRECT_URL']){
                Shopware()->Session()->HPdidRequest = 'true';
                return $args->getSubject()->redirect($responseHps['FRONTEND_REDIRECT_URL']);
            } else {
                /*
                 * @todo auf Fehlerseite umleiten
                 */
//                return $args->getSubject()->redirect();
            }
        }
    }

    /**
     * getHgwTransactions() fetches a single transaction from hgw_transactions by a transactionId
     * @param $transactionId
     * @return mixed
     */
    public function getHgwTransactions($transactionId) {
        $sql= "SELECT * FROM `s_plugin_hgw_transactions` WHERE `transactionid` = ? ORDER BY `id` DESC LIMIT 1 ;";
        $params = array($transactionId);
        try {

            $transactionResult = Shopware()->Db()->fetchRow($sql, $params);
            if (empty($transactionResult) || $transactionResult == '') {
                self::Logging('getHgwTransactions Bootstrap  | No Transaction found for '.$transactionId);
            }

        } catch (Exception $e){
            self::Logging('getHgwTransactions Bootstrap failed | Message: '. $e->getMessage().' in file: '.$e->getFile());
        }
        return $transactionResult;
    }

    /*
     * Method to get BasketId from Basket API
     * needed for wallet transactions
     * @return $response
     */
    public function getBasketId(){
        try{
            $config = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config();
            $ta_mode= $config->HGW_TRANSACTION_MODE;
            $origRequestUrl = self::$requestUrl;

            if(is_numeric($ta_mode) && (($ta_mode == 0) || ($ta_mode == 3))){
                self::$requestUrl = self::$live_url_basket;
            }else{
                self::$requestUrl = self::$test_url_basket;
            }

            $params['raw']= $this->prepareBasketData();
            $response = $this->doRequest($params);
            // switch back to post url, after basket request is sent
            self::$requestUrl = $origRequestUrl;
            return $response;
        }catch(Exception $e){
            $this->hgw()->Logging('getBasketId | '.$e->getMessage());
            return;
        }
    }

	/**
	 * Event for custom code (backend functionality)
	 */
	public function loadHeidelBackend(Enlight_Event_EventArgs $args){
		try{
			$request = $args->getSubject()->Request();
			$response = $args->getSubject()->Response();
			$view = $args->getSubject()->View();
			$view->addTemplateDir(__DIR__ . '/Views/backend/');

			if($request->getActionName() === 'load'){
				$view->extendsTemplate('order/view/detail/heidel_window.js');
			}
		}catch(Exception $e){
			$this->Logging('loadHeidelBackend | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Event to provide the file collection for LESS
	 */
	public function addLessFiles(Enlight_Event_EventArgs $args){
		$less = new \Shopware\Components\Theme\LessDefinition(
				// configuration
				array(),
				// LESS files to compile
				array(
						__DIR__ . '/Views/responsive/frontend/_public/src/less/all.less'
				),
				// import directory
				__DIR__
				);

		return new Doctrine\Common\Collections\ArrayCollection(array($less));
	}

    /**
     * Provide the file collection for js files
     *
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
//    public function addJsFiles(Enlight_Event_EventArgs $args)
//    {
//       $jsFiles = array(
//            __DIR__ . '/Views/responsive/frontend/_public/src/js/valPayment.js',
//            __DIR__ . '/Views/responsive/frontend/_public/src/js/hpf_script.js'
//        );
//        return new Doctrine\Common\Collections\ArrayCollection($jsFiles);
//    }

	/**
	 * Method to return the path to the backend controller.
	 * @return string
	 */
	public static function onGetControllerPathBackend(Enlight_Event_EventArgs $args){
		Shopware()->Template()->addTemplateDir(dirname(__FILE__).'/Views/');
		return dirname(__FILE__).'/Controllers/Backend/BackendHgw.php';
	}

	/**
	 * Method to remove registration data from database
	 * @param string $id - id of registration
	 */
	public function removeReg($id){
		try{
			$sql = 'DELETE FROM `s_plugin_hgw_regdata` WHERE id = ?';
			Shopware()->Db()->query($sql, array($id));
		}catch(Exception $e){
			$this->Logging('removeReg | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get configuration data for a transaction
	 * (ppd stands for 'prepare post data')
	 * @param string $bookingMode - booking mode
	 * @param string $pm - payment code
	 * @param string $uid - unique id
	 * @return array $ppd_config
	 */
	public function ppd_config($bookingMode, $pm, $uid = NULL, $gateway = NULL, $isabo = NULL){
		try{
			$config = Shopware()->Plugins()->Frontend()->HeidelGateway()->Config();

			switch ($bookingMode){
                case '1':
                    $ppd_config['PAYMENT.TYPE'] = "DB";
                    break;
                case '2':
                    $ppd_config['PAYMENT.TYPE'] = "PA";
                    break;
                case '3':
                case '4':
                    if($uid != NULL){
                        if($gateway && $bookingMode == '3'){
                            $ppd_config['PAYMENT.TYPE'] = "DB";
                        }elseif($gateway && $bookingMode == '4'){
                            $ppd_config['PAYMENT.TYPE'] = "PA";
                        }else{
                            $ppd_config['PAYMENT.TYPE'] = "RR";
                        }
                        $ppd_config['IDENTIFICATION.REFERENCEID'] = $uid;
                    }else{
                        $ppd_config['PAYMENT.TYPE'] = "RG";
                    }
                    break;
                case '5':
                    $ppd_config['PAYMENT.METHOD'] = 'HP';
                    $ppd_config['PAYMENT.TYPE'] = "IN";
                    break;
            }

//			if($bookingMode == '1'){ $ppd_config['PAYMENT.TYPE'] = "DB"; }
//			if($bookingMode == '2'){ $ppd_config['PAYMENT.TYPE'] = "PA"; }
//			if(($bookingMode == '3') || ($bookingMode == '4')){
//				if($uid != NULL){
//					if($gateway && $bookingMode == '3'){
//						$ppd_config['PAYMENT.TYPE'] = "DB";
//					}elseif($gateway && $bookingMode == '4'){
//						$ppd_config['PAYMENT.TYPE'] = "PA";
//					}else{
//						$ppd_config['PAYMENT.TYPE'] = "RR";
//					}
//					$ppd_config['IDENTIFICATION.REFERENCEID'] = $uid;
//				}else{
//					$ppd_config['PAYMENT.TYPE'] = "RG";
//				}
//			}
//            if($bookingMode == '5'){ $ppd_config['PAYMENT.TYPE'] = "IN"; }

			$ppd_config['SECURITY.SENDER']	= trim($config->HGW_SECURITY_SENDER);
			$ppd_config['USER.LOGIN'] 		= trim($config->HGW_USER_LOGIN);
			$ppd_config['USER.PWD'] 		= trim($config->HGW_USER_PW);

			$ta_mode = $this->Config()->HGW_TRANSACTION_MODE;

			if(is_numeric($ta_mode) && (($ta_mode == 0) || ($ta_mode == 3))){
				$ppd_config['TRANSACTION.MODE'] = 'LIVE';
				self::$requestUrl =	self::$live_url;
			}else{
				$ppd_config['TRANSACTION.MODE'] = 'CONNECTOR_TEST';
				self::$requestUrl =	self::$test_url;
			}

			if($isabo && (($pm == 'cc') || ($pm == 'dc'))){ $abo = '_ABO'; }else{ $abo = ''; }

			$ppd_config['TRANSACTION.CHANNEL'] = trim($config->{'HGW_'. strtoupper($pm).$abo.'_CHANNEL'});

			if($pm == 'hpr' || $pm == 'hps') {$pm = 'HP';}
			$ppd_config['PAYMENT.METHOD'] = $pm;
			$ppd_config['SHOP.TYPE'] = 'Shopware - '. Shopware()->Config()->Version;
			$ppd_config['SHOPMODULE.VERSION'] = $this->moduleType ." ". $this->getVersion();

			return $ppd_config;

		}catch(Exception $e){
			$this->Logging('ppd_config | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get user data
	 * (ppd stands for 'prepare post data')
	 * @param array $user
	 * @param string $pm - payment method
	 * @return array $ppd_user
	 */
	public function ppd_user($user = NULL, $pm = NULL){
		try{
			// check if user is logged in, otherweise set dummy data
			if(Shopware()->Modules()->Admin()->sCheckUser()){
				if($user == NULL){ $user = Shopware()->Modules()->Admin()->sGetUserData(); }
                // getting Customer's userid to save regdata in regData-table
                $ppd_user['CRITERION.USER_ID']	= !empty($user['additional']['user']['userID']) ? $user['additional']['user']['userID'] : $user['additional']['user']['customerId'];
                // use shipping adress instead of billing adress, if set/possible
				if($pm == 'va'  || $pm == 'mpa'){

					$countryId = $user['shippingaddress']['countryID'] != '' ? $user['shippingaddress']['countryID'] : $user['billingaddress']['countryID'];
					$countryInfo = Shopware()->Modules()->Admin()->sGetCountry($countryId);

					$ppd_user['ADDRESS.COUNTRY']	= $countryInfo['countryiso'];
					$ppd_user['NAME.GIVEN']			= $user['shippingaddress']['firstname'] != ''   ? $user['shippingaddress']['firstname'] : $user['billingaddress']['firstname'];
					$ppd_user['NAME.FAMILY']		= $user['shippingaddress']['lastname'] != ''    ? $user['shippingaddress']['lastname'] : $user['billingaddress']['lastname'];
					$ppd_user['ADDRESS.STREET'] 	= $user['shippingaddress']['street'] != ''      ? $user['shippingaddress']['street'] : $user['billingaddress']['street'];
					$ppd_user['ADDRESS.STREET'] 	.= $user['shippingaddress']['streetnumber'] != '' ? $user['shippingaddress']['streetnumber'] : $user['billingaddress']['streetnumber'];
					$ppd_user['ADDRESS.ZIP'] 		= $user['shippingaddress']['zipcode'] != ''     ? $user['shippingaddress']['zipcode'] : $user['billingaddress']['zipcode'];
					$ppd_user['ADDRESS.CITY'] 		= $user['shippingaddress']['city'] != ''        ? $user['shippingaddress']['city'] : $user['billingaddress']['city'];
					$ppd_user['CONTACT.PHONE'] 		= $user['shippingaddress']['phone'] != ''       ? $user['shippingaddress']['phone'] : $user['billingaddress']['phone'];

				}else{
					$countryInfo = Shopware()->Modules()->Admin()->sGetCountry($user['billingaddress']['countryID']);
					if (strtoupper($user['shippingaddress']['salutation']) == 'MS') {
						$user['shippingaddress']['salutation'] = 'MRS';
					}

                    $ppd_user['ADDRESS.COUNTRY']	= $countryInfo['countryiso'];
                    $ppd_user['NAME.SALUTATION']	= strtoupper($user['billingaddress']['salutation']) == 'MS' ? 'MRS' : strtoupper($user['billingaddress']['salutation']);
                    $ppd_user['NAME.GIVEN']			= $user['billingaddress']['firstname'];
                    $ppd_user['NAME.FAMILY']		= $user['billingaddress']['lastname'];
                    $ppd_user['NAME.COMPANY']		= $user['billingaddress']['company'].' - '.$user['billingaddress']['department'];
                    $ppd_user['ADDRESS.STREET']		= $user['billingaddress']['street'].' '.$user['billingaddress']['streetnumber'];
                    $ppd_user['ADDRESS.ZIP']		= $user['billingaddress']['zipcode'];
                    $ppd_user['ADDRESS.CITY']		= $user['billingaddress']['city'];
                    $ppd_user['CONTACT.PHONE']		= $user['billingaddress']['phone'];

					if($pm == 'san' || $pm == 'ivpd' || $pm == 'hps')
					{
                        //fetching count of orders of customer
                        $countOrderForCustomer = '';
                        $sql = 'SELECT COUNT(id) FROM `s_order` WHERE userID ="'.$user['additional']['user']['userID'].'" AND ordernumber != "0"';
                        $countOrderForCustomer = Shopware()->Db()->fetchRow($sql);
                        $countryId = $user['shippingaddress']['countryID'] != '' ? $user['shippingaddress']['countryID'] : $user['billingaddress']['countryID'];
                        $countryInfo = Shopware()->Modules()->Admin()->sGetCountry($countryId);

                        $ppd_user['CRITERION.USER_ID']	= $user['additional']['user']['userID'];
                        $ppd_user['ADDRESS.COUNTRY']	= $countryInfo['countryiso'];
                        $ppd_user['NAME.GIVEN']			= $user['billingaddress']['firstname'] != ''    ? $user['billingaddress']['firstname'] : $user['billingaddress']['firstname'];
                        $ppd_user['NAME.FAMILY']		= $user['billingaddress']['lastname'] != ''     ? $user['billingaddress']['lastname'] : $user['billingaddress']['lastname'];
                        $ppd_user['ADDRESS.STREET'] 	= $user['billingaddress']['street'] != ''       ? $user['billingaddress']['street'] : $user['billingaddress']['street'];
                        $ppd_user['ADDRESS.STREET'] 	.= $user['billingaddress']['streetnumber'] != '' ? $user['billingaddress']['streetnumber'] : $user['billingaddress']['streetnumber'];
                        $ppd_user['ADDRESS.ZIP'] 		= $user['billingaddress']['zipcode'] != ''      ? $user['billingaddress']['zipcode'] : $user['billingaddress']['zipcode'];
                        $ppd_user['ADDRESS.CITY'] 		= $user['billingaddress']['city'] != ''         ? $user['billingaddress']['city'] : $user['billingaddress']['city'];
                        $ppd_user['CONTACT.PHONE'] 		= $user['billingaddress']['phone'] != ''        ? $user['billingaddress']['phone'] : $user['billingaddress']['phone'];

                        $ppd_user['RISKINFORMATION.CUSTOMERGUESTCHECKOUT']  = $user['additional']['user']['accountmode'] == '0' ?  'FALSE':'TRUE';
                        $ppd_user['RISKINFORMATION.CUSTOMERSINCE'] 		    = $user['additional']['user']['firstlogin'];
                        $ppd_user['RISKINFORMATION.CUSTOMERORDERCOUNT'] 	= $countOrderForCustomer['COUNT(id)'];
                    }
				}

				$kundenNummer = '';
				if(Shopware()->Config()->version >= '5.2.12'){
					$kundenNummer = $user['additional']['user']['customernumber'];
				}else{
					$kundenNummer = $user['billingaddress']['customernumber'];
				}

				$ppd_user['CONTACT.EMAIL'] 				= $user['additional']['user']['email'];
				$ppd_user['ACCOUNT.HOLDER'] 			= $ppd_user['NAME.GIVEN'].' '.$ppd_user['NAME.FAMILY'];
				$ppd_user['IDENTIFICATION.SHOPPERID']	= $kundenNummer;
			}else{
				$ppd_user['ADDRESS.COUNTRY']			= 'DE';
				$ppd_user['NAME.SALUTATION']			= ' - ';
				$ppd_user['NAME.GIVEN'] 				= ' - ';
				$ppd_user['NAME.FAMILY'] 				= ' - ';
				$ppd_user['ADDRESS.STREET'] 			= ' - ';
				$ppd_user['ADDRESS.ZIP'] 				= ' - ';
				$ppd_user['ADDRESS.CITY'] 				= ' - ';
				$ppd_user['CONTACT.EMAIL'] 				= 'dummy@heidelpay.de';
				$ppd_user['ACCOUNT.HOLDER'] 			= ' - ';
				$ppd_user['IDENTIFICATION.SHOPPERID']	= 'guest';
			}
			return $ppd_user;
		}catch(Exception $e){
			$this->Logging('ppd_user | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to prepare data for Basket API
	 * @param array $basket
	 * @return array $shoppingCart
	 */
	public function prepareBasketData($basket = NULL, $user = NULL){
		try{
            $basket == NULL ? 	$basket = Shopware()->Modules()->Basket()->sGetBasket() : $basket;
			$user == NULL	?	$user = Shopware()->Modules()->Admin()->sGetUserData()	: $user;

            $shippingCostVariable = Shopware()->Session()->sOrderVariables;
            $shippingCostArray = $shippingCostVariable->sBasket;

			$count = '1';
			$amountTotalVat = 0;
			$shoppingCart = array();

			// if customergroup "händler" is not configured to "Bruttopreise im Shop" we need other values
			$queryBuilder = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();
            $queryBuilder->select('*')
                ->from('s_core_customergroups')
                ->where('groupkey = :groupkey')
                ->setParameter('groupkey', "H");

            $data = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
            $isTaxActive = $data[0]["tax"];

			// catching basic basket-Api-information
			$shoppingCart['authentication'] = array(
					'sender' 		=> trim($this->Config()->HGW_SECURITY_SENDER),
					'login'			=> trim($this->Config()->HGW_USER_LOGIN),
					'password'		=> trim($this->Config()->HGW_USER_PW),
			);
			// catching item basket-Api-information
			foreach ($basket['content'] as $item){
				$amountNet 		= str_replace(',','.',$item['amountnet']);
				$amountVat 		= str_replace(',','.',$item['tax']);
				$amountGross 	= str_replace(',','.',$item['amount']);
				$amountPerUnit 	= str_replace(',','.',$item['price']);
	
				//$amountTotalVat = $amountVat + $amountTotalVat;
                // fix for missing Config in customergroups "Bruttopreise im Shop"
                if($isTaxActive){
                    $shoppingCart['basket']['basketItems'][] = array(
                        'position'				=> $count,
                        'basketItemReferenceId' => $count,
                        'articleId'				=> !empty($item['ean']) ? $item['ean'] : $item['ordernumber'],
                        'unit'					=> $item['packunit'],
                        'quantity'				=> $item['quantity'],
                        'vat'					=> $item['tax_rate'],
                        'amountNet'				=> floor(bcmul($amountNet, 100, 10)),
                        'amountVat'				=> floor(bcmul($amountVat, 100, 10)),
                        'amountGross'			=> floor(bcmul($amountGross, 100, 10)),
                        'amountPerUnit'			=> floor(bcmul($amountPerUnit, 100, 10)),
                        'type'					=> $amountGross >= 0 ? 'goods' : 'voucher',
                        'title'					=> strlen($item['articlename']) > 255 ? substr($item['articlename'], 0, 250).'...' : $item['articlename'],
                        'description'			=> strlen($item['additional_details']['description']) > 255 ? substr($item['additional_details']['description'], 0, 250).'...' : $item['additional_details']['description'],
                        'imageUrl'				=> $item['image']['src']['2'], // 105px x 105px with original thumbnail mapping
                    );
                } else {
                    $shoppingCart['basket']['basketItems'][] = array(
                        'position'				=> $count,
                        'basketItemReferenceId' => $count,
                        'articleId'				=> !empty($item['ean']) ? $item['ean'] : $item['ordernumber'],
                        'unit'					=> $item['packunit'],
                        'quantity'				=> $item['quantity'],
                        'vat'					=> $item['tax_rate'],
                        'amountNet'				=> floor(bcmul($amountNet, 100, 10)),
                        'amountVat'				=> floor(bcmul($amountVat, 100, 10)),
                        'amountGross'			=> floor(bcmul($amountGross+$amountVat, 100, 10)),
                        'amountPerUnit'			=> floor(bcmul($amountPerUnit+$amountVat, 100, 10)),
                        'type'					=> $amountGross >= 0 ? 'goods' : 'voucher',
                        'title'					=> strlen($item['articlename']) > 255 ? substr($item['articlename'], 0, 250).'...' : $item['articlename'],
                        'description'			=> strlen($item['additional_details']['description']) > 255 ? substr($item['additional_details']['description'], 0, 250).'...' : $item['additional_details']['description'],
                        'imageUrl'				=> $item['image']['src']['2'], // 105px x 105px with original thumbnail mapping
                    );
                }

				// Hotfix for missing articleId on voucher for secured invoice payment
				if($shoppingCart['basket']['basketItems'][$count-1]['type'] == "voucher") {
                    $shoppingCart['basket']['basketItems'][$count - 1]['articleId'] = "voucher";
                }

				$count++;
			};

			// adding shipping costs as an article
			$shoppingCart['basket']['basketItems'][] = array(
					'position'				=> $count,
					'basketItemReferenceId' => $count,
					'articleId'				=> 'Ship1234',
					'unit'					=> 'stk',
					'quantity'				=> '1',
					'vat'					=> $basket['sShippingcostsTax'],
                    'amountNet'				=> floor(bcmul($shippingCostArray['sShippingcostsNet'], 100, 10)),
                    'amountVat'				=> floor(bcmul($shippingCostArray['sShippingcostsWithTax']-$shippingCostArray['sShippingcostsNet'], 100, 10)),
                    'amountGross'			=> floor(bcmul($shippingCostArray['sShippingcosts'], 100, 10)),
                    'amountPerUnit'			=> floor(bcmul($shippingCostArray['sShippingcosts'], 100, 10)),
					'type'					=> 'shipment',
					'title'					=> 'Shipping',
			);
	
	
			if (empty($basket['sAmountTax'])){
				foreach ($shoppingCart['basket']['basketItems'] as $singleItem){
					$amountTotalVat += $singleItem['amountVat'];
				}
			} else {
				$amountTotalVat = $basket['sAmountTax'];
			}

			// adding total amounts basket-Api-information
            $amountTotalNet      = number_format($basket['AmountNetNumeric'], 4,".","");
            $amountTotalNet      = bcmul($amountTotalNet, 100, 0);

            $amountTotalGross    = number_format($basket["AmountNumeric"], 4,".","");
            $amountTotalGross    = bcmul($amountTotalGross, 100, 0);

            if($isTaxActive){
                $amountTotalVatCalc = number_format(bcmul($basket["sAmountTax"], 100, 0),0,".","");
            } else {
                $amountTotalVatCalc = number_format( bcmul($basket["sAmountTax"], 100, 0),"0","","");
            }

            $basketTotalData['basket'] = [
                'amountTotalNet' => $amountTotalNet,
                'amountTotalVat' => $amountTotalVatCalc,
                'currencyCode'   => !empty($basket["sCurrencyName"])  ? $basket["sCurrencyName"]: "EUR",
                'itemCount'		 => count($shoppingCart['basket']['basketItems']),
            ];

            $shoppingCart['basket'] = array_merge($shoppingCart['basket'],$basketTotalData['basket']);

			return $shoppingCart;
		}catch(Exception $e){
			$this->Logging('prepareBasketData | '.$e->getMessage());
			return;
		}
	}

    /**
     * function to prepare Request-Data for paymentmethod HPR
     * @param array $config
     * @param array $frontend
     * @param array $userData
     * @param array $basketData
     * @param array $criterion
     * @param array $additional
     * @param array $brand
     * @return array
     */
    public function prepareHprIniData($config = array(), $frontend = array(), $userData = array(), $basketData = array(), $criterion = array(), $additional = array(), $brand = "")
    {
        try {
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
            $type = (!array_key_exists('PAYMENT.TYPE',$config)) ? 'PA' : $config['PAYMENT.TYPE'];
            $params['PAYMENT.CODE'] 		= "HP.".$type;
            $params['ACCOUNT.BRAND'] 		= "EASYCREDIT";
            $params['FRONTEND.ENABLED'] 	= "true";

            switch ($brand){
                case "SANTANDER":
                    $params['ACCOUNT.BRAND'] 		= "SANTANDER_HP";
//                    $params['FRONTEND.ENABLED'] 	= "false";
                    break;
                case "EASYCREDIT";
                    $params['ACCOUNT.BRAND'] 		= "EASYCREDIT";
                    break;
            }

            //adding additionaldata
            $params = array_merge($params,$additional);

            if(array_key_exists('SHOP.TYPE',$config)) $params['SHOP.TYPE'] = $config['SHOP.TYPE'];
            if(array_key_exists('SHOPMODULE.VERSION',$config)) $params['SHOPMODULE.VERSION'] = $config['SHOPMODULE.VERSION'];

            // costumer data configuration
            $params = array_merge($params, $userData);

            // basket data configuration
            $params['BASKET.ID'] = $basketData['basketId'];

            // criterion data configuration
            $params = array_merge($params, $criterion);
            $params['CRITERION.SHOP_ID']	= Shopware()->Shop()->getId();
            $params['CRITERION.PUSH_URL'] 	= Shopware()->Front()->Router()->assemble(array('forceSecure' => 1,'controller' => 'PaymentHgw','action' => 'rawnotify'));
            $params['REQUEST.VERSION'] 		= "1.0";

            $params['FRONTEND.RESPONSE_URL'] = Shopware()->Front()->Router()->assemble(array(
                'forceSecure'	=> 1,
                'controller' 	=> 'PaymentHgw',
                'action' 		=> 'responseHpr'
            ));

            return $params;
        } catch (Exception $e) {
            $this->Logging('prepareHprIniData | '.$e->getMessage());
        }
    }
	
	/**
	 * Method to get registerd payment information
	 * @param string $userId
	 * @param string $activePayment
	 * @return array $reg - registered payment data
	 */
	public function getRegData($userId, $activePayment = NULL){
		try{

			if($activePayment == NULL){ $activePayment = '%'; }

			$reg = Shopware()->Db()->fetchAll("
				SELECT * FROM `s_plugin_hgw_regdata`
				WHERE `userID` = ?
				AND `payType` LIKE ?
				", array($userId, $activePayment)
					);

			if($activePayment == '%'){
				return $reg;
			}else{
				return $reg['0'];
			}
		}catch(Exception $e){
			$this->Logging('getRegData | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to do Request via CURL
	 * @param array $params
	 * @param string $url
	 * @return array $result
	 */
	public function doRequest($params = array(), $url = NULL){
		try{

		    if($url == NULL){ $url = self::$requestUrl; }
			$client = new Zend_Http_Client($url, array(
					'useragent' => 'Shopware/' . Shopware()->Config()->Version,
					'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
			));

			if(array_key_exists('raw', $params)){
				$client->setRawData(json_encode($params['raw']), 'application/json');
			}else{
				$client->setParameterPost($params);
			}

			if(extension_loaded('curl')){
				$adapter = new Zend_Http_Client_Adapter_Curl();
				$adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
				$adapter->setCurlOption(CURLOPT_SSL_VERIFYHOST, false);
				$client->setAdapter($adapter);
			}
			$response = $client->request('POST');

            if(array_key_exists('raw', $params)){
				$res = json_decode($response->getBody(), true);
				if($response->isError()){
                    self::Logging('doRequest '.$params["PAYMENT.CODE"].' '.'Brand:'.$params["ACCOUNT.BRAND"].' | TransId: '.$params["IDENTIFICATION.TRANSACTIONID"] .' | '.$response->getStatus().' - Message: '.$res['basketErrors'][0]['message']);
				}

				return $res;
				exit;
			}else{
				$res = $response->getBody();
			}

			$result = null;
			parse_str($res, $result);

			if(($result['PROCESSING_RESULT'] == 'NOK') && ($result['PROCESSING_STATUS'] == 'REJECTED_VALIDATION')){
                self::Logging('doRequest '.$params["PAYMENT.CODE"].' '.'Brand:'.$params["ACCOUNT.BRAND"].' | TransId: '.$params["IDENTIFICATION.TRANSACTIONID"] .' | '.$result['PROCESSING_RETURN']);
            }

			if($this->Config()->HGW_DEBUG > 0 && Shopware()->Front()->Request()->getActionName() == 'gateway'){
				print "<div style='font-family: arial; font-size: 13px;'><h1>HGW Controller / Bootstrap</h1>";
				print "<h2>Debug Mode = ".$this->Config()->HGW_DEBUG."</h2><br />";
				print "<h3>Request:</h3><table style='font-size: 12px;'>";
				foreach ($params as $key => $value){
					print "<tr><td>$key</td><td>&nbsp;=&nbsp;</td><td>$value</td></tr>";
				}
				print "</table><br /><br /><h3>Response:</h3><table style='font-size: 12px;'>";
				foreach ($result as $key => $value){
					print "<tr><td>$key</td><td>&nbsp;=&nbsp;</td><td>$value</td></tr>";
				}
				print "</table><br /><br />";
				print '<a href="'. Shopware()->Front()->Router()->assemble(array(
						'forceSecure' => 1,
						'controller' => 'checkout',
						'action' => 'cart',
				)). '">&lt;&lt;&lt; Warenkorb</a><br /><br /></div>';
				die();
			}

			return $result;
		}catch(Exception $e){
			self::Logging('doRequest '.$params["PAYMENT.CODE"].' | TransId: '.$params["IDENTIFICATION.TRANSACTIONID"] .' | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create a db entry for heidelpay,
	 * so you can filter the log-files for heidelpay entrys
	 */
	public function createLoggingUser(){
		try{
			$sql = '
			INSERT INTO `s_core_auth` (`id`, `roleID`, `username`, `name`, `email`, `active`)
			VALUES(?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE name = ?';

			$prms_id 		= NULL;
			$prms_roleID 	= '1';
			$prms_username 	= 'heidelpay-logging';
			$prms_name 		= $this->getLabel();
			$prms_email 	= 'support@heidelpay.de';
			$prms_active	= '1';

			$params = array($prms_id, $prms_roleID, $prms_username, $prms_name, $prms_email, $prms_active, $prms_name);
			return Shopware()->Db()->query($sql, $params);
		}catch(Exception $e){
			$this->Logging('createLoggingUser | '.$e->getMessage());
			return;
		}
	}

    /**
     * Method to update a db entry for heidelpay,
     * so you can filter the log-files for heidelpay entrys
     */
    public function updateLoggingUser(){
        try{
            $sql = '
			UPDATE `s_core_auth` SET `username` = ? WHERE `username` = "heidelpay"
			';

            $prms_username 	= 'heidelpay-logging';

            $params = array($prms_username);
            return Shopware()->Db()->query($sql, $params);
        }catch(Exception $e){
            $this->Logging('updateLoggingUser | '.$e->getMessage());
            return;
        }
    }

	/**
	 * Method that adds an entry to the log and throws an exception
	 * @param string $msg - additional message
	 * @param object $e - exception
	 */
	public static function logError($msg, $e){
		self::Logging($msg.$e->getMessage());
	}

	/**
	 * Method to write errors to db
	 * viewable in backend / logfiles
	 * @param string $msg - additional message
	 */
	public static function Logging($msg){
		try{
			$sql = '
			INSERT INTO `s_core_log` (`id`, `type`, `key`, `text`, `date`, `user`, `ip_address`, `user_agent`, `value4`)
			VALUES(?,?,?,?,?,?,?,?,?)';

			$prms_id 		= NULL;
			$prms_type 		= 'backend';
			$prms_key 		= 'Plugin - '.self::getLabel();
			$prms_text 		= $msg;
			$prms_date 		= date('Y-m-d H:i:s');
			$prms_user 		= self::getLabel();
			$prms_ip_address= '';
			$prms_user_agent= '';
			$prms_value4 	= '';

			$params = array($prms_id, $prms_type, $prms_key, $prms_text, $prms_date, $prms_user, $prms_ip_address, $prms_user_agent, $prms_value4);

			$prmsTextToIgnore	= array(
					"createPayments inserting new paymethods| SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hgw_san' for key 'name'",
			);

			if (in_array($prms_text, $prmsTextToIgnore)) {
				return ;
			} else {
				return Shopware()->Db()->query($sql, $params);
			}

		}catch(Exception $e){
			self::Logging('Logging | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to detected mobile devices
	 * return bool
	 */
	public function isMobile(){
		try{
			$mobileDev = array('mobi', 'android', 'playbook', 'kindle');
			$isMobile = false;

			foreach($mobileDev as $key => $dev){
				if(is_int(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), $dev))){
					$isMobile = true;
				}
			}
			return $isMobile;
		}catch(Exception $e){
			$this->Logging('isMobile | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to check if responsive template from conexco is active
	 * return bool
	 */
	public function swfActive(){
		try{
			/* check if SwfResponsiveTemplate is active  */
			$name = Shopware()->Plugins()->Frontend()->get('SwfResponsiveTemplate');
			if(!empty($name)){
				$plugin = Shopware()->Models()->find('\Shopware\Models\Plugin\Plugin', $name->getId());
				if($plugin->getActive() && $name->Config()->SwfResponsiveTemplateActive){
					return true;
				}
			}else{
				return false;
			}
		}catch(Exception $e){
			$this->Logging('swfActive | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create an array with payment methods
	 * @return array $inst - payment methods
	 */
	public function paymentMethod(){
		try{
			$inst = array();

			$inst[] = array(
					'name'			=> 'cc',
					'description'	=> 'Heidelpay CD-Edition Kreditkarte',
					'trans_desc' 	=> 'Heidelpay CD-Edition Credit Card',
			);
			$inst[] = array(
					'name'			=> 'dc',
					'description'	=> 'Heidelpay CD-Edition Debitkarte',
					'trans_desc' 	=> 'Heidelpay CD-Edition Debit Card',
			);
			$inst[] = array(
					'name'			=> 'dd',
					'description'	=> 'Heidelpay CD-Edition Lastschrift',
					'trans_desc' 	=> 'Heidelpay CD-Edition Direct Debit',
			);
			$inst[] = array(
					'name'			=> 'iv',
					'description'	=> 'Heidelpay CD-Edition Rechnung',
					'trans_desc' 	=> 'Heidelpay CD-Edition Invoice',
			);
			$inst[] = array(
					'name'			=> 'papg',
					'description'	=> 'Heidelpay CD-Edition gesicherter Rechnungskauf',
					'trans_desc' 	=> 'Heidelpay CD-Edition invoice with guarantee',
			);
            $inst[] = array(
                    'name'			=> 'ivb2b',
                    'description'	=> 'Heidelpay CD-Edition gesicherter B2B Rechnungskauf',
                    'trans_desc' 	=> 'Heidelpay CD-Edition invoice for business customer',
            );
			$inst[] = array(
					'name'			=> 'san',
					'description'	=> 'Rechnungskauf von Santander',
					'trans_desc' 	=> 'Rechnungskauf von Santander',
			);
			$inst[] = array(
					'name'			=> 'pp',
					'description'	=> 'Heidelpay CD-Edition Vorkasse',
					'trans_desc' 	=> 'Heidelpay CD-Edition Prepayment',
			);
			$inst[] = array(
					'name'			=> 'sue',
					'description'	=> 'Heidelpay CD-Edition Sofort',
					'trans_desc' 	=> 'Heidelpay CD-Edition Sofort Banking',
			);
			$inst[] = array(
					'name'			=> 'p24',
					'description'	=> 'Heidelpay CD-Edition Przelewy24',
					'trans_desc' 	=> 'Heidelpay CD-Edition Przelewy24',
			);
			$inst[] = array(
					'name'			=> 'gir',
					'description'	=> 'Heidelpay CD-Edition Giropay',
					'trans_desc' 	=> 'Heidelpay CD-Edition Giropay',
			);
			$inst[] = array(
					'name'			=> 'pay',
					'description'	=> 'Heidelpay CD-Edition PayPal',
					'trans_desc' 	=> 'Heidelpay CD-Edition PayPal',
			);
			$inst[] = array(
					'name'			=> 'ide',
					'description'	=> 'Heidelpay CD-Edition Ideal',
					'trans_desc' 	=> 'Heidelpay CD-Edition Ideal',
			);
			$inst[] = array(
					'name'			=> 'eps',
					'description'	=> 'Heidelpay CD-Edition EPS',
					'trans_desc' 	=> 'Heidelpay CD-Edition EPS',
			);
			$inst[] = array(
					'name'			=> 'bs',
					'description'	=> 'Kauf auf Rechnung Heidelpay CD-Edition',
					'additionaldescription' => $this->billsafeDesc,
			);
			$inst[] = array(
					'name'			=> 'mk',
					'description'	=> 'Heidelpay CD-Edition MangirKart',
					'trans_desc' 	=> 'Heidelpay CD-Edition MangirKart',
			);
			$inst[] = array(
					'name'			=> 'pf',
					'description'	=> 'Heidelpay CD-Edition PostFinance',
					'trans_desc' 	=> 'Heidelpay CD-Edition PostFinance',
			);
            $inst[] = array(
                'name'			=> 'ivpd',
                'description'	=> 'Payolution Rechnungskauf',
                'trans_desc' 	=> 'Payolution invoice payment',
            );
            $inst[] = array(
                'name'			=> 'hpr',
                'description'	=> 'Heidelpay CD-Edition ratenkauf by easyCredit',
                'trans_desc' 	=> 'Heidelpay CD-Edition Hire purchase by easyCredit',
                'additionalDescription' => '
								<div class="EasyPermission">
									<p>Der Finanzierungsbetrag liegt außerhalb der zulässigen Beträge ('.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT.' - '.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT.' EUR). </p>
								</div>',
                'template' 		=> 'hp_payment_hpr.tpl',
            );
            $inst[] = array(
                'name'			=> 'hps',
                'description'	=> 'Ratenkauf von Santander',
                'trans_desc' 	=> 'Hire Purchace by Santander',
                'additionalDescription' => '
								<div class="SanPermission">
									<p>Der Finanzierungsbetrag liegt außerhalb der zulässigen Beträge ('.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT.' - '.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT.' EUR). </p>
								</div>',
                'template' 		=> 'hp_payment_hps.tpl',

            );
			$inst[] = array(
					'name'			=> 'mpa',
					'description'	=> 'Heidelpay CD-Edition MasterPass',
					'additionaldescription' => 'MasterPass ist eine digitale Bezahllösung für den Einkauf im Internet von MasterCard, die von Banken und MasterCard direkt bereitgestellt wird. Ihre Kartendaten und Lieferadressen werden an einem geschützten Ort aufbewahrt. Nachdem Sie sich registriert haben, können Sie mit wenigen Klicks sicher online einkaufen.',
					'trans_addDesc' => 'The MasterPass by MasterCard&reg; digital wallet makes online shopping safe and easy by storing all your payment and shipping information in one convenient and secure place. And it’s free. With MasterPass, you simply shop, and check out faster.',
			);
			return $inst;
		}catch(Exception $e){
			$this->Logging('swfActive | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to add snippets to database
	 * @return bool
	 */
	public function addSnippets(){
		$sql = 'SELECT `s_core_locales`.`id`, `s_core_locales`.`locale`
			FROM `s_core_locales`, `s_core_shops`
			WHERE `s_core_locales`.`id` = `s_core_shops`.`locale_id`
			AND `s_core_shops`.`active` = 1';
		try{
			$langs = Shopware()->Db()->fetchAll($sql);
		}catch(Exception $e){
			$this->Logging('addSnippets | '.$e->getMessage());
			return;
		}
		foreach($langs as $key => $lang){
			if(is_int(strpos($lang['locale'],'de_'))){
				$snipLang = 'de';
			}else{
				$snipLang = 'en';
			}

			$snippets = $this->snippets();

			foreach($snippets as $key => $snippet){
				// check if all array elements are set and lang matches

                if($snippet[1] == "de")
                { $lang['id'] = "1";}
                else {$lang['id'] = "2";}

				if((count(array_filter($snippet)) == '4') /*&& ($snipLang == $snippet['1'])*/){
					$sql = 'SELECT `id` FROM `s_core_snippets` WHERE `namespace` = ? AND `shopID` = ? AND `localeID` = ? AND `name` = ?';
					$data = Shopware()->Db()->fetchAll($sql, array($snippet['0'], '1', $lang['id'], $snippet['2']));

					$sql = '';
					if(!isset($data) || empty($data[0])){
						$sql = 'INSERT `s_core_snippets` SET `namespace` = ?, `shopID` = ?, `localeID` = ?, `name` = ?, `value` = ?, created = NOW(), updated = NOW()';
					}
					if(!empty($sql)){ Shopware()->Db()->query($sql, array($snippet['0'], '1', $lang['id'], $snippet['2'], $snippet['3'])); }
				}
			}
		}
		return true;

	}

	/**
	 * Method for all new snippets
	 * @return array $snippets
	 */
	private function snippets(){
		$snippets = array();

		$snippets[] = array('frontend/payment_heidelpay/gateway','de','PaymentHeader','Bitte f&uuml;hren Sie nun die Zahlung durch:');
		$snippets[] = array('frontend/payment_heidelpay/gateway','en','PaymentHeader','Please confirm your payment:');
		$snippets[] = array('frontend/payment_heidelpay/gateway','de','PaymentInfoWait','Bitte warten...');
		$snippets[] = array('frontend/payment_heidelpay/gateway','en','PaymentInfoWait','Please wait...');
		$snippets[] = array('frontend/payment_heidelpay/gateway','de','PaymentRedirectInfo','Ihre Zahlung wird verarbeitet...');
		$snippets[] = array('frontend/payment_heidelpay/gateway','en','PaymentRedirectInfo','Your payment is processed...');
		$snippets[] = array('frontend/payment_heidelpay/gateway','de','PaymentRedirect',"Sollten Sie nicht automatisch zum Zahlungssystem weitergeleitet werden, klicken Sie bitte auf 'Weiter'");
		$snippets[] = array('frontend/payment_heidelpay/gateway','en','PaymentRedirect',"If you're not automatically forwarded to the payment system, please click 'Continue'");

        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyName','Name inkl. Rechtsform');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyName','Name of company');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyStreet','Straße');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyStreet','Street');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyZip','PLZ');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyZip','Zip');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyCity','Stadt');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyCity','City');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyCountry','Land');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyCountry','Country');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyUstNr','USt-IdNr.');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyUstNr','Vat Id');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyRegistered','Registriert');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyRegistered','Registered');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyIndustry','Branche');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyIndustry','Type of trade');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyPobox','Postfach');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyPobox','PoBox');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bCompanyRegisterNr','Registernummer');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bCompanyRegisterNr','Commercialregisternumber');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bLastName','Nachname');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bLastName','Lastname');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bPreName','Vorname');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bPreName','Prename');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bEmail','E-Mail');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bEmail','Email');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bExePhone','Telefon');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bExePhone','Phone');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bFunction','Funktion im Unternehmen');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bFunction','Function in Company');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bExeStreet','Straße u. Hausnummer');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bExeStreet','Street and housenumber');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bExeZip','Postleitzahl');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bExeZip','Zip');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bExeCity','Stadt');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bExeCity','City');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bExeCountry','Land');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bExeCountry','Country');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bHeaderPersonal','Angaben zum Inhaber:');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bHeaderPersonal','Personal information of company owner:');
        $snippets[] = array('frontend/payment_heidelpay/gateway','de','B2bHeaderFirm','Angaben zum Unternehmen:');
        $snippets[] = array('frontend/payment_heidelpay/gateway','en','B2bHeaderFirm','Company information:');

        $snippets[] = array('frontend/payment_heidelpay/fail','de','PaymentProcess','Bezahlvorgang');
		$snippets[] = array('frontend/payment_heidelpay/fail','en','PaymentProcess','Payment process');
		$snippets[] = array('frontend/payment_heidelpay/fail','de','basket','Zur&uuml;ck zum Warenkorb');
		$snippets[] = array('frontend/payment_heidelpay/fail','en','basket','back to basket');
		$snippets[] = array('frontend/payment_heidelpay/fail','de','PaymentFailed','Ihr Bezahlvorgang konnte aus folgenden Grund nicht bearbeitet werden:');
		$snippets[] = array('frontend/payment_heidelpay/fail','en','PaymentFailed','Your payment process could not be finished, because of the following reason:');
		$snippets[] = array('frontend/payment_heidelpay/fail','de','AddressError','Liefer- und Rechnungsadresse sind nicht identisch.');
		$snippets[] = array('frontend/payment_heidelpay/fail','en','AddressError','Shipping and billing address did not match.');

		$snippets[] = array('frontend/payment_heidelpay/error','de','basket','Zur&uuml;ck zum Warenkorb');
		$snippets[] = array('frontend/payment_heidelpay/error','en','basket','back to basket');
		$snippets[] = array('frontend/payment_heidelpay/error','de','PaymentProcess','Bezahlvorgang');
		$snippets[] = array('frontend/payment_heidelpay/error','en','PaymentProcess','Payment process');
		$snippets[] = array('frontend/payment_heidelpay/error','de','PaymentError','Es ist ein Fehler bei Ihrem Bezahlvorgang aufgetreten. Bitte wenden Sie sich an den Shopbetreiber.');
		$snippets[] = array('frontend/payment_heidelpay/error','en','PaymentError','An error occurred during your payment process. Please contact the shop owner.');

		// ErrorCodes
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-default','Es ist ein Fehler aufgetreten bitte versuchen Sie es erneut');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-default','An error has occured, please try again');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-login','Bitte tragen Sie ihre Daten unter "Mein Konto" ein');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-login','please fill in your accountdata');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-100.100.101','Bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-100.100.101','Please verify your payment data');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-100.100.303','Die Karte ist abgelaufen');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-100.100.303','card expired');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-100.100.500','Bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-100.100.500','Please verify your payment data');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-100.396.101','Abbruch durch Benutzer');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-100.396.101','Canceled by user');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-100.396.102','Die Transaktion wurde abgelehnt');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-100.396.102','Transaction was declined');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-100.400.110','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-100.400.110','Please choose another payment method');
        // added for Santander
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.100','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.100','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.101','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.101','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.102','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.102','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.103','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.103','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.104','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.104','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.105','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.105','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.106','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.106','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.107','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.107','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.108','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.108','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.109','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.109','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.110','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.110','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.111','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.111','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.112','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.112','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.113','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.113','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.114','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.114','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.115','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.115','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.116','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.116','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.117','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.117','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.118','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.118','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.119','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.119','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.120','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.120','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.121','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.121','This paymentmethod can´t be quoted to you. Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.122','Die von Ihnen gew&auml;hlte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte w&auml;hlen Sie eine andere Zahlungsart aus.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.122','This paymentmethod can´t be quoted to you.  Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.123','Finalisierungsbetrag muss mit offenem Betrag &uuml;bereinstimmen');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.123','finalize-amount must match open amount');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.124','Finalisierung bedarf Basked-Id');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.124','finalize needs basketId');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.125','Finalisierungsbetrag muss mit offenem Betrag &uuml;bereinstimmen');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.125','finalize-amount must match open amount');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-400.100.126','Refund wurde vom Versicherer zur&uuml;ckgewiesen');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-400.100.126','refund not accepted by insurance provider');

        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-700.400.800','Versicherung wurde bereits aktiviert');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-700.400.800','Insurance is already activated');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-700.400.801','Versicherung wurde bereits beendet');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-700.400.801','Insurance has already been cancelled');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-700.400.802','Versicherungsaktivierungsdatum &uuml;berschritten');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-700.400.802','Activation deadline is in the past');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-700.400.804','Transaktion wurde zum Versicherer bereits &uuml;bermittelt');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-700.400.804','Transaction already submitted to insurance provider');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-700.400.XXX','Ihre Bestelldaten wurden ge&auml;ndert. Bitte klicken Sie auf „Zur&uuml;ck zum Warenkorb“, um Ihre Bestellung mit den ge&auml;nderten Daten abzuschlie&szlig;en.');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-700.400.XXX','Your data has been changed. Please click on „Return to shopping cart“ to complete your order with the changed data. ');
        // Ende Santander Codes

        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.151','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.151','Please choose another payment method');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.152','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.152','Please choose another payment method');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.153','Bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.153','Please verify your payment data');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.157','Bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.157','Please verify your payment data');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.159','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.159','Please choose another payment method');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.160','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.160','Please choose another payment method');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.168','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.168','Please choose another payment method');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.171','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.171','Please choose another payment method');
		$snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.300.101','Bitte w&auml;hlen Sie eine andere Zahlart');
		$snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.300.101','Please choose another payment method');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.100.174','Der Finanzierungsbetrag liegt au&szlig;erhalb der zulässigen Beträge ('.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT.' - '.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT.' EUR) ');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.100.174','The financing amount is outside the permitted amounts of '.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMINAMOUNT.' and '.Shopware()->Plugins()->Frontend()->HeidelGateway()->Config()->HGW_EASYMAXAMOUNT.' EUR');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.400.153','Die verwendete Adresse wurde nicht gefunden');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.400.153','Sorry, your address could not be found');
        $snippets[] = array('frontend/payment_heidelpay/error','de','HPError-800.400.152','Die verwendete Adresse wurde nicht gefunden');
        $snippets[] = array('frontend/payment_heidelpay/error','en','HPError-800.400.152','Sorry, your address could not be found');

        $snippets[] = array('frontend/payment_heidelpay/cancel','de','PaymentProcess','Bezahlvorgang');
		$snippets[] = array('frontend/payment_heidelpay/cancel','en','PaymentProcess','Payment process');
		$snippets[] = array('frontend/payment_heidelpay/cancel','de','PaymentCancel','Der Bezahlvorgang wurde von Ihnen abgebrochen.');
		$snippets[] = array('frontend/payment_heidelpay/cancel','en','PaymentCancel','The payment process was canceled by you.');
		$snippets[] = array('frontend/payment_heidelpay/cancel','de','basket','Zur&uuml;ck zum Warenkorb');
		$snippets[] = array('frontend/payment_heidelpay/cancel','en','basket','back to basket');

		$snippets[] = array('frontend/payment_heidelpay/success','de','PaymentSuccess','Ihr Bezahlvorgang war erfolgreich!');
		$snippets[] = array('frontend/payment_heidelpay/success','en','PaymentSuccess','Your transaction was successfull!');
        $snippets[] = array('frontend/payment_heidelpay/success','de','PaymentProcess','Bezahlvorgang');
        $snippets[] = array('frontend/payment_heidelpay/success','en','PaymentProcess','Payment process');

        // common
        $snippets[] = array('frontend/payment_heidelpay/success','de','InvoiceHeader','Rechnungsinformation');
        $snippets[] = array('frontend/payment_heidelpay/success','en','InvoiceHeader','Invoiceinformation');
        // prepayment
		$snippets[] = array('frontend/payment_heidelpay/success','de','PrepaymentText','Bitte &uuml;berweisen Sie uns den Betrag von <strong>{AMOUNT} {CURRENCY}</strong> auf folgendes Konto: Land: {CONNECTOR_ACCOUNT_COUNTRY} Kontoinhaber: {CONNECTOR_ACCOUNT_HOLDER} Konto-Nr.: {CONNECTOR_ACCOUNT_NUMBER} Bankleitzahl: {CONNECTOR_ACCOUNT_BANK} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{IDENTIFICATION_SHORTID}</strong>');
        $snippets[] = array('frontend/payment_heidelpay/success','en','PrepaymentText','Please transfer the amount of <strong>{AMOUNT} {CURRENCY}</strong> to the following account: Country: {CONNECTOR_ACCOUNT_COUNTRY} Account holder: {CONNECTOR_ACCOUNT_HOLDER} Account No: {CONNECTOR_ACCOUNT_NUMBER} Bank Code: {CONNECTOR_ACCOUNT_BANK} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Please use the following identifcation number as payment reference: <strong>{IDENTIFICATION_SHORTID}</strong>');
        // invoice and invoice secured
        $snippets[] = array('frontend/payment_heidelpay/success','de','InvoiceText','Bitte &uuml;berweisen Sie uns den Betrag von <strong>{AMOUNT} {CURRENCY}</strong> auf folgendes Konto: \nLand: {CONNECTOR_ACCOUNT_COUNTRY} \nKontoinhaber: {CONNECTOR_ACCOUNT_HOLDER} \nKonto-Nr.: {CONNECTOR_ACCOUNT_NUMBER} \nBankleitzahl: {CONNECTOR_ACCOUNT_BANK} \nIBAN: {CONNECTOR_ACCOUNT_IBAN} \nBIC: {CONNECTOR_ACCOUNT_BIC} \n\nGeben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: \n<strong>{IDENTIFICATION_SHORTID}</strong>');
        $snippets[] = array('frontend/payment_heidelpay/success','en','InvoiceText','Please transfer the amount of <strong>{AMOUNT} {CURRENCY}</strong> to the following account: Country: {CONNECTOR_ACCOUNT_COUNTRY} Account holder: {CONNECTOR_ACCOUNT_HOLDER} Account No: {CONNECTOR_ACCOUNT_NUMBER} Bank Code: {CONNECTOR_ACCOUNT_BANK} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Please use the following identifcation number as payment reference: <strong>{IDENTIFICATION_SHORTID}</strong>');
        // direct debit
        $snippets[] = array('frontend/payment_heidelpay/success','de','DirectdebitText','Der Betrag in Höhe von <strong>{AMOUNT} {CURRENCY}</strong> wird in den n&auml;chsten Tagen von folgendem Konto abgebucht:\n\nIBAN: {ACCOUNT_IBAN}\nBIC: {ACCOUNT_BIC}\n\nDie Abbuchung enth&auml;lt die Mandatsreferenz-ID:{ACCOUNT_IDENT}\n und die Gl&auml;ubiger ID: {IDENT_CREDITOR_ID}\n\nBitte sorgen Sie f&uuml;r ausreichende Deckung auf dem entsprechenden Konto.\n\n\nVielen Dank\nMit freundlichen Gr&uuml;&szlig;en\n\n{config name=shopName}\n{config name=address}');
        $snippets[] = array('frontend/payment_heidelpay/success','en','DirectdebitText','The amount of <strong>{AMOUNT} {CURRENCY}</strong> will be debited from the following account: \n\nIBAN: {ACCOUNT_IBAN}\nBIC: {ACCOUNT_BIC}\n\nThe debit contains the Reference-Id: {ACCOUNT_IDENT}\n and the creditor-id: {IDENT_CREDITOR_ID}\n\n Please ensure that your amount is enough.\n\n\nThanks for Your purchase\nSincere regards,\n\n{config name=shopName}\n{config name=address}');
        // Santander
        $snippets[] = array('frontend/payment_heidelpay/success','de','PrepaymentSanText','Bitte &uuml;berweisen Sie den Betrag von <strong>{AMOUNT} {CURRENCY}</strong> mit Zahlungsziel innerhalb von 30 Tagen auf folgendes Konto: Land: {CONNECTOR_ACCOUNT_COUNTRY} Kontoinhaber: {CONNECTOR_ACCOUNT_HOLDER} Konto-Nr.: {CONNECTOR_ACCOUNT_NUMBER} Bankleitzahl: {CONNECTOR_ACCOUNT_BANK} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{CONNECTOR_ACCOUNT_USAGE}</strong>');
        $snippets[] = array('frontend/payment_heidelpay/success','en','PrepaymentSanText','Please transfer the amount of <strong>{AMOUNT} {CURRENCY}</strong> to the following account with term of payment within 30 days: Country: {CONNECTOR_ACCOUNT_COUNTRY} Account holder: {CONNECTOR_ACCOUNT_HOLDER} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Please use the following identifcation number as payment reference: <strong>{CONNECTOR_ACCOUNT_USAGE}</strong>');
        // Payolution
        $snippets[] = array('frontend/payment_heidelpay/success','de','PrepaymentIvpdText','Bitte &uuml;berweisen Sie den Betrag von <strong>{AMOUNT} {CURRENCY}</strong> mit Zahlungsziel innerhalb von 30 Tagen auf folgendes Konto: Land: {CONNECTOR_ACCOUNT_COUNTRY} Kontoinhaber: {CONNECTOR_ACCOUNT_HOLDER} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Geben Sie als Verwendungszweck bitte ausschlie&szlig;lich diese Identifikationsnummer an: <strong>{CONNECTOR_ACCOUNT_USAGE}</strong>');
        $snippets[] = array('frontend/payment_heidelpay/success','en','PrepaymentIvpdText','Please transfer the amount of <strong>{AMOUNT} {CURRENCY}</strong> to the following account with term of payment within 30 days: Country: {CONNECTOR_ACCOUNT_COUNTRY} Account holder: {CONNECTOR_ACCOUNT_HOLDER} Account No: {CONNECTOR_ACCOUNT_NUMBER} Bank Code: {CONNECTOR_ACCOUNT_BANK} IBAN: {CONNECTOR_ACCOUNT_IBAN} BIC: {CONNECTOR_ACCOUNT_BIC} Please use the following identifcation number as payment reference: <strong>{CONNECTOR_ACCOUNT_USAGE}</strong>');


		
		$snippets[] = array('frontend/payment_heidelpay/success','de','BarpayText','<center><a href=\"{CRITERION_BARPAY_PAYCODE_URL}\" target=\"_blank\" class=\"button-right large\">Klicken Sie hier um Ihren Barcode runterzuladen</a></center> {BARPAY_PAYCODE_URL} \<br /><br />'
				.'Drucken Sie den Barcode aus oder speichern Sie diesen auf Ihrem mobilen Endger&auml;t. '
				.'Gehen Sie nun zu einer Kasse der <b>18.000 Akzeptanzstellen in Deutschland</b> und '
				.'bezahlen Sie ganz einfach in bar. <br/><br/>'
				.'In dem Augenblick, wenn der Rechnungsbetrag beglichen wird, erh&auml;lt der '
				.'Online-H&auml;ndler die Information &uuml;ber den Zahlungseingang.'
				.'Die bestellte Ware oder Dienstleistung geht umgehend in den Versand.');
		$snippets[] = array('frontend/payment_heidelpay/success','de','mailSubject','Zahldaten zu Ihrer Bestellung {ORDERNR} bei {config name=shopName}');
		$snippets[] = array('frontend/payment_heidelpay/success','en','mailSubject','Payment data for your order {ORDERNR} at {config name=shopName}');
		$snippets[] = array('frontend/payment_heidelpay/success','de','mailContent','Sehr geehrter Kunde,\r\n\r\nvielen Dank f&uuml;r Ihre Bestellung in userem Shop. Bitte nutzen Sie zum Zahlen Ihrer Bestellung folgende Bezahldaten.\r\n\r\nBetrag: {AMOUNT} {CURRENCY}\r\nKontoinhaber: {CONNECTOR_ACCOUNT_HOLDER}\r\nKonto-Nr.: {CONNECTOR_ACCOUNT_NUMBER}\r\nBankleitzahl: {CONNECTOR_ACCOUNT_BANK}\r\nIBAN: {CONNECTOR_ACCOUNT_IBAN}\r\nBIC: {CONNECTOR_ACCOUNT_BIC}\r\n\r\nUm eine schnelle Bearbeitung gewhrleisten zu knnen, geben Sie bitte als Verwendungszweck nur diese Nummer an.\r\nVerwendungszweck: {IDENTIFICATION_SHORTID}\r\n\r\nVielen Dank\r\nMit freundlichen Gren\r\n\r\nIhr Schopbeteiber');
		$snippets[] = array('frontend/payment_heidelpay/success','en','mailContent','Dear Customer,\r\n\r\nthank you for your order. Please use the following transaction data to pay your order.\r\n\r\nAmount: {AMOUNT} {CURRENCY}\r\nAccount holder: {CONNECTOR_ACCOUNT_HOLDER}\r\nAccount No.: {CONNECTOR_ACCOUNT_NUMBER}\r\nBank Code: {CONNECTOR_ACCOUNT_BANK}\r\nIBAN: {CONNECTOR_ACCOUNT_IBAN}\r\nBIC: {CONNECTOR_ACCOUNT_BIC}\r\n\r\nTo speed up the shipping, please use this number identification number as a descriptor.\r\n\r\nDescriptor: {IDENTIFICATION_SHORTID}\r\n\r\nThank you.\r\n\r\nKind regards\r\n\r\nYour shop owner');

		$snippets[] = array('frontend/checkout/finish','de','accountIdent','
			Der Betrag {$smarty.session.Shopware.sOrderVariables->accountAmount} {$smarty.session.Shopware.sOrderVariables->accountCurrency} wird in den n&auml;chsten Tagen von folgendem Konto abgebucht:<br /><br />
			IBAN: {$smarty.session.Shopware.sOrderVariables->accountIban}<br />
			BIC: {$smarty.session.Shopware.sOrderVariables->accountBic}<br /><br />
			Die Abbuchung enth&auml;lt die Mandatsreferenz-ID: {$smarty.session.Shopware.sOrderVariables->accountIdent}');
		$snippets[] = array('frontend/checkout/finish','en','accountIdent','
			The amount of {$smarty.session.Shopware.sOrderVariables->accountAmount} {$smarty.session.Shopware.sOrderVariables->accountCurrency} will be debited from this account within the next days:<br /><br />
			IBAN: {$smarty.session.Shopware.sOrderVariables->accountIban}<br />
			BIC: {$smarty.session.Shopware.sOrderVariables->accountBic}<br /><br />
			The booking contains the mandate reference ID: {$smarty.session.Shopware.sOrderVariables->accountIdent}');
		$snippets[] = array('frontend/checkout/finish','de','identCreditorId','und die Gl&auml;ubiger ID: {$smarty.session.Shopware.sOrderVariables->identCreditorId}');
		$snippets[] = array('frontend/checkout/finish','en','identCreditorId','and the creditor identifier: {$smarty.session.Shopware.sOrderVariables->identCreditorId}');
		$snippets[] = array('frontend/checkout/finish','de','accountFunds','Bitte sorgen Sie f&uuml;r ausreichende Deckung auf dem entsprechenden Konto.');
		$snippets[] = array('frontend/checkout/finish','en','accountFunds','Please ensure that there will be sufficient funds on the corresponding account.');

		$snippets[] = array('frontend/payment_heidelpay/recurring','de','PaymentTitle','Zahlung durchf&uuml;hren');
		$snippets[] = array('frontend/payment_heidelpay/recurring','en','PaymentTitle','accomplish payment');
		$snippets[] = array('frontend/payment_heidelpay/recurring','de','PaymentLinkChange','Zahlungsart &auml;ndern');
		$snippets[] = array('frontend/payment_heidelpay/recurring','en','PaymentLinkChange','change payment method');

		$snippets[] = array('frontend/register/hp_payment','de','hp_cardBrand','Kartentyp');
		$snippets[] = array('frontend/register/hp_payment','en','hp_cardBrand','Card Brand');
		$snippets[] = array('frontend/register/hp_payment','de','hp_eps_bankBrand','Bank ausw&auml;hlen');
		$snippets[] = array('frontend/register/hp_payment','en','hp_eps_bankBrand','Choose your Bank');
		$snippets[] = array('frontend/register/hp_payment','de','hp_eps_BankHolder','Kontoinhaber');
		$snippets[] = array('frontend/register/hp_payment','en','hp_eps_BankHolder','Account holder');

		$snippets[] = array('frontend/register/hp_payment','de','hp_cardNumber','Kartennr.');
		$snippets[] = array('frontend/register/hp_payment','en','hp_cardNumber','Card No.');
		$snippets[] = array('frontend/register/hp_payment','de','hp_cardHolder','Karteninhaber');
		$snippets[] = array('frontend/register/hp_payment','en','hp_cardHolder','Card holder');
		$snippets[] = array('frontend/register/hp_payment','de','hp_AccountHolder','Kontoinhaber');
		$snippets[] = array('frontend/register/hp_payment','en','hp_AccountHolder','Account holder');
		$snippets[] = array('frontend/register/hp_payment','de','hp_cardExpiry','Ablaufdatum');
		$snippets[] = array('frontend/register/hp_payment','en','hp_cardExpiry','Expiry Date');
		$snippets[] = array('frontend/register/hp_payment','de','hp_cardVeri','Pr&uuml;fnummer');
		$snippets[] = array('frontend/register/hp_payment','en','hp_cardVeri','Verification Number');
		$snippets[] = array('frontend/register/hp_payment','de','hp_mail','PayPal-E-Mailadresse ');
		$snippets[] = array('frontend/register/hp_payment','en','hp_mail','PayPal-E-Mail-adress ');
		$snippets[] = array('frontend/register/hp_payment','de','hp_day','Tag');
		$snippets[] = array('frontend/register/hp_payment','en','hp_day','Day');
		$snippets[] = array('frontend/register/hp_payment','de','hp_month','Monat');
		$snippets[] = array('frontend/register/hp_payment','en','hp_month','Month');
		$snippets[] = array('frontend/register/hp_payment','de','hp_year','Jahr');
		$snippets[] = array('frontend/register/hp_payment','en','hp_year','Year');
		$snippets[] = array('frontend/register/hp_payment','de','hp_account','Kontonummer');
		$snippets[] = array('frontend/register/hp_payment','en','hp_account','Account Nr.');
		$snippets[] = array('frontend/register/hp_payment','de','hp_bank','Bankleitzahl');
		$snippets[] = array('frontend/register/hp_payment','en','hp_bank','Bank Nr.');
		$snippets[] = array('frontend/register/hp_payment','de','hp_iban','IBAN');
		$snippets[] = array('frontend/register/hp_payment','en','hp_iban','IBAN');
		$snippets[] = array('frontend/register/hp_payment','de','hp_bic','BIC');
		$snippets[] = array('frontend/register/hp_payment','en','hp_bic','BIC');
		$snippets[] = array('frontend/register/hp_payment','de','hp_ktoOrIban','KTO bzw. IBAN');
		$snippets[] = array('frontend/register/hp_payment','en','hp_ktoOrIban','Account Nr. or IBAN');
		$snippets[] = array('frontend/register/hp_payment','de','hp_blzOrBic','BLZ bzw. BIC');
		$snippets[] = array('frontend/register/hp_payment','en','hp_blzOrBic','Bank Nr. or BIC');
		$snippets[] = array('frontend/register/hp_payment','de','hp_accInfo','Kontoinfo');
		$snippets[] = array('frontend/register/hp_payment','en','hp_accInfo','Account information');
		$snippets[] = array('frontend/register/hp_payment','de','hp_accSalutation','Anrede');
		$snippets[] = array('frontend/register/hp_payment','en','hp_accSalutation','Salutation');

        $snippets[] = array('frontend/register/hp_payment','de','hp_valueDay','Tag');
        $snippets[] = array('frontend/register/hp_payment','en','hp_valueDay','day');
        $snippets[] = array('frontend/register/hp_payment','de','hp_valueMonth','Monat');
        $snippets[] = array('frontend/register/hp_payment','en','hp_valueMonth','month');
        $snippets[] = array('frontend/register/hp_payment','de','hp_valueYear','Jahr');
        $snippets[] = array('frontend/register/hp_payment','en','hp_valueYear','Year');
        //added for Santander
        $snippets[] = array('frontend/register/hp_payment','de','hp_accSal_gender','bitte wählen');
        $snippets[] = array('frontend/register/hp_payment','en','hp_accSal_gender','please choose');
        $snippets[] = array('frontend/register/hp_payment','de','hp_accSal_mr','Herr');
		$snippets[] = array('frontend/register/hp_payment','en','hp_accSal_mr','Mr');
		$snippets[] = array('frontend/register/hp_payment','de','hp_accSal_ms','Frau');
		$snippets[] = array('frontend/register/hp_payment','en','hp_accSal_ms','Ms');
        $snippets[] = array('frontend/register/hp_payment','de','hp_accSal_unknown','Bitte wählen');
        $snippets[] = array('frontend/register/hp_payment','en','hp_accSal_unknown','please choose');
		$snippets[] = array('frontend/register/hp_payment','de','hp_RegisterLabelBirthday','Geburtsdatum');
		$snippets[] = array('frontend/register/hp_payment','en','hp_RegisterLabelBirthday','Date of birth');
        $snippets[] = array('frontend/register/hp_payment','de','hp_sanAdvPermission','Werbezustimmung');
        $snippets[] = array('frontend/register/hp_payment','en','hp_sanAdvPermission','Permission for advertising');
        $snippets[] = array('frontend/register/hp_payment','de','hp_sanPrivacyPolicy','Datenschutzerklärung');
		$snippets[] = array('frontend/register/hp_payment','en','hp_sanPrivacyPolicy','privacy policy');

		$snippets[] = array('frontend/register/hp_payment','de','hp_RegisterPhone','telefoonnummer');
		$snippets[] = array('frontend/register/hp_payment','en','hp_RegisterPhone','phonenumber');
		$snippets[] = array('frontend/register/hp_payment','nl','hp_RegisterPhone','telefoonnummer');

		$snippets[] = array('frontend/register/hp_payment','de','hp_sepa_classic','Kontonr. &amp; Bankleitzahl');
		$snippets[] = array('frontend/register/hp_payment','en','hp_sepa_classic','Account no. &amp; Bank no.');
		$snippets[] = array('frontend/register/hp_payment','de','hp_sepa_iban','IBAN');
		$snippets[] = array('frontend/register/hp_payment','en','hp_sepa_iban','IBAN');
		$snippets[] = array('frontend/register/hp_payment','de','hp_accHolder','Inhaber');
		$snippets[] = array('frontend/register/hp_payment','en','hp_accHolder','Owner');
		$snippets[] = array('frontend/register/hp_payment','de','hp_country','Land');
		$snippets[] = array('frontend/register/hp_payment','en','hp_country','Country');
		$snippets[] = array('frontend/register/hp_payment','de','hp_reuse','Hallo {$user.billingaddress.firstname} {$user.billingaddress.lastname}, beim letzten Besuch haben Sie folgende Daten verwendet.<br />M&ouml;chten Sie diese wieder verwenden?<br /><br />');
		$snippets[] = array('frontend/register/hp_payment','en','hp_reuse','Dear {$user.billingaddress.firstname} {$user.billingaddress.lastname}, you set the following data.<br />Do you want to use it now?<br /><br />');
		$snippets[] = array('frontend/register/hp_payment','de','hp_reenter','Nein, Ich m&ouml;chte meine Daten erneut eingeben.');
		$snippets[] = array('frontend/register/hp_payment','en','hp_reenter','No, I want to rerenter my data.');
		$snippets[] = array('frontend/register/hp_payment','de','hp_enter','&#x25BC; Zahldaten eingeben &#x25BC;');
		$snippets[] = array('frontend/register/hp_payment','en','hp_enter','&#x25BC; Enter payment information &#x25BC;');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorCrdNr','Die Kartennummer ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorCrdNr','Card number is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorCvv','Die Prüfnummer ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorCvv','The card validation code is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorIban','Die IBAN ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorIban','The IBAN code is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorBic','Der BIC ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorBic','The BIC is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorAccount','Die Kontonummer ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorAccount','The account number is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorBank','Die Bankleitzahl ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorBank','The bank no. is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorExp','Das Ablaufdatum ist nicht korrekt');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorExp','The expiry date is invalid');
		$snippets[] = array('frontend/register/hp_payment','de','ErrorDob','Sie müssen über 18 Jahre alt sein um diese Zahlart zu nutzen.');
		$snippets[] = array('frontend/register/hp_payment','en','ErrorDob','You need to be 18 and over to use this payment type.');
        //added for Santander
        $snippets[] = array('frontend/register/hp_payment','de','ErrorSalut','Bitte geben Sie Ihre Anrede an.');
        $snippets[] = array('frontend/register/hp_payment','en','ErrorSalut','You need to enter a salutation to use this payment type.');
        $snippets[] = array('frontend/register/hp_payment','de','ErrorCb','Sie müssen die Datenschutzbestimmungen akzeptieren um diese Zahlart nutzen zu können.');
        $snippets[] = array('frontend/register/hp_payment','en','ErrorCb','You have to accept the privacy policy to use this payment type.');
        $snippets[] = array('frontend/register/hp_payment','de','ErrorPhone','Sie m&uuml;ssen Ihre Telefonnummer eingeben um diese Zahlart nutzen zu k&ouml;nnen.');
        $snippets[] = array('frontend/register/hp_payment','en','ErrorPhone','You have to enter your phonenumber to use this payment type.');
        $snippets[] = array('frontend/register/hp_payment','de','hp_payNow','Jetzt bezahlen');
		$snippets[] = array('frontend/register/hp_payment','en','hp_payNow','Pay now');
		$snippets[] = array('frontend/register/hp_payment','de','hp_cancelPay','Abbrechen');
		$snippets[] = array('frontend/register/hp_payment','en','hp_cancelPay','Cancel');
		$snippets[] = array('frontend/register/hp_payment','de','hp_selectedPayData','Gew&auml;hlte Zahldaten');
		$snippets[] = array('frontend/register/hp_payment','en','hp_selectedPayData','Selected payment data');
		$snippets[] = array('frontend/register/hp_payment','de','hp_paypalInfo','Wenn Sie Ihre E-Mail Adresse registieren m&ouml;chten werden Sie zur Best&auml;tigung Ihrer Daten auf die Seite von PayPal umgeleitet.');
		$snippets[] = array('frontend/register/hp_payment','en','hp_paypalInfo',"You'll be redirectet to PayPal to confirm your data, if you want to register it.");
		$snippets[] = array('frontend/register/hp_payment','de','hp_moreMpa','über MasterPass');
		$snippets[] = array('frontend/register/hp_payment','en','hp_moreMpa','about MasterPass');
        $snippets[] = array('frontend/register/hp_payment','en','hp_interest','accrued interest');
        $snippets[] = array('frontend/register/hp_payment','de','hp_interest','Zinsen f&uuml;r Ratenkauf');
        $snippets[] = array('frontend/register/hp_payment','en','hp_totalInterest','total amount with interest');
        $snippets[] = array('frontend/register/hp_payment','de','hp_totalInterest','Gesamtsumme mit Zinsen');


        $snippets[] = array('backend/heidelBackend','de','action','Aktion');
		$snippets[] = array('backend/heidelBackend','en','action','Action');
		$snippets[] = array('backend/heidelBackend','de','amount','Betrag');
		$snippets[] = array('backend/heidelBackend','en','amount','Amount');
		$snippets[] = array('backend/heidelBackend','de','date','Datum');
		$snippets[] = array('backend/heidelBackend','en','date','Date');
		$snippets[] = array('backend/heidelBackend','de','result','Erg.');
		$snippets[] = array('backend/heidelBackend','en','result','Result');
		$snippets[] = array('backend/heidelBackend','de','shortid','ShortID');
		$snippets[] = array('backend/heidelBackend','en','shortid','ShortID');
		$snippets[] = array('backend/heidelBackend','de','type','Typ');
		$snippets[] = array('backend/heidelBackend','en','type','Type');
		$snippets[] = array('backend/heidelBackend','de','currency','Währung');
		$snippets[] = array('backend/heidelBackend','en','currency','Currency');
		$snippets[] = array('backend/heidelBackend','de','total','Summe');
		$snippets[] = array('backend/heidelBackend','en','total','Total');
		$snippets[] = array('backend/heidelBackend','de','db','Debit');
		$snippets[] = array('backend/heidelBackend','en','db','Debit');
		$snippets[] = array('backend/heidelBackend','de','rb','Rebill');
		$snippets[] = array('backend/heidelBackend','en','rb','Rebill');
		$snippets[] = array('backend/heidelBackend','de','pa','Reservierung');
		$snippets[] = array('backend/heidelBackend','en','pa','Reservation');
		$snippets[] = array('backend/heidelBackend','de','cp','Capture');
		$snippets[] = array('backend/heidelBackend','en','cp','Capture');
		$snippets[] = array('backend/heidelBackend','de','rc','Receipt');
		$snippets[] = array('backend/heidelBackend','en','rc','Receipt');
		$snippets[] = array('backend/heidelBackend','de','rv','Reversal');
		$snippets[] = array('backend/heidelBackend','en','rv','Reversal');
		$snippets[] = array('backend/heidelBackend','de','rf','Refund');
		$snippets[] = array('backend/heidelBackend','en','rf','Refund');
		$snippets[] = array('backend/heidelBackend','de','cb','Chargeback');
		$snippets[] = array('backend/heidelBackend','en','cb','Chargeback');
		$snippets[] = array('backend/heidelBackend','de','fi','Finalize');
        $snippets[] = array('backend/heidelBackend','en','fi','Finalize');
        $snippets[] = array('backend/heidelBackend','de','in','Initialisierung');
        $snippets[] = array('backend/heidelBackend','en','in','Initialize');
		$snippets[] = array('backend/heidelBackend','de','pay','Zahlart');
		$snippets[] = array('backend/heidelBackend','en','pay','Payment');
		$snippets[] = array('backend/heidelBackend','de','refreshPage','Übersicht aktualisieren');
		$snippets[] = array('backend/heidelBackend','en','refreshPage','refresh transaction list');
		$snippets[] = array('backend/heidelBackend','de','noTrans','Für diese Transaktion liegen keine Push-Informationen vor.');
		$snippets[] = array('backend/heidelBackend','en','noTrans','For this transaction is no push-information available.');
		$snippets[] = array('backend/heidelBackend','de','note','<strong>Hinweis:</strong> Ein "Refund" ist nur auf den ersten "Capture", "Receipt" oder "Debit" möglich.');
		$snippets[] = array('backend/heidelBackend','en','note','<strong>Note:</strong> You can only "refund" on the first "capture", "receipt" or "debit"');
		$snippets[] = array('backend/heidelBackend','de','noAction','<strong>Hinweis:</strong> Keine Aktion m&ouml;glich - Im Logfile finden Sie weitere Informationen.');
		$snippets[] = array('backend/heidelBackend','en','noAction','<strong>Note:</strong> No action possible - check logfile for further information');

		return $snippets;
	}

	/**
	 * Method to get billsafe request from database
	 * @return
	 */
	private function getBillSafeRequestFromDB($tempId){
		try{
			$sql = 'SELECT `Request` FROM `s_plugin_hgw_billsafe` WHERE `temporaryID` = ?';
			$dat = Shopware()->Db()->fetchOne($sql, $tempId);

			return unserialize(urldecode($dat));
		}catch(Exception $e){
			$this->Logging('getBillSafeRequestFromDB | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create database table for transactions
	 * @return
	 */
	public function createTransactionsTable(){
		try{
			$sql = "
			CREATE TABLE IF NOT EXISTS `s_plugin_hgw_transactions` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				`payment_method` varchar(2) NOT NULL COMMENT 'Payment Method',
				`payment_type` varchar(2) NOT NULL COMMENT 'Payment Type',
				`transactionid` varchar(50) NOT NULL COMMENT 'Transaction ID',
				`uniqueid` varchar(32) NOT NULL COMMENT 'Unique ID',
				`shortid` varchar(14) NOT NULL COMMENT 'Short ID',
				`result` varchar(3) NOT NULL COMMENT 'Result',
				`statuscode` smallint(5) unsigned NOT NULL COMMENT 'Status Code',
				`return` varchar(255) NOT NULL COMMENT 'Return',
				`returncode` varchar(12) NOT NULL COMMENT 'Return Code',
				`jsonresponse` blob NOT NULL COMMENT 'JSON Response',
				`datetime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Datetime',
				`source` varchar(100) NOT NULL COMMENT 'Source',
				`storeid` smallint(3) NOT NULL COMMENT 'Store ID',
				PRIMARY KEY (`id`),
				KEY `IDX_HEIDELPAY_TRANSACTION_UNIQUEID` (`uniqueid`),
				KEY `IDX_HEIDELPAY_TRANSACTION_TRANSACTIONID` (`transactionid`),
				KEY `IDX_HEIDELPAY_TRANSACTION_RETURNCODE` (`returncode`),
				KEY `IDX_HEIDELPAY_TRANSACTION_SOURCE` (`source`),
				UNIQUE KEY `IDX_HEIDELPAY_TRANSACTIONID_UNIQUEID` (`transactionid`, `uniqueid`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='heidelpay_transaction' AUTO_INCREMENT=0
			";

			return Shopware()->Db()->query($sql);
		}catch(Exception $e){
			$this->Logging('createTransactionsTable | '.$e->getMessage());
			return;
		}
	}

	var $billsafeDesc = 'Kaufen Sie jetzt auf Rechnung und begutachten Sie Ihre Eink&auml;ufe in Ruhe bevor Sie bezahlen.
		<br/><br/><a title="Ihre Vorteile" href="http://www.billsafe.de/special/payment-info" target="_blank"><img src="https://images.billsafe.de/image/image/id/191997712fbe" style="border:0"/></a>';
	var $barpayDesc ='Sicher, schnell und ohne Geb&uuml;hren: mit BarPay zahlen Sie Internet-Eink&auml;ufe mit Bargeld. Ohne Anmeldung. Ohne Kreditkarte. Ohne Kontodetails.
		<br /><br />Nach Auswahl von BarPay &uuml;bermittelt Ihnen Ihr Online-H&auml;ndler einen individuellen Barcode per E-Mail oder zum Download auf Ihren Computer. Diesen k&ouml;nnen Sie ausdrucken und in &uuml;ber 18.000 BarPay-Akzeptanzstellen bezahlen. Der Zahlungseingang wird dem Online-H&auml;ndler in Echtzeit &uuml;bermittelt, und die bestellte Ware geht umgehend in den Versand. <br /><br />
		<a href="http://www.barpay.de/info/" onclick="window.open(this.href,\'Popup\',\'width=580,height=550,scrollbars=no\');return false;"><img src="engine/Shopware/Plugins/Default/Frontend/HeidelGateway/img/BarPay.jpg" style="border:0"/></a>';

	/**
	 * Method to create the prepayment mail
	 */
	private function installPrepaymentMail(){
		try{
			$sql = '
			INSERT INTO `s_core_config_mails` (`id`, `stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE frommail= ?, fromname= ?, subject= ?, content= ?, contentHTML= ?, ishtml= ?, attachment= ?, mailtype= ?, context= ?';

			$prms_id 		= NULL;
			$prms_stateId 	= NULL;
			$prms_name 		= 'prepaymentHeidelpay';
			$prms_frommail 	= '{config name=mail}';
			$prms_fromname 	= '{config name=shopName}';
			$prms_subject 	= 'Zahldaten zu Ihrer Bestellung {$ordernumber} bei {config name=shopName}';
			$prms_content 	= 'Sehr geehrter Kunde,

vielen Dank fuer Ihre Bestellung in unserem Shop.

Bitte nutzen Sie zum Zahlen Ihrer Bestellung folgende Bezahldaten.

Betrag: {$AMOUNT} {$CURRENCY}
Kontoinhaber: {$CONNECTOR_ACCOUNT_HOLDER}
Konto-Nr.: {$CONNECTOR_ACCOUNT_NUMBER}
Bankleitzahl: {$CONNECTOR_ACCOUNT_BANK}
IBAN: {$CONNECTOR_ACCOUNT_IBAN}
BIC: {$CONNECTOR_ACCOUNT_BIC}

Um eine schnelle Bearbeitung gewaehrleisten zu koennen, geben Sie bitte als Verwendungszweck nur diese Nummer an.
Verwendungszweck: {$IDENTIFICATION_SHORTID}


Vielen Dank

Mit freundlichen Gruessen

{config name=shopName}
{config name=address}';
			$prms_contentHTML	= 'Sehr geehrter Kunde,<br/><br/>vielen Dank f&uuml;r Ihre Bestellung in unserem Shop.<br><br/>Bitte nutzen Sie zum Zahlen Ihrer Bestellung folgende Bezahldaten.<br/><br/>Betrag: {$AMOUNT} {$CURRENCY}<br/>Kontoinhaber: {$CONNECTOR_ACCOUNT_HOLDER}<br/>Konto-Nr.: {$CONNECTOR_ACCOUNT_NUMBER}<br/>Bankleitzahl: {$CONNECTOR_ACCOUNT_BANK}<br/>IBAN: {$CONNECTOR_ACCOUNT_IBAN}<br/>BIC: {$CONNECTOR_ACCOUNT_BIC}<br/><br/>Um eine schnelle Bearbeitung gew&auml;hrleisten zu k&ouml;nnen, geben Sie bitte als Verwendungszweck nur diese Nummer an.<br/>Verwendungszweck: {$IDENTIFICATION_SHORTID}<br/><br/><br/>Vielen Dank<br/><br/>Mit freundlichen Gr&uuml;&szlig;en<br/><br/>{config name=shopName}<br/>{config name=address}';
			$prms_ishtml 		= 1;
			$prms_attachment 	= '';
			$prms_mailtype 		= 1;
			$prms_context 		= 'a:10:{s:6:"AMOUNT";s:5:"73.98";s:8:"CURRENCY";s:3:"EUR";s:25:"CONNECTOR_ACCOUNT_COUNTRY";s:3:"DE\n";s:24:"CONNECTOR_ACCOUNT_HOLDER";s:25:"heidelpay - TEST Inhaber\n";s:24:"CONNECTOR_ACCOUNT_NUMBER";s:11:"1234567890\n";s:22:"CONNECTOR_ACCOUNT_BANK";s:9:"10000000\n";s:22:"CONNECTOR_ACCOUNT_IBAN";s:23:"DE01000000001234567890\n";s:21:"CONNECTOR_ACCOUNT_BIC";s:12:"HEIDELPAYXY\n";s:22:"IDENTIFICATION_SHORTID";s:18:"\n\n2311.5548.6334\n\n";s:11:"ordernumber";s:5:"20028";}';

			$params = array($prms_id, $prms_stateId, $prms_name, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context);

			return Shopware()->Db()->query($sql, $params);
		}catch(Exception $e){
			$this->Logging('installPrepaymentMail | '.$e->getMessage());
			return;
		}
	}

    /**
     * Method to create the prepayment mail
     */
    private function installInvoiceSanMail(){
        try{
            $sql = '
			INSERT INTO `s_core_config_mails` (`id`, `stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE frommail= ?, fromname= ?, subject= ?, content= ?, contentHTML= ?, ishtml= ?, attachment= ?, mailtype= ?, context= ?';


            $prms_id 		= NULL;
            $prms_stateId 	= NULL;
            $prms_name 		= 'invoiceSanHeidelpay';
            $prms_frommail 	= '{config name=mail}';
            $prms_fromname 	= '{config name=shopName}';
            $prms_subject 	= 'Zahldaten zu Ihrer Bestellung {$ordernumber} bei {config name=shopName}';
            $prms_content 	= 'Sehr geehrter Kunde,

vielen Dank fuer Ihre Bestellung in userem Shop.

Bezahlen Sie bequem nach Warenerhalt. Alle Zahlungsdetails können Sie später auch Ihrer Rechnung entnehmen. Ab Rechnungsdatum haben Sie 30 Tage Zeit, diese zu begleichen.

Zahlungsziel: 30 Tage nach Warenerhalt
Betrag: {$AMOUNT} {$CURRENCY}
Kontoinhaber: {$CONNECTOR_ACCOUNT_HOLDER}
Konto-Nr.: {$CONNECTOR_ACCOUNT_NUMBER}
Bankleitzahl: {$CONNECTOR_ACCOUNT_BANK}
IBAN: {$CONNECTOR_ACCOUNT_IBAN}
BIC: {$CONNECTOR_ACCOUNT_BIC}

Um eine schnelle Bearbeitung gewaehrleisten zu koennen, geben Sie bitte als Verwendungszweck nur diese Nummer an.
Verwendungszweck: {$CONNECTOR_ACCOUNT_USAGE}


Vielen Dank

Mit freundlichen Gruessen

{config name=shopName}
{config name=address}';
            $prms_contentHTML	= 'Sehr geehrter Kunde,<br/><br/>vielen Dank f&uuml;r Ihre Bestellung in userem Shop.<br><br/>Bezahlen Sie bequem nach Warenerhalt. Alle Zahlungsdetails können Sie später auch Ihrer Rechnung entnehmen. Ab Rechnungsdatum haben Sie 30 Tage Zeit, diese zu begleichen.<br/><br/>Betrag: {$AMOUNT} {$CURRENCY}<br/>Kontoinhaber: {$CONNECTOR_ACCOUNT_HOLDER}<br/>Konto-Nr.: {$CONNECTOR_ACCOUNT_NUMBER}<br/>Bankleitzahl: {$CONNECTOR_ACCOUNT_BANK}<br/>IBAN: {$CONNECTOR_ACCOUNT_IBAN}<br/>BIC: {$CONNECTOR_ACCOUNT_BIC}<br/><br/>Um eine schnelle Bearbeitung gew&auml;hrleisten zu k&ouml;nnen, geben Sie bitte als Verwendungszweck nur diese Nummer an.<br/>Verwendungszweck: {$CONNECTOR_ACCOUNT_USAGE}<br/><br/><br/>Vielen Dank<br/><br/>Mit freundlichen Gr&uuml;&szlig;en<br/><br/>{config name=shopName}<br/>{config name=address}';
            $prms_ishtml 		= 1;
            $prms_attachment 	= '';
            $prms_mailtype 		= 1;
            $prms_context 		= 'a:10:{s:6:"AMOUNT";s:5:"73.98";s:8:"CURRENCY";s:3:"EUR";s:25:"CONNECTOR_ACCOUNT_COUNTRY";s:3:"DE\n";s:24:"CONNECTOR_ACCOUNT_HOLDER";s:25:"heidelpay - TEST Inhaber\n";s:24:"CONNECTOR_ACCOUNT_NUMBER";s:11:"1234567890\n";s:22:"CONNECTOR_ACCOUNT_BANK";s:9:"10000000\n";s:22:"CONNECTOR_ACCOUNT_IBAN";s:23:"DE01000000001234567890\n";s:21:"CONNECTOR_ACCOUNT_BIC";s:12:"HEIDELPAYXY\n";s:22:"IDENTIFICATION_SHORTID";s:18:"\n\n2311.5548.6334\n\n";s:11:"ordernumber";s:5:"20028";}';

            $params = array($prms_id, $prms_stateId, $prms_name, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context);

            return Shopware()->Db()->query($sql, $params);
        }catch(Exception $e){
            $this->Logging('installPrepaymentMail | '.$e->getMessage());
            return;
        }
    }

    /**
     * Method to create the prepayment mail
     */
    private function installInvoiceIvpdMail(){
        try{
            $sql = '
			INSERT INTO `s_core_config_mails` (`id`, `stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE frommail= ?, fromname= ?, subject= ?, content= ?, contentHTML= ?, ishtml= ?, attachment= ?, mailtype= ?, context= ?';

            $prms_id 		= NULL;
            $prms_stateId 	= NULL;
            $prms_name 		= 'invoiceIvpdHeidelpay';
            $prms_frommail 	= '{config name=mail}';
            $prms_fromname 	= '{config name=shopName}';
            $prms_subject 	= 'Zahldaten zu Ihrer Bestellung {$ordernumber} bei {config name=shopName}';
            $prms_content 	= 'Sehr geehrter Kunde,

vielen Dank fuer Ihre Bestellung in unserem Shop.

Bitte nutzen Sie zum Bezahlen Ihrer Bestellung folgende Bezahldaten.

Betrag: {$AMOUNT} {$CURRENCY}
Kontoinhaber: {$CONNECTOR_ACCOUNT_HOLDER}
IBAN: {$CONNECTOR_ACCOUNT_IBAN}
BIC: {$CONNECTOR_ACCOUNT_BIC}

Um eine schnelle Bearbeitung gewaehrleisten zu koennen, geben Sie bitte als Verwendungszweck nur diese Nummer an.
Verwendungszweck: {$CONNECTOR_ACCOUNT_USAGE}


Vielen Dank

Mit freundlichen Gruessen

{config name=shopName}
{config name=address}';
            $prms_contentHTML	= 'Sehr geehrter Kunde,<br/><br/>vielen Dank f&uuml;r Ihre Bestellung in unserem Shop.<br><br/>Bitte nutzen Sie zum Zahlen Ihrer Bestellung folgende Bezahldaten.<br/><br/>Betrag: {$AMOUNT} {$CURRENCY}<br/>Kontoinhaber: {$CONNECTOR_ACCOUNT_HOLDER}<br/>IBAN: {$CONNECTOR_ACCOUNT_IBAN}<br/>BIC: {$CONNECTOR_ACCOUNT_BIC}<br/><br/>Um eine schnelle Bearbeitung gew&auml;hrleisten zu k&ouml;nnen, geben Sie bitte als Verwendungszweck nur diese Nummer an.<br/>Verwendungszweck: {$CONNECTOR_ACCOUNT_USAGE}<br/><br/><br/>Vielen Dank<br/><br/>Mit freundlichen Gr&uuml;&szlig;en<br/><br/>{config name=shopName}<br/>{config name=address}';
            $prms_ishtml 		= 1;
            $prms_attachment 	= '';
            $prms_mailtype 		= 1;
            $prms_context 		= 'a:10:{s:6:"AMOUNT";s:5:"73.98";s:8:"CURRENCY";s:3:"EUR";s:25:"CONNECTOR_ACCOUNT_COUNTRY";s:3:"DE\n";s:24:"CONNECTOR_ACCOUNT_HOLDER";s:25:"heidelpay - TEST Inhaber\n";s:24:"CONNECTOR_ACCOUNT_NUMBER";s:11:"1234567890\n";s:22:"CONNECTOR_ACCOUNT_BANK";s:9:"10000000\n";s:22:"CONNECTOR_ACCOUNT_IBAN";s:23:"DE01000000001234567890\n";s:21:"CONNECTOR_ACCOUNT_BIC";s:12:"HEIDELPAYXY\n";s:22:"IDENTIFICATION_SHORTID";s:18:"\n\n2311.5548.6334\n\n";s:11:"ordernumber";s:5:"20028";}';

            $params = array($prms_id, $prms_stateId, $prms_name, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context);

            return Shopware()->Db()->query($sql, $params);
        }catch(Exception $e){
            $this->Logging('installPrepaymentMail | '.$e->getMessage());
            return;
        }
    }

	/**
	 * Method to create the barpay mail
	 */
	private function installBarPayMail(){
		try{
			$sql = '
			INSERT INTO `s_core_config_mails` (`id`, `stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE frommail= ?, fromname= ?, subject= ?, content= ?, contentHTML= ?, ishtml= ?, attachment= ?, mailtype= ?, context= ?';

			$prms_id 					= NULL;
			$prms_stateId 			= NULL;
			$prms_name 				= 'barpayHeidelpay';
			$prms_frommail 		= '{config name=mail}';
			$prms_fromname 		= '{config name=shopName}';
			$prms_subject 			= 'Barcode Download zu Ihrer Bestellung {$ordernumber} bei {config name=shopName}';
			$prms_content 			= 'Sehr geehrter Kunde,

vielen Dank fuer Ihre Bestellung in unserem Shop.

Bitte nutzen Sie zum Zahlen Ihrer Bestellung folgenden Barcode.

{$CRITERION_BARPAY_PAYCODE_URL}

Drucken Sie den Barcode aus oder speichern Sie diesen auf Ihrem mobilen Endgeraet.
Gehen Sie nun zu einer Kasse der 18.000 Akzeptanzstellen in Deutschland und
bezahlen Sie ganz einfach in bar.

In dem Augenblick, wenn der Rechnungsbetrag beglichen wird, erhaelt der
Online-Haendler die Information ueber den Zahlungseingang. Die bestellte Ware oder
Dienstleistung geht umgehend in den Versand.


Vielen Dank

Mit freundlichen Gruessen

{config name=shopName}
{config name=address}';
			$prms_contentHTML	= 'Sehr geehrter Kunde,<br/><br/>vielen Dank f&uuml;r Ihre Bestellung in unserem Shop.<br><br/>Bitte nutzen Sie zum Zahlen Ihrer Bestellung folgenden Barcode.<br/><br/><a href"{$CRITERION_BARPAY_PAYCODE_URL}" target="_blank">{$CRITERION_BARPAY_PAYCODE_URL}</a><br/><br/><p>Drucken Sie den Barcode aus oder speichern Sie diesen auf Ihrem mobilen Endger&auml;t.</p><p>Gehen Sie nun zu einer Kasse der <b>18.000 Akzeptanzstellen in Deutschland</b> undbezahlen Sie ganz einfach in bar. </p><p>In dem Augenblick, wenn der Rechnungsbetrag beglichen wird, erh&auml;lt der Online-H&auml;ndler die Information &uuml;ber den Zahlungseingang. Die bestellte Ware oder Dienstleistung geht umgehend in den Versand.</p><br/><br/>Vielen Dank<br/><br/>Mit freundlichen Gr&uuml;&szlig;en<br/><br/>{config name=shopName}<br/>{config name=address}';
			$prms_ishtml 				= 1;
			$prms_attachment 	= '';
			$prms_mailtype 			= 1;
			$prms_context 			= 'a:2:{s:28:"CRITERION_BARPAY_PAYCODE_URL";s:100:"https://testavis.barpay-system.de/paycodegenerator.php?productid=4260284350017&paycode=0010000102475";s:11:"ordernumber";s:5:"20019";}';

			$params = array($prms_id, $prms_stateId, $prms_name, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context);

			return Shopware()->Db()->query($sql, $params);
		}catch(Exception $e){
			$this->Logging('installBarPayMail | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to create the direct debit mail
	 */
	private function installDirectDebitMail(){
		try{
			$sql = '
			INSERT INTO `s_core_config_mails` (`id`, `stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE frommail= ?, fromname= ?, subject= ?, content= ?, contentHTML= ?, ishtml= ?, attachment= ?, mailtype= ?, context= ?';

			$prms_id 			= NULL;
			$prms_stateId 		= NULL;
			$prms_name 			= 'directdebitHeidelpay';
			$prms_frommail 		= '{config name=mail}';
			$prms_fromname 		= '{config name=shopName}';
			$prms_subject 			= 'Lastschriftdaten zu Ihrer Bestellung {$ordernumber} bei {config name=shopName}';
			$prms_content 			= 'Der Betrag wird in den naechsten Tagen von folgendem Konto abgebucht:
IBAN: {$ACCOUNT_IBAN}
BIC: {$ACCOUNT_BIC}
Die Abbuchung enthaelt die Mandatsreferenz-ID: {$ACCOUNT_IDENT}
und die Glaeubiger ID: {$IDENT_CREDITOR_ID}

Bitte sorgen Sie fuer ausreichende Deckung auf dem entsprechenden Konto.

Vielen Dank

Mit freundlichen Gruessen
{config name=shopName}
{config name=address}';
			$prms_contentHTML	= 'Der Betrag wird in den n&auml;chsten Tagen von folgendem Konto abgebucht:<br/><br/>IBAN: {$ACCOUNT_IBAN}<br/>BIC: {$ACCOUNT_BIC}<br/><br/>Die Abbuchung enth&auml;lt die Mandatsreferenz-ID:{$ACCOUNT_IDENT}<br/>und die Gl&auml;ubiger ID: {$IDENT_CREDITOR_ID}<br/><br/>Bitte sorgen Sie f&uuml;r ausreichende Deckung auf dem entsprechenden Konto.<br/><br/><br/>Vielen Dank<br/><br/>Mit freundlichen Gr&uuml;&szlig;en<br/><br/>{config name=shopName}<br/>{config name=address}';
			$prms_ishtml 				= 1;
			$prms_attachment 	= '';
			$prms_mailtype 			= 1;
			$prms_context 			= 'a:4:{s:12:"ACCOUNT_IBAN";s:22:"DE06000000010203456789";s:11:"ACCOUNT_BIC";s:11:"BSPTDE20XXX";s:13:"ACCOUNT_IDENT";s:14:"1234.5678.9012";s:17:"IDENT_CREDITOR_ID";s:9:"XTEST_IDX";}';

			$params = array($prms_id, $prms_stateId, $prms_name, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context, $prms_frommail, $prms_fromname, $prms_subject, $prms_content, $prms_contentHTML, $prms_ishtml, $prms_attachment, $prms_mailtype, $prms_context);

			return Shopware()->Db()->query($sql, $params);
		}catch(Exception $e){
			$this->Logging('installDirectDebitMail | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to format a number to '0.00'
	 */
	public function formatNumber($value){
		return sprintf('%1.2f', $value);
	}

	/*
	 * Method to save response to database
	 * @param array $data
	 */
	public function saveRes($data){
		// has no try/catch because errors are processed in script
		$payType = substr($data['PAYMENT_CODE'], 0, 2);
		$transType = substr($data['PAYMENT_CODE'], 3, 2);
		if((!isset($data['TRANSACTION_SOURCE'])) || ($data['TRANSACTION_SOURCE'] == '')){ $data['TRANSACTION_SOURCE'] = 'BACKEND'; }
		if((!isset($data['CRITERION_SHOP_ID'])) || ($data['CRITERION_SHOP_ID'] == '')){ /* $data['CRITERION_SHOP_ID'] = '1';*/ Shopware()->Session()->shop; }
		$sql ='
			INSERT INTO `s_plugin_hgw_transactions` (`payment_method`, `payment_type`, `transactionid`, `uniqueid`, `shortid`, `result`, `statuscode`, `return`, `returncode`, `jsonresponse`, `datetime`, `source`, `storeid`)
			VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),?,?)
		';

		$params = array($payType, $transType, $data['IDENTIFICATION_TRANSACTIONID'], $data['IDENTIFICATION_UNIQUEID'], $data['IDENTIFICATION_SHORTID'], $data['PROCESSING_RESULT'], $data['PROCESSING_STATUS_CODE'], $data['PROCESSING_RETURN'], $data['PROCESSING_RETURN_CODE'], json_encode($data), $data['TRANSACTION_SOURCE'], $data['CRITERION_SHOP_ID']);
		try {
			Shopware()->Db()->query($sql, $params);
		} catch (Exception $e) {
			// if entry is in db yet do not write again
			if($e->getPrevious()->errorInfo['1'] != '1062'){
				Shopware()->Plugins()->Frontend()->HeidelGateway()->Logging('saveRes | '.$e->getMessage());
			}
			return;
		}

	}

	/**
	 * Method to get Snippets
	 * used as brige to use getSnippet() from fronend controller in the backend controller
	 * @param string $name
	 * @param string $localeId
	 * @param string $ns - namespace
	 * @param string $shopId
	 * @return string $snippet
	 */
	public function getSnippets($name, $localeId, $ns, $shopId = 1){
		try{
			$file = realpath(dirname(__FILE__)).'/Controllers/Frontend/PaymentHgw.php';
			if(file_exists($file)){
				require_once($file);

				return Shopware_Controllers_Frontend_PaymentHgw::getSnippet($name,$localeId,$ns, $shopId);
			}
		}catch(Exception $e){
			$this->Logging('getSnippets | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get payment id by payment name
	 * @param string $name - payment name
	 * @return string $payId - payment id
	 */
	public function getPaymentIdByName($name){
		try{
			$sql = 'SELECT `id` FROM `s_core_paymentmeans` WHERE `s_core_paymentmeans`.`name` = ?';
			$payId = Shopware()->Db()->fetchOne($sql, $name);

			return $payId;
		}catch(Exception $e){
			$this->Logging('getPaymentIdByName | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get transaction information by transaction type
	 * @param string $transactionId
	 * @param string $transType
	 * @return array $data
	 */
	public function getTransactionByTransType($transactionId, $transType){
		try{
			$sql = 'SELECT * FROM `s_plugin_hgw_transactions` WHERE `transactionid` = ? AND `payment_type` = ?';
			$data = Shopware()->Db()->fetchRow($sql, array($transactionId, strtoupper($transType)));

			return $data;
		}catch(Exception $e){
			$this->Logging('getTransactionByTransType | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method that adds the backend Translation for this Plugin
	 */
	public function addPluginTranslation(){
		try{
			$form = $this->Form();

			$bookingModeDescDE = 'Debit: Die Zahlung wird direkt durchgeführt<br/>Reservation: Die Warenkorbsumme wird für eine gewisse Zeit reserviert und kann mit einer zweiten Transaktion eingezogen werden.<br/>Registration: Die Zahldaten werden gespeichert um sie bei weiteren Bestellungen wiederzuverwenden.';
			$bookingModeDescEN = 'Debit: The payment for the order happens right away<br />Reservation: The basket amout is reserved for a number of days and can be captured in a second step<br />Registration: Payment information is stored to reuse it for further orders.<br/>debit with guarantee: Please consider, that a special contract is needed';

			//contains all form translations
			$translations = array(
					'de_DE' => array(
							'HGW_USER_PW' 			=> array('label' => 'Passwort'),
							'HGW_TRANSACTION_MODE' 	=> array(
									'description' 		=> 'Wenn der Sandbox-Modus aktiv ist, werden alle Transaktionen gegen unser Testsystem gebucht. Wenn nicht, sind alle Transaktionen echte Transaktionen und jede Transaktion ist kostenpflichtig.',
							),
							'HGW_CC_CHANNEL' 		=> array('label' => 'Kreditkarten Channel'),
							'HGW_CC_ABO_CHANNEL' 	=> array(
									'label' 			=> 'Kreditkarten Channel für Abos',
									'description' 		=> 'Channel notwendig für Abonnements (Abo Commerce Plug-In).',
							),
							'HGW_DC_CHANNEL' 		=> array('label' => 'Debitkarten Channel'),
							'HGW_DC_ABO_CHANNEL' 	=> array(
									'label' 			=> 'Debitkarten Channel für Abos',
									'description' 		=> 'Channel notwendig für Abonnements (Abo Commerce Plug-In).',
							),
							'HGW_DD_CHANNEL' 		=> array('label' => 'Lastschrift Channel'),
							'HGW_PP_CHANNEL' 		=> array('label' => 'Vorkasse Channel'),
							'HGW_IV_CHANNEL' 		=> array('label' => 'Rechnung Channel'),
                            'HGW_IVPD_CHANNEL' 		=> array('label' => 'Payolution Rechnungskauf Channel'),
                            'HGW_IVB2B_CHANNEL' 	=> array('label' => 'Rechnungskauf B2B Channel'),
                            'HGW_PAPG_CHANNEL'		=> array('label' => 'Rechnung mit Zahlungssicherung Channel'),
							'HGW_SAN_CHANNEL'		=> array('label' => 'Santander Channel'),
							'HGW_SU_CHANNEL' 		=> array('label' => 'Sofort Channel'),
                            'HGW_HPR_CHANNEL' 		=> array('label' => 'EasyCredit Channel'),
							'HGW_CC_BOOKING_MODE' 	=> array(
									'label' 			=> 'Kreditkarten Buchungsmodus',
									'description' 		=> $bookingModeDescDE,
							),
							'HGW_DC_BOOKING_MODE' 	=> array(
									'label' 			=> 'Debitkarten Buchungsmodus',
									'description' 		=> $bookingModeDescDE,
							),
							'HGW_DD_BOOKING_MODE' 	=> array(
									'label' 			=> 'Lastschrift Buchungsmodus',
									'description' 		=> $bookingModeDescDE,
							),
							'HGW_DD_GUARANTEE_MODE' => array(
									'label' 			=> 'Gesicherte Lastschrift',
									'description' 		=> 'Bitte beachten Sie, dass zur Aktivierung ein gesonderter Vertrag notwendig ist.',
							),
                            'HGW_FACTORING_MODE' => array(
                                'label' 			=> 'heidelpay Factoring Modus aktiv',
                                'description' 		=> 'Bitte beachten Sie, dass zur Aktivierung ein gesonderter Vertrag notwendig ist.',
                            ),
							'HGW_VA_BOOKING_MODE' 	=> array(
									'label' 			=> 'PayPal Buchungsmodus',
									'description' 		=> '<b>Wichtig: Ihr PayPal Accout muss für Referenztransaktionen konfiguriert sein, wenn sie das Registierungs-Feature benutzen wollen.</b>',
							),
							'HGW_MPA_BOOKING_MODE' 	=> array(
									'label' 			=> 'MasterPass booking mode',
									'description' 		=> $bookingModeDescDE,
							),
							'HGW_CHB_STATUS' => array(
									'label' 			=> 'Chargeback Status',
									'description' 		=> 'Dieser Status wird gesetzt bei geplatzten Lastschriften und Kreditkarten Chargebacks.',
							),
							'HGW_MOBILE_CSS' 		=> array('label' => 'CSS für Mobilgeräte aktivieren'),
							'HGW_SECRET' 			=> array('description' => 'Secret, um die Serverantwort zu verifizieren. Nur wenn nötig ändern.'),
//							'HGW_ERRORMAIL' 		=> array('label' => 'Error E-Mail-Adresse'),
							'HGW_IV_MAIL' 			=> array(
									'label' 			=> 'Zahlungsinformationen für Rechnung per Mail senden',
									'description' 		=> 'Zahlungsinformationen für Rechnung, gesicherte Rechnung und Santander in einer zusätzlicher Mail versenden.',
							),
							'HGW_DD_MAIL' 			=> array(
									'label' 			=> 'Zahlungsinformationen für Lastschrift per Mail senden',
									'description' 		=> 'Zahlungsinformationen für Lastschrift in einer zusätzlicher Mail versenden.',
							),
							'HGW_PP_MAIL' 			=> array(
									'label' 			=> 'Zahlungsinformationen für Vorkasse per Mail senden',
									'description' 		=> 'Zahlungsinformationen für Vorkasse in einer zusätzlicher Mail versenden.',
							),
							'HGW_INVOICE_DETAILS' 	=> array(
									'label' 			=> 'Rechnungsdaten senden',
									'description' 		=> 'Rechnungsdaten an Heidelpay übermitteln. Heidelpay generiert eine PDF-Rechnung für den Endkunden. (Zusätzliche Heidelpay Serviceleistungen notwendig)',
							),
							'HGW_IBAN' => array(
									'label' 			=> 'IBAN anzeigen?',
									'description' 		=> 'Soll bei einer Lastschrift IBAN oder Kontonr. / BLZ abgefragt werden?'
							),
							'HGW_SHIPPINGHASH' 		=> array(
									'label' 			=> 'Wiedererkennung mit abweichender Lieferadresse',
									'description' 		=> 'Ist die Wiedererkennung deaktiviert, werden die registrierten Daten verworfen, sollte der Kunde nach der Registrierung seine Lieferanschrift geändert haben.'
							),
							'HGW_HPF_CC_CSS' 		=> array(
									'label' 			=> 'Pfad zum hPF CSS für Kreditkarte',
									'description' 		=> 'Bitte tragen Sie hier, beginnend mit "http(s)://", den absoluten Pfad zum CSS ein. Dieses CSS wird für das Kreditkartenformular verwendet.'
							),
							'HGW_HPF_DC_CSS' 		=> array(
									'label'				=>'Pfad zum hPF CSS für Debitkarte',
									'description' 		=> 'Bitte tragen Sie hier, beginnend mit "http(s)://", den absoluten Pfad zum CSS ein. Dieses CSS wird für das Debitkartenformular verwendet.'
							),
                            'HGW_EASYMINAMOUNT' 		=> array(
                                'label'				=>'Minimumbetrag für Ratenkauf by easyCredit',
                                'description' 		=> 'Bitte tragen Sie hier den mit heidelpay / easyCredit vereinbarten Minimumbetrag für Ihre Transaktionen ein.'
                            ),
                            'HGW_EASYMAXAMOUNT' 		=> array(
                                'label'				=>'Maximumbetrag für Ratenkauf by easyCredit',
                                'description' 		=> 'Bitte tragen Sie hier den mit heidelpay / easyCredit vereinbarten Maximumbetrag für Ihre Transaktionen ein.'
                            )
					),
					'en_GB' => array(
							'HGW_USER_PW' 			=> array('label' => 'Password'),
							'HGW_TRANSACTION_MODE' 	=> array(
									'description' 		=> 'If enabled, all transaction will be send to Heidelpay Sandbox. Otherwise all transactions are real transactions and each transaction is charged.',
							),
							'HGW_CC_CHANNEL' 		=> array('label' => 'Credit Card Channel'),
							'HGW_CC_ABO_CHANNEL'	=> array(
									'label' 			=> 'Credit Card Channel for subscriptions',
									'description' 		=> 'Channel necessary for subscriptions (Abo Commerce Plug-In).',
							),
							'HGW_DC_CHANNEL' 		=> array('label' => 'Debit Card Channel'),
							'HGW_DC_ABO_CHANNEL' 	=> array(
									'label' 			=> 'Debit Card Channel for subscriptions',
									'description' 		=> 'Channel necessary for subscriptions (Abo Commerce Plug-In).',
							),
							'HGW_DD_CHANNEL' 		=> array('label' => 'Direct Debit Channel'),
							'HGW_PP_CHANNEL' 		=> array('label' => 'Prepayment Channel'),
							'HGW_IV_CHANNEL' 		=> array('label' => 'Invoice Channel'),
							'HGW_PAPG_CHANNEL', array('label'=> 'Invoice with guarantee Channel'),
                            'HGW_IVPD_CHANNEL' 		=> array('label' => 'Payolution Invoice Payment Channel'),
                            'HGW_SU_CHANNEL' 		=> array('label' => 'Sofort Banking Channel'),
                            'HGW_HPR_CHANNEL' 		=> array('label' => 'EasyCredit Channel'),
							'HGW_CC_BOOKING_MODE' 	=> array(
									'label' 			=> 'Credit Card booking mode',
									'description' 		=> $bookingModeDescEN,
							),
							'HGW_DC_BOOKING_MODE' 	=> array(
									'label' 			=> 'Debit Card booking mode',
									'description' 		=> $bookingModeDescEN,
							),
							'HGW_DD_BOOKING_MODE' 	=> array(
									'label' 			=> 'Direct Debit booking mode',
									'description' 		=> $bookingModeDescEN,
							),
							'HGW_DD_GUARANTEE_MODE' => array(
									'label' 			=> 'Direct debit with guarantee',
									'description' 		=> 'Please consider, that a special contract is needed.',
							),
                            'HGW_FACTORING_MODE' => array(
                                'label' 			=> 'heidelpay factoring active',
                                'description' 		=> 'Please consider, that a special contract is needed.',
                            ),
							'HGW_VA_BOOKING_MODE' 	=> array(
									'label' 			=> 'PayPal booking mode',
									'description' 		=> '<b>Please note: PayPal Account needs to be configured for recurring transactions, if you want use the registration feature.<b>',
							),
							'HGW_MPA_BOOKING_MODE' 	=> array(
									'label' 			=> 'MasterPass booking mode',
									'description' 		=> $bookingModeDescEN,
							),
							'HGW_CHB_STATUS' 		=> array(
									'label' 			=> 'Chargeback State',
									'description' 		=> 'This state is set if a direct debit is bounced or at a credit card chargeback.',
							),
							'HGW_MOBILE_CSS' 		=> array('label' => 'activate mobile CSS'),
							'HGW_SECRET' 			=> array('description' => 'Secret to verify the server response. Change only if necessary.'),
//							'HGW_ERRORMAIL' 		=> array('label' => 'Error E-Mail address'),
							'HGW_IV_MAIL' 			=> array(
									'label' 			=> 'Send paymentinformation email for invoice',
									'description' 		=> 'Paymentinformation are beeing send in an extra email for invoice payment.',
							),
							'HGW_DD_MAIL' 			=> array(
									'label' 			=> 'Send paymentinformation email for direct debit',
									'description' 		=> 'Paymentinformation are beeing send in an extra email for direct debit.',
							),
							'HGW_PP_MAIL' 			=> array(
									'label' 			=> 'Send paymentinformation email for prepayment',
									'description' 		=> 'Paymentinformation are beeing send in an extra email for prepayment.',
							),
							'HGW_INVOICE_DETAILS' 	=> array(
									'label' 			=> 'Send invoice data',
									'description' 		=> 'Send invoice data to Heidelpay. Heidelpay generates a PDF invoice for the customer. (Additional Heidelpay services needed)',
							),
							'HGW_IBAN' 				=> array(
									'label' 			=> 'Show IBAN?',
									'description' 		=> 'Show IBAN or Account / Bank no.? Valid for Direct Debit and Sofort Banking.'
							),
							'HGW_SHIPPINGHASH' 		=> array(
									'label' 			=> 'Recognition with different delivery address?',
									'description' 		=> 'Is the recognition disabled, the registered payment data will be discarded, if the customer changes the delivery address after the registration.'
							),
							'HGW_HPF_CC_CSS' 		=> array(
									'label' 			=> 'Path to hPF CSS for creditcard',
									'description' 		=> 'Please enter the absolute path to the CSS, starting with "http(s)://". This CSS applies to our creditcard form.'
							),
							'HGW_HPF_DC_CSS' 		=> array(
									'label'				=>'Path to hPF CSS for debitcard',
									'description' 		=> 'Please enter the absolute path to the CSS, starting with "http(s)://". This CSS applies to our debitcard form.'
							),
                            'HGW_EASYMINAMOUNT' 		=> array(
                                'label'				=>'Minimum amount for Ratenkauf by easyCredit',
                                'description' 		=> 'Please enter here the appointed minimum amount for Ratenkauf by easyCredit.'
                            ),
                            'HGW_EASYMAXAMOUNT' 		=> array(
                                'label'				=>'maximum amount for Ratenkauf by easyCredit',
                                'description' 		=> 'Please enter here the appointed maximum amount for Ratenkauf by easyCredit.'
                            )
					),
			);

			foreach ($translations as $localeCode => $snippets){
				$locale = Shopware()->Models()->getRepository('Shopware\Models\Shop\Locale')
				->findOneBy(array('locale' => $localeCode));
				if(empty($locale)){
					continue;
				}

				foreach ($snippets as $elementName => $snippet){
					$isUpdate = false;
					$element = $form->getElement($elementName);
					if($element === null){ continue; }

					foreach ($element->getTranslations() as $existingTranslation){
						// Check if translation for this locale already exists
						if($existingTranslation->getLocale()->getLocale() != $localeCode){
							continue;
						}
						if(array_key_exists('label', $snippet)){
							$existingTranslation->setLabel($snippet['label']);
						}
						if(array_key_exists('description', $snippet)){
							$existingTranslation->setDescription($snippet['description']);
						}
						$isUpdate = true;
						break;
					}

					if(!$isUpdate){
						$elementTranslation = new \Shopware\Models\Config\ElementTranslation();
						if(array_key_exists('label', $snippet)){
							$elementTranslation->setLabel($snippet['label']);
						}
						if(array_key_exists('description', $snippet)){
							$elementTranslation->setDescription($snippet['description']);
						}
						$elementTranslation->setLocale($locale);
						$element->addTranslation($elementTranslation);
					}
				}
			}
		}catch(Exception $e){
			$this->Logging('addPluginTranslation | '.$e->getMessage());
			return;
		}
	}

	/**
	 * Method to get invoice content from db
	 * @param array $containers
	 * @param array $orderData
	 * @param string $payType
	 * @return string $rawFooter[$footer['id']] | invoice content with placeholder
	 */
	public function getInvoiceContentInfo($containers, $orderData, $payType){
		$name = 'Hgw_'.strtoupper($payType).'_Content_Info';
		$footer = $containers[$name];

		$query = "SELECT * FROM `s_core_documents_box` WHERE `id` = ?";
		$rawFooter = Shopware()->Db()->fetchAssoc($query, array($footer['id']));

		return $rawFooter[$footer['id']];
	}

	/**
	 * Method to deactivate payment method 'Yapital'
	 */
	public function deactivateYapital(){
		$sql = "UPDATE `s_core_paymentmeans` SET `active` = '0' WHERE `name` = 'hgw_yt'";
		Shopware()->Db()->query($sql);
	}

	/** function to create a shipping hash with some User-Data
	 * @param array $user
	 * @param string $pm Paymentmethode
	 */
	public static function createShippingHash($userGiven = null, $pm) {


		if (empty($userGiven) ) {
			try {
				$user = Shopware()->Modules()->Admin()->sGetUserData();
			}
			catch (Exception $e) {
				$callers = debug_backtrace();
				self::Logging('createShippingHash  | bei Payment: '.$pm.' | '.$e->getMessage(). ' Funktion '.$callers[1]['function']);
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
//			    self::Logging('createShippingHash  | bei Payment: '.$pm.' leeres UserArray');
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

    /** formatUserInfos() to normalize $userArray given from Shopware in different ways in Shopware 5.1.6
     * @param null $user
     * @return normalzed User Array
     */
    public static function formatUserInfos($user = null)
    {
        $userGiven = $user;
        if($userGiven != null)
        {
            $user['additional']['user']['userID']       = isset($user['additional']['user']['userID'])      && !empty($user['additional']['user']['userID'])    ? $user['additional']['user']['userID']     : $user['additional']['user']['customerId'];
            $user['additional']['user']['firstname']    = isset($user['additional']['user']['firstname'])   && !empty($user['additional']['user']['firstname']) ? $user['additional']['user']['firstname']  : $user['billingaddress']['firstname'];
            $user['additional']['user']['lastname']     = isset($user['additional']['user']['lastname'])    && !empty($user['additional']['user']['lastname'])  ? $user['additional']['user']['lastname']   : $user['billingaddress']['lastname'];

        } else {
            $user = Shopware()->Modules()->Admin()->sGetUserData();
            $user['additional']['user']['userID']       = isset($user['additional']['user']['userID'])      && !empty($user['additional']['user']['userID'])    ? $user['additional']['user']['userID']     : $user['additional']['user']['customerId'];
            $user['additional']['user']['firstname']    = isset($user['additional']['user']['firstname'])   && !empty($user['additional']['user']['firstname']) ? $user['additional']['user']['firstname']  : $user['billingaddress']['firstname'];
            $user['additional']['user']['lastname']     = isset($user['additional']['user']['lastname'])    && !empty($user['additional']['user']['lastname'])  ? $user['additional']['user']['lastname']   : $user['billingaddress']['lastname'];
        }
        return $user;
    }
}