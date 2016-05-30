<?php

defined ('_JEXEC') or die('Restricted access');

/**
 * @version $Id: bepaid.php,v 1.0 2016/05/30 13:20:00 ei
 *
 * @author Markun Uladzislav, Valérie Isaksen
 * @version $Id: bepaid.php 5122 2011-12-18 22:24:49Z alatak $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */
if (!class_exists ('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVmPaymentbepaid extends vmPSPlugin {

	private $settings = null;

	function __construct (& $subject, $config) {

		parent::__construct ($subject, $config);
		$this->_loggable = TRUE;
		$this->tableFields = array_keys ($this->getTableSQLFields ());
		$this->_tablepkey = 'id';
		$this->_tableId = 'id';
		$varsToPush = $this->getVarsToPush ();
		$this->settings = $varsToPush;
		$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);
		
		$this->settings = $this->loadPluginSetings();

		define('CALLBACK_URL_GATEWAY', JURI::root().'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&method=bepaid&tmpl=component');
	}

	/*
		Загрузка настроек магазина
	*/
	public function loadStoreSettings()
	{
		$vendorId = JRequest::getInt('vendorid', 1);
		$vendorModel = VmModel::getModel('vendor');
		$vendorModel->setId(1);
		return $vendorModel->getVendor();
	}

	/*
		Отрисовка кнопки ручной генерации счета
	*/
	function onAfterRender() { 
		$documentbody = JResponse::getBody();
		//VMPAYMENT_BEPAID_ERIP_API_DOMAIN_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_ERIP_API_DOMAIN_DEFAULT', JText::_('VMPAYMENT_BEPAID_ERIP_API_DOMAIN_DEFAULT'), $documentbody);

		//VMPAYMENT_BEPAID_INFO_MESSAGE_IN_CHECK_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_INFO_MESSAGE_IN_CHECK_DEFAULT', JText::_('VMPAYMENT_BEPAID_INFO_MESSAGE_IN_CHECK_DEFAULT'), $documentbody);

		//VMPAYMENT_BEPAID_NAME_SPOSOBA_OPLATI_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_NAME_SPOSOBA_OPLATI_DEFAULT', JText::_('VMPAYMENT_BEPAID_NAME_SPOSOBA_OPLATI_DEFAULT'), $documentbody);

		//VMPAYMENT_BEPAID_DESCRIPTION_SPOSOBA_OPLATI_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_DESCRIPTION_SPOSOBA_OPLATI_DEFAULT', JText::_('VMPAYMENT_BEPAID_DESCRIPTION_SPOSOBA_OPLATI_DEFAULT'), $documentbody);

		//VMPAYMENT_BEPAID_DESCRIPTION_CONFIRATION_MANUAL_MODE_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_DESCRIPTION_CONFIRATION_MANUAL_MODE_DEFAULT', JText::_('VMPAYMENT_BEPAID_DESCRIPTION_CONFIRATION_MANUAL_MODE_DEFAULT'), $documentbody);

		//VMPAYMENT_BEPAID_DESCRIPTION_CONFIRATION_AUTO_MODE_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_DESCRIPTION_CONFIRATION_AUTO_MODE_DEFAULT', JText::_('VMPAYMENT_BEPAID_DESCRIPTION_CONFIRATION_AUTO_MODE_DEFAULT'), $documentbody);
		
		//VMPAYMENT_BEPAID_DESCRIPTION_ERIP_ORDER_PAY_DEFAULT
		$documentbody = str_replace ('VMPAYMENT_BEPAID_DESCRIPTION_ERIP_ORDER_PAY_DEFAULT', JText::_('VMPAYMENT_BEPAID_DESCRIPTION_ERIP_ORDER_PAY_DEFAULT'), $documentbody);

		//Если это не просмотр заказа товара, выходим
		if ((isset($_GET['view']) && $_GET['view']=='orders') && (isset($_GET['task']) && $_GET['task']=='edit') && !empty($_GET['virtuemart_order_id'])) {
			$code_button = '<a class="btn" href="'.CALLBACK_URL_GATEWAY.'&custom_event=generate_manula_invoice&virtuemart_order_id='.$_GET['virtuemart_order_id'].'" style="margin-right: 0.3%;"><span class="icon icon-undo"></span>Сгенерировать платежное поручение</a>'; 
			$signature_paste_button_manual_generate_invoice = '<a class="updateOrder btn  btn-primary" href="';

	        $documentbody = str_replace ($signature_paste_button_manual_generate_invoice, $code_button.$signature_paste_button_manual_generate_invoice, $documentbody);
		}

		
        JResponse::setBody($documentbody);
        unset($documentbody);

        return true;
	}

	/*
		Загрузка настроек плагина
	*/
	private function loadPluginSetings() {
		// Get a db connection.
		$db = JFactory::getDbo();
		// Create a new query object.
		$query = $db->getQuery(true);
		$query
    		->select($db->quoteName(array('payment_params')))
    		->from($db->quoteName('#__virtuemart_paymentmethods'))
    		->where($db->quoteName('payment_element') . ' = '. $db->quote('bepaid'))
    		->setLimit('1');
    	$db->setQuery($query);
    	$config = $db->loadObjectList();
    	unset($db);
    	if (isset($config[0])) {
    		 return json_decode($config[0]->payment_params);
    	} else {
    		return null;
    	}
	}

	/*
		Получение значения настроек плагина
	*/
	private function getSettingWithName($nameParam)
	{
		return isset($this->settings->{$nameParam}) ? JText::_( $this->settings->{$nameParam} ): null;
	}

	/**
	 * Create the table for this plugin if it does not yet exist.
	 *
	 * @author Valérie Isaksen
	 */
	public function getVmPluginCreateTableSQL () {

		return $this->createTableSQL ('Payment Bepaid Table');
	}

	/**
	 * Fields to create the payment table
	 *
	 * @return string SQL Fileds
	 */
	function getTableSQLFields () {

		$SQLfields = array(
			'id'                          => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id'         => 'int(1) UNSIGNED',
			'order_number'                => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_name'                => 'varchar(5000)',
			'payment_order_total'         => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\'',
			'payment_currency'            => 'char(3)',
			'cost_per_transaction'        => 'decimal(10,2)',
			'cost_percent_total'          => 'decimal(10,2)',
			'tax_id'                      => 'smallint(1)'
		);

		return $SQLfields;
	}

	/*
		Создание Счета на оплату в системе ЕРИП
	*/
	public function create_invoice_with_erip(&$order_data) {
		$moneyTool = new Money($order_data['total_summ'], $order_data['code_money']);

		$countryModel = VmModel::getModel ('country');
		$countryOrder = null;
	    $countries = $countryModel->getCountries (TRUE, TRUE, FALSE);
	    foreach ($countries as  $country) {
	    	if($country->virtuemart_country_id == $order_data['shipping_country']) {
	            $countryOrder = $country->country_2_code;
	            break;
	        }
	    }

		$arrayDataInvoice = [
 			"request" => [
 				//@NOTICE: getAmount vs getCents
				"amount" => $moneyTool->getCents(),
				"currency" => $moneyTool->getCurrency(),
				"description" => "Оплата заказа #".$order_data['get_order_number'],
				"email" => $order_data['billing_email'],
				"ip" => Tools::getIp(),
				"order_id" => $order_data['get_order_number'],
				"notification_url" => CALLBACK_URL_GATEWAY,
				"customer" => [
					"first_name" => $order_data['shipping_first_name'],
					"last_name" => $order_data['shipping_last_name'],
					"country" => $countryOrder,
					"city" => $order_data['shipping_city'],
					"zip" => $order_data['shipping_postcode'],
					"address" => $order_data['shipping_address_1'],
					"phone" => $order_data['billing_phone']
	 			],
	 			"payment_method" => [
					"type" => "erip",
					"account_number" => $this->getSettingWithName( 'erip_id_magazin' ),
					"service_no" => $this->getSettingWithName( 'erip_kod_uslugi' ),
					"service_info" => [
						"Уважаемый клиент,",
						"Вы оплачиваете заказ ".$order_data['get_order_number']
					],
					"receipt" => [
						$this->getSettingWithName( 'info_message_in_check' )
					]
				]
			]
 		];

		$url = 'https://'.$this->getSettingWithName('erip_API_domain').'/beyag/payments';
		$headers = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen(json_encode($arrayDataInvoice)) 
        );
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_PORT, 443);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
	    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_USERPWD, $this->getSettingWithName( 'erip_id_magazin' ).':'.$this->getSettingWithName( 'erip_API_key' ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayDataInvoice));
		$response = json_decode(curl_exec($ch));

		if (!$response) {
			//ОШИБКА! 
			//echo curl_error($ch);
			echo curl_error($ch);
		}
		curl_close($ch);

		//Отправляем инструкцию как оплатить через ЕРИП
		$this->sendInfoPaymentsErip($order_data, $response);

		return ($response);
	}

	/*
		Отправка сообщения с инструкцией об оплате
	*/
	protected function sendInfoPaymentsErip($order, $dataPaymentsEripSystem) {
		$storeSettings = $this->loadStoreSettings();
		$mailer =& JFactory::getMailer();
		$sender = array( 
		    $this->getSettingWithName('store_email_adress'),
		    $storeSettings->vendor_store_name
		);
		$mailer->setSender($sender); 
		$mailer->addRecipient($order['billing_email']);
		$mailer->setSubject('Инструкция об оплате заказа № '.$dataPaymentsEripSystem->transaction->order_id);

		$message_body = $this->getSettingWithName( 'description_erip_order_pay' );
		//Замена плейсхолдеров на данные из отвера платёжной системы
		$instructionEripPays = isset($dataPaymentsEripSystem->transaction->erip->instruction[0]) ? 
								$dataPaymentsEripSystem->transaction->erip->instruction[0] 
								: 
								$dataPaymentsEripSystem->transaction->erip->instruction;

		$message_body = str_replace("{{instruction_erip}}", $instructionEripPays, $message_body);
		$message_body = str_replace("{{order_num}}", $dataPaymentsEripSystem->transaction->order_id, $message_body);
		$message_body = str_replace("{{fio}}", $order['shipping_first_name']." ".$order['shipping_last_name'], $message_body);
		$message_body = str_replace("{{name_shop}}", $storeSettings->vendor_store_name, $message_body);
		$message_body = str_replace("{{name_provider_service}}", $this->getSettingWithName('erip_name_provider_uslugi'), $message_body);
		$mailer->setBody($message_body);

		$send =& $mailer->Send();

		if ( $send !== true ) {
		    //echo 'Error sending email: ' . $send->message;
		} else {
		    //echo 'Mail sent';
		}


		return $send;
	}

	/*
		Название метода оплаты
	*/
	public function renderPluginName() {
		return $this->getSettingWithName('name_sposoba_oplati');
	}

	/**
	 *
	 *
	 * @author Valérie Isaksen
	 */
	function plgVmConfirmedOrder ($cart, $order) {

		if (!($method = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}
		// 		$params = new JParameter($payment->payment_params);
		$lang = JFactory::getLanguage ();
		$filename = 'com_virtuemart';
		$lang->load ($filename, JPATH_ADMINISTRATOR);

		if (!class_exists ('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		$this->getPaymentCurrency ($method, TRUE);

		// END printing out HTML Form code (Payment Extra Info)
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
		$db = JFactory::getDBO ();
		$db->setQuery ($q);
		$currency_code_3 = $db->loadResult ();
		$paymentCurrency = CurrencyDisplay::getInstance ($method->payment_currency);
		$totalInPaymentCurrency = round ($paymentCurrency->convertCurrencyTo ($method->payment_currency, $order['details']['BT']->order_total, FALSE), 2);
		$cd = CurrencyDisplay::getInstance ($cart->pricesCurrency);

		$dbValues['payment_name'] = $this->renderPluginName ($method) . '<br />' . $method->payment_info;
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['virtuemart_paymentmethod_id'] = $order['details']['BT']->virtuemart_paymentmethod_id;
		$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues['cost_percent_total'] = $method->cost_percent_total;
		$dbValues['payment_currency'] = $currency_code_3;
		$dbValues['payment_order_total'] = $totalInPaymentCurrency;
		$dbValues['tax_id'] = $method->tax_id;
		$this->storePSPluginInternalData ($dbValues);

		$html = '<table class="vmorder-done">' . "\n";
		$html .= $this->getHtmlRow ('bepaid_PAYMENT_INFO', $dbValues['payment_name'], 'class="vmorder-done-payinfo"');
		if (!empty($payment_info)) {
			$lang = JFactory::getLanguage ();
			if ($lang->hasKey ($method->payment_info)) {
				$payment_info = JText::_ ($method->payment_info);
			} else {
				$payment_info = $method->payment_info;
			}
			$html .= $this->getHtmlRow ('bepaid_PAYMENTINFO', $payment_info, 'class="vmorder-done-payinfo"');
		}
		if (!class_exists ('VirtueMartModelCurrency')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
		}

		$dataResponseErip = null;

		if ($this->getSettingWithName('type_sposoba_oplati') == 0) {
			//Ручное создание заказа
			$html .= $this->getSettingWithName('description_confiration_manual_mode');
		} else {
			//Оплата будет происходить в автоматическом режиме
			//Собираем данные для генерации счета
			$order_data = [
				'total_summ' 			=> $totalInPaymentCurrency,
				'code_money' 			=> $currency_code_3,
				'get_order_number' 		=> $order['details']['BT']->virtuemart_order_id,
				'billing_email' 		=> $order['details']['BT']->email,
				'shipping_first_name' 	=> $order['details']['BT']->first_name,
				'shipping_last_name' 	=> $order['details']['BT']->last_name,
				'shipping_country' 		=> $order['details']['BT']->virtuemart_country_id,
				'shipping_city' 		=> $order['details']['BT']->city,
				'shipping_postcode' 	=> $order['details']['BT']->zip,
				'shipping_address_1' 	=> $order['details']['BT']->address_1,
				'shipping_address_2' 	=> $order['details']['BT']->address_2,
				'billing_phone' 		=> $order['details']['BT']->phone_1,
			];

			//Создаем заказ в системе ЕРИП
			$dataResponseErip = $this->create_invoice_with_erip($order_data);


			//Замена плейсхолдеров на данные из отвера платёжной системы
			$instructionEripPays = isset($dataResponseErip->transaction->erip->instruction[0]) ? 
										$dataResponseErip->transaction->erip->instruction[0] 
										: 
										$dataResponseErip->transaction->erip->instruction;

			$message_success = $this->getSettingWithName('description_confiration_auto_mode');
			$message_success = str_replace("{{instruction_value_from_response}}", $instructionEripPays, $message_success);
			$html .= str_replace("{{order_number}}", $dataResponseErip->transaction->order_id, $message_success);
		}

		$currency = CurrencyDisplay::getInstance ('', $order['details']['BT']->virtuemart_vendor_id);
		$html .= $this->getHtmlRow ('bepaid_ORDER_NUMBER', $order['details']['BT']->order_number, "vmorder-done-nr");
		$html .= $this->getHtmlRow ('bepaid_AMOUNT', $currency->priceDisplay ($order['details']['BT']->order_total), "vmorder-done-amount");
		$html .= $this->getHtmlRow('bepaid_AMOUNT', $totalInPaymentCurrency.' '.$currency_code_3);
		$html .= '</table>' . "\n";

		$modelOrder = VmModel::getModel ('orders');
		$order['order_status'] = $this->getNewStatus ($method);
		$order['customer_notified'] = 1;
		$order['comments'] = 'UID требования:'. $dataResponseErip->transaction->uid;
		$modelOrder->updateStatusForOneOrder ($order['details']['BT']->virtuemart_order_id, $order, TRUE);

		//We delete the old stuff
		$cart->emptyCart ();
		JRequest::setVar ('html', $html);

		return TRUE;
	}

	/*
		 * Keep backwards compatibility
		 * a new parameter has been added in the xml file
		 */
	function getNewStatus ($method) {

		if (isset($method->status_pending) and $method->status_pending!="") {
			return $method->status_pending;
		} else {
			return 'P';
		}
	}

	/**
	 * Display stored payment data for an order
	 *
	 */
	function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $virtuemart_payment_id) {

		if (!$this->selectedThisByMethodId ($virtuemart_payment_id)) {
			return NULL; // Another method was selected, do nothing
		}

		if (!($paymentTable = $this->getDataByOrderId ($virtuemart_order_id))) {
			return NULL;
		}

		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE ();
		$html .= $this->getHtmlRowBE ('bepaid_PAYMENT_NAME', $paymentTable->payment_name);
		$html .= $this->getHtmlRowBE ('bepaid_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
		$html .= '</table>' . "\n";
		return $html;
	}

	function getCosts (VirtueMartCart $cart, $method, $cart_prices) {
		if (preg_match ('/%$/', (isset($method->cost_percent_total) ? $method->cost_percent_total : 0))) {
			$cost_percent_total = substr ((isset($method->cost_percent_total) ? $method->cost_percent_total : 0), 0, -1);
		} else {
			$cost_percent_total = (isset($method->cost_percent_total) ? $method->cost_percent_total : 0);
		}
		return ((isset($method->cost_per_transaction) ? $method->cost_per_transaction : 0) + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}

	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 *
	 * @author: Valerie Isaksen
	 *
	 * @param $cart_prices: cart prices
	 * @param $payment
	 * @return true: if the conditions are fulfilled, false otherwise
	 *
	 */
	protected function checkConditions ($cart, $method, $cart_prices) {

		$this->convert ($method);
		// 		$params = new JParameter($payment->payment_params);
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

		// We come from the calculator, the $cart->pricesUnformatted does not exist yet
		//$amount = $cart->pricesUnformatted['billTotal'];
		$amount = $cart_prices['salesPrice'];

		$amount_cond = ($amount >= (isset($method->min_amount) ? $method->min_amount : 0) AND $amount <= (isset($method->max_amount) ? $method->max_amount : 0)
			OR
			((isset($method->min_amount) ? $method->min_amount : 0) <= $amount AND ((isset($method->max_amount) ? $method->max_amount : 0) == 0)));
		if (!$amount_cond) {
			return FALSE;
		}
		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array ($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		// probably did not gave his BT:ST address
		if (!is_array ($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (count ($countries) == 0 || in_array ($address['virtuemart_country_id'], $countries) ) {
			return TRUE;
		}

		return FALSE;
	}

	function convert ($method) {
		if (isset($method->min_amount)) {
			$method->min_amount = (float)$method->min_amount;
		}

		if (isset($method->max_amount)) {
			$method->max_amount = (float)$method->max_amount;
		}
	}

	/*
	* We must reimplement this triggers for joomla 1.7
	*/

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 *
	 */
	function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {

		return $this->onStoreInstallPluginTable ($jplugin_id);
	}

	/**
	 * This event is fired after the payment method has been selected. It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Valérie isaksen
	 *
	 * @param VirtueMartCart $cart: the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *
	 */
	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {

		return $this->OnSelectCheck ($cart);
	}

	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
	 *
	 * @param object  $cart Cart object
	 * @param integer $selected ID of the method selected
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		return $this->displayListFE ($cart, $selected, $htmlIn);
	}

	/*
* plgVmonSelectedCalculatePricePayment
* Calculate the price (value, tax_id) of the selected method
* It is called by the calculator
* This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
* @author Valerie Isaksen
* @cart: VirtueMartCart the current cart
* @cart_prices: array the new cart prices
* @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
*
*
*/

	public function plgVmonSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {

		return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
	}

	function plgVmgetPaymentCurrency ($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

		if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}
		$this->getPaymentCurrency ($method);

		$paymentCurrencyId = $method->payment_currency;
		return;
	}

	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE ($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}
/**
	 * @param $orderDetails
	 * @param $data
	 * @return null
	 */

	function plgVmOnUserInvoice ($orderDetails, &$data) {

		if (!($method = $this->getVmPluginMethod ($orderDetails['virtuemart_paymentmethod_id']))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return NULL;
		}
		//vmdebug('plgVmOnUserInvoice',$orderDetails, $method);

		if (!isset($method->send_invoice_on_order_null) or $method->send_invoice_on_order_null==1 or $orderDetails['order_total'] > 0.00){
			return NULL;
		}

		if ($orderDetails['order_salesPrice']==0.00) {
			$data['invoice_number'] = 'reservedByPayment_' . $orderDetails['order_number']; // Nerver send the invoice via email
		}

	}
	/**
	 * This event is fired during the checkout process. It can be used to validate the
	 * method data as entered by the user.
	 *
	 * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
	 * @author Max Milbers

	public function plgVmOnCheckoutCheckDataPayment(  VirtueMartCart $cart) {
	return null;
	}
	 */

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment ($order_number, $method_id) {
		return $this->onShowOrderPrint ($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsPayment ($name, $id, &$data) {

		return $this->declarePluginParams ('payment', $name, $id, $data);
	}

	function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table) {

		return $this->setOnTablePluginParams ($name, $id, $table);
	}

	//Notice: We only need to add the events, which should work for the specific plugin, when an event is doing nothing, it should not be added

	/**
	 * Save updated order data to the method specific table
	 *
	 * @param array   $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 *
	public function plgVmOnUpdateOrderPayment(  $_formData) {
	return null;
	}

	/**
	 * Save updated orderline data to the method specific table
	 *
	 * @param array   $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 *
	public function plgVmOnUpdateOrderLine(  $_formData) {
	return null;
	}

	/**
	 * plgVmOnEditOrderLineBE
	 * This method is fired when editing the order line details in the backend.
	 * It can be used to add line specific package codes
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 *
	public function plgVmOnEditOrderLineBEPayment(  $_orderId, $_lineId) {
	return null;
	}

	/**
	 * This method is fired when showing the order details in the frontend, for every orderline.
	 * It can be used to display line specific package codes, e.g. with a link to external tracking and
	 * tracing systems
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 *
	public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
	return null;
	}
	*/


	/**
	 * This event is fired when the  method notifies you when an event occurs that affects the order.
	 * Typically,  the events  represents for payment authorizations, Fraud Management Filter actions and other actions,
	 * such as refunds, disputes, and chargebacks.
	 *
	 * NOTE for Plugin developers:
	 *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
	 *
	 * @param         $return_context: it was given and sent in the payment form. The notification should return it back.
	 * Used to know which cart should be emptied, in case it is still in the session.
	 * @param int     $virtuemart_order_id : payment  order id
	 * @param char    $new_status : new_status for this order id.
	 * @return mixed Null when this method was not selected, otherwise the true or false
	 *
	 * @author Valerie Isaksen
	 *
	 *
	 */
	function plgVmOnPaymentNotification () {
		if (!class_exists ('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}

		if (!class_exists ('VirtueMartModelCurrency')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
		}

		if (isset($_GET['custom_event']) && $_GET['custom_event'] == 'generate_manula_invoice' && !empty($_GET['virtuemart_order_id'])) {

			// 		$params = new JParameter($payment->payment_params);
			$lang = JFactory::getLanguage ();
			$filename = 'com_virtuemart';
			$lang->load ($filename, JPATH_ADMINISTRATOR);

			if (!class_exists ('VirtueMartModelOrders')) {
				require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
			}
			$method = $this->getVmPluginMethod($_GET['virtuemart_order_id']);
			$this->getPaymentCurrency ($method, TRUE);

			$modelOrder = VmModel::getModel ('orders');
			$order      = $modelOrder->getOrder($_GET['virtuemart_order_id']);

	        if (!$order) {
	            bplog('order could not be loaded '.$_GET['virtuemart_order_id']);
	            return NULL;
	        }

	        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $order['details']['BT']->order_currency . '" ';
			$db = JFactory::getDBO ();
			$db->setQuery ($q);
			$currency_code_3 = $db->loadResult ();
			//
			if (!class_exists('CurrencyDisplay')) require (JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
			$paymentCurrency = CurrencyDisplay::getInstance ($method->payment_currency);
			$totalInPaymentCurrency = round ($paymentCurrency->convertCurrencyTo ($method->payment_currency, $order['details']['BT']->order_total, FALSE), 2);

			$countryModel = VmModel::getModel ('country');
			$countryOrder = null;
	        $countries = $countryModel->getCountries (TRUE, TRUE, FALSE);
	        foreach ($countries as  $country) {
	          if($country->virtuemart_country_id == $order['details']['BT']->virtuemart_country_id) {
	            $countryOrder = $country->country_2_code;
	            break;
	          }
	        }

			//Собираем данные для генерации счета
			$order_data = [
				'total_summ' 			=> $totalInPaymentCurrency,
				'code_money' 			=> $currency_code_3,
				'get_order_number' 		=> $order['details']['BT']->virtuemart_order_id,
				'billing_email' 		=> $order['details']['BT']->email,
				'shipping_first_name' 	=> $order['details']['BT']->first_name,
				'shipping_last_name' 	=> $order['details']['BT']->last_name,
				'shipping_country' 		=> $countryOrder,
				'shipping_city' 		=> $order['details']['BT']->city,
				'shipping_postcode' 	=> $order['details']['BT']->zip,
				'shipping_address_1' 	=> $order['details']['BT']->address_1,
				'shipping_address_2' 	=> $order['details']['BT']->address_2,
				'billing_phone' 		=> $order['details']['BT']->phone_1,
			];

			//Создаем заказ в системе ЕРИП
			$dataResponseErip = $this->create_invoice_with_erip($order_data);
			$order['order_status'] = $this->getNewStatus ($method);
			$order['customer_notified'] = 1;
			$order['order_status'] = 'C';
			$order['comments'] = 'UID требования:'. $dataResponseErip->transaction->uid;
			$modelOrder->updateStatusForOneOrder ($_GET['virtuemart_order_id'], $order, TRUE);

			return true;
		}
	}
	/**
	 * plgVmOnPaymentResponseReceived
	 * This event is fired when the  method returns to the shop after the transaction
	 *
	 *  the method itself should send in the URL the parameters needed
	 * NOTE for Plugin developers:
	 *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
	 *
	 * @param int     $virtuemart_order_id : should return the virtuemart_order_id
	 * @param text    $html: the html to display
	 * @return mixed Null when this method was not selected, otherwise the true or false
	 *
	 * @author Valerie Isaksen
	 *
	 *
	function plgVmOnPaymentResponseReceived(, &$virtuemart_order_id, &$html) {
	return null;
	}
	 */
}


class Tools {
	// lowercase first letter of functions. It is more standard for PHP
	static function getIP() {
	    if (isset($_SERVER)) {
	        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
	            return $_SERVER["HTTP_X_FORWARDED_FOR"];

	        if (isset($_SERVER["HTTP_CLIENT_IP"]))
	            return $_SERVER["HTTP_CLIENT_IP"];

	        return $_SERVER["REMOTE_ADDR"];
	    }

	    if (getenv('HTTP_X_FORWARDED_FOR'))
	        return getenv('HTTP_X_FORWARDED_FOR');

	    if (getenv('HTTP_CLIENT_IP'))
	        return getenv('HTTP_CLIENT_IP');

	    return getenv('REMOTE_ADDR');
	}
}

class Money {
  protected $_amount;
  protected $_currency;
  protected $_cents;
  public function __construct($amount = 0, $currency = 'USD') {
    $this->_currency = $currency;
    $this->setAmount($amount);
  }
  public function getCents() {
    $cents = ($this->_cents) ? $this->_cents : (int)($this->_amount * $this->_currency_multiplyer());
    return $cents;
  }
  public function setCents($cents) {
    $this->_cents = (int)$cents;
    $this->_amount = NULL;
  }
  public function setAmount($amount){
    $this->_amount = (float)$amount;
    $this->_cents = NULL;
  }
  public function getAmount() {
    $amount = ($this->_amount) ? $this->_amount : (float)($this->_cents / $this->_currency_multiplyer());
    return $amount;
  }
  public function setCurrency($currency){
    $this->_currency = $currency;
  }
  public function getCurrency() {
    return $this->_currency;
  }
  private function _currency_multiplyer() {
    //array currency code => mutiplyer
    $exceptions = array(
        'BIF' => 1,
        'BYR' => 1,
        'CLF' => 1,
        'CLP' => 1,
        'CVE' => 1,
        'DJF' => 1,
        'GNF' => 1,
        'IDR' => 1,
        'IQD' => 1,
        'IRR' => 1,
        'ISK' => 1,
        'JPY' => 1,
        'KMF' => 1,
        'KPW' => 1,
        'KRW' => 1,
        'LAK' => 1,
        'LBP' => 1,
        'MMK' => 1,
        'PYG' => 1,
        'RWF' => 1,
        'SLL' => 1,
        'STD' => 1,
        'UYI' => 1,
        'VND' => 1,
        'VUV' => 1,
        'XAF' => 1,
        'XOF' => 1,
        'XPF' => 1,
        'MOP' => 10,
        'BHD' => 1000,
        'JOD' => 1000,
        'KWD' => 1000,
        'LYD' => 1000,
        'OMR' => 1000,
        'TND' => 1000
    );
    $multiplyer = 100; //default value
    foreach ($exceptions as $key => $value) {
        if (($this->_currency == $key)) {
            $multiplyer = $value;
            break;
        }
    }
    return $multiplyer;
  }
}



jimport('joomla.form.formfield');
class JFormFieldPostbackURL extends JFormField {
	var $type = 'postbackurl';
	function getInput() {
		$cid = JFactory::getApplication()->input->post->get('cid');
		if (is_Array($cid)) {
			$virtuemart_paymentmethod_id = $cid[0];
		} else {
			$virtuemart_paymentmethod_id = $cid;
		}
		$url = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=' . $virtuemart_paymentmethod_id);
		return $url;
	}
}