<?php
/**
 * PAY10
 */
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
    exit;


class Pay10 extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'pay10';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.0';
        $this->author = 'Pay10';
        $this->need_instance = 1;
        $this->bootstrap = true;       
		
		
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );


        parent::__construct();
		
		$this->meta_title = $this->l('Pay10');
		$this->displayName = $this->l('Pay10');		        
        $this->description = $this->l('Pay10');	

    }

    public function install()
    {
		$this->addOrderState('Payment Failed');
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('adminOrder') || !$this->registerHook('hookPaymentReturn') || !$this->registerHook('orderConfirmation')           
        ) {
            return false;
        }
        return true;

    }
	
	public function addOrderState($name)
    {
        $state_exist = false;
        $states = OrderState::getOrderStates((int)$this->context->language->id);
 
        // check if order state exist
        foreach ($states as $state) {
            if (in_array($name, $state)) {
                $state_exist = true;
                break;
            }
        }
 
        // If the state does not exist, we create it.
        if (!$state_exist) {
            // create new order state
			$order_state = new OrderState();
			$order_state->id_order_state = 21;
            $order_state->color = '#d30016';
            $order_state->send_email = false;
            $order_state->module_name = 'pay10';
            $order_state->template = '';
            $order_state->name = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $language)
                $order_state->name[ $language['id_lang'] ] = $name;
 
            // Update object
            $order_state->add();
        }
 
        return true;
    }
	
    public function hookPaymentOptions($params)
    {
        return $this->pay10PaymentOptions($params);
    }
	
    public function hookPaymentReturn($params)
    {		
		
        $this->pay10PaymentReturnNew($params);
        return $this->display(dirname(__FILE__), '/tpl/order-confirmation.tpl');
    }	
	
		
    public static function setOrderStatus($oid, $status)
    {
        $order_history = new OrderHistory();
        $order_history->id_order = (int)$oid;
        $order_history->changeIdOrderState((int)$status, (int)$oid, true);
        $order_history->addWithemail(true);        
    }
	

	public function hookOrderConfirmation($params)
    {
		
		
		if ($params['order']->module != $this->name)
			return false;
		
		$errmsg = '';
		if (isset($_GET['errmsg'])) $errmsg = base64_decode($_GET['errmsg']);		
		
		if ($params['order'] && Validate::isLoadedObject($params['order']) && isset($params['order']->valid))
		{
			
		$this->smarty->assign('pay10_order', array('id' => $params['order']->id, 'valid' => $params['order']->valid, 'errmsg'=>$errmsg));

			return $this->display(__FILE__, '/tpl/order-confirmation.tpl');
		}
    }
	
	public function returnsuccess($params){
		//print_r($params);exit;
		 $orderId = $params['ORDER_ID'];
		list($oid) = explode('_', $orderId);
		$jsonResponse = $params;

		
		if ($jsonResponse['STATUS'] == "Captured") {
			
			$errmsglist['CANCELLED'] = 'Payment has been cancelled';
			$errmsglist['PENDING'] = 'Payment is under review.';			
			$errmsglist['FLAGGED'] = 'Payment is under flagged';
			$errmsglist['FAILED'] = 'Payment failed';			
		
			
			$txStatus = 'CANCELLED';
			$total = $jsonResponse['AMOUNT'];	
			//echo "entered";		
				
			if ($jsonResponse['STATUS'] == "Captured") {
			//echo "Hi";echo $oid;
				$cart = new Cart((int)$oid);
				if ($cart->OrderExists()) {
					$order = new Order((int)Order::getOrderByCartId($oid));
					$query = http_build_query([
						'controller'    => 'order-confirmation',
						'id_cart'       => (int)$oid,
						'id_module'     => (int)$this->id,
						'id_order'      => (int)$order->id,
						'key'           => $this->context->customer->secure_key,
					], '', '&');

				$url = 'index.php?' . $query;
					Logger::addLog("Already Order Exist. Payment Successful for cart#".$oid.". Pay10 reference id: ".$jsonResponse['TXN_ID'] . " Success Url: ".$response_url, 1);
					//Tools::redirect($url);exit;
					return $url;
				}			
				$extra_vars['transaction_id'] = $jsonResponse['referenceId'];
				$orderValidate = $this->validateOrder((int)$oid, (int)Configuration::get('PAY10_OSID'), (float)($total), $this->displayName, null, $extra_vars, null, false, $cart->secure_key);

				Logger::addLog("Payment Successful for cart#".$oid.". Pay10 reference id: ".$jsonResponse['referenceId'] . " Ret=" . (int)$orderValidate." Success Url: ".$response_url, 1);

				$query = http_build_query([
					'controller'    => 'order-confirmation',
					'id_cart'       => (int)$oid,
					'id_module'     => (int)$this->id,
					'id_order'      => (int)$this->currentOrder,
					'key'           => $this->context->customer->secure_key,
				], '', '&');
	
				$url = 'index.php?' . $query;
			}			
			return $url;			
			
		}

		else{ 
            
				Logger::addLog("Payment Failed or cancelled by user  for cart#".$oid.". Pay10 Transaction id: ".$jsonResponse['TXN_ID'] . " Order Id=" .$jsonResponse['ORDER_ID']." Status: ". $jsonResponse['STATUS'], 1);

                //echo "Payment Failed or cancelled by user  for cart#".$oid.". Pay10 Transaction id: ".$jsonResponse['TXN_ID'] . " Order Id=" .$jsonResponse['ORDER_ID']." Status: ". $jsonResponse['STATUS'];exit;
				Tools::redirect(Tools::getHttpHost(true).__PS_BASE_URI__.'en/cart?action=show');
		}
		
	}
	
    /**
     * Uninstall and clean the module settings
     *
     * @return	bool
     */
    public function uninstall()
    {
        parent::uninstall();

        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'module_country` WHERE `id_module` = '.(int)$this->id);

        return (true);
    }

	
    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
			
			$pay10_name = Tools::getValue('pay10_name');
			$saveOpt = false;
			$err_msg = '';
			if (empty(Tools::getValue('pay10_app_id'))) $err_msg = 'App ID must have value';
			if (empty(Tools::getValue('pay10_secret_key'))) $err_msg = 'Secret Key must have value';	
			if (empty(Tools::getValue('pay10_hosted_key'))) $err_msg = 'Hosted Key must have value';		
		
			
			if (empty($err_msg)) $saveOpt = true;
			
        	if ($saveOpt) {
				Configuration::updateValue('PAY10_APP_ID', pSQL(Tools::getValue('pay10_app_id')));
				Configuration::updateValue('PAY10_SKEY', pSQL(Tools::getValue('pay10_secret_key')));
				Configuration::updateValue('PAY10_HKEY', pSQL(Tools::getValue('pay10_hosted_key')));				
				Configuration::updateValue('PAY10_OSID', pSQL(Tools::getValue('pay10_order_status')));
				Configuration::updateValue('PAY10_MODE', pSQL(Tools::getValue('pay10_mode')));			
																		
				$html = '<div class="alert alert-success">'.$this->l('Configuration updated successfully').'</div>';			
			}
			else {
				$html = '<div class="alert alert-warning">'.$this->l($err_msg).'</div>';	
			}
        }

		$states = 	OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT'));
		foreach ($states as $state)		
		{
			$OrderStates[$state['id_order_state']] = $state['name'];
		}
		$orderstatusid = Configuration::get('PAY10_OSID');			
		if (empty($orderstatusid)) 	$orderstatusid = '2';
		
        $data    = array(
            'base_url'    => _PS_BASE_URL_ . __PS_BASE_URI__,
            'module_name' => $this->name,            
			'pay10_form' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',				
			'pay10_app_id' => Configuration::get('PAY10_APP_ID'),				
			'pay10_secret_key' => Configuration::get('PAY10_SKEY'),	
			'pay10_hosted_key' => Configuration::get('PAY10_HKEY'),		        
            'pay10_mode' => Configuration::get('PAY10_MODE'),			
            'pay10_order_status' => $orderstatusid,			
			'pay10_confirmation' => isset($html) ? $html : '',			
            'orderstates' => $OrderStates,
        );

        $this->context->smarty->assign($data);	
        $output = $this->display(__FILE__, 'tpl/admin.tpl');

        return $output;
    }

	
	//1.7

    public function pay10PaymentOptions($params)
    {

        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [
            $this->pay10ExternalPaymentOption(),
        ];
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function pay10ExternalPaymentOption()
    {
		$lang = Tools::strtolower($this->context->language->iso_code);
		if (isset($_GET['pay10error'])) $errmsg = $_GET['pay10error'];
        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'errmsg' => $errmsg,			
        ));		
		
		$url = $this->context->link->getModuleLink('pay10', 'payment');
		
        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->l('Pay with Pay10'))
			->setAction($url)
            ->setAdditionalInformation($this->context->smarty->fetch('module:pay10/tpl/payment_infos.tpl'));

        return $newOption;
    }

    public function pay10PaymentReturnNew($params)
    {
        // Payement return for PS 1.7
        if ($this->active == false) {
            return;
        }
        $order = $params['order'];
        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }	
		
        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,					
            'params' => $params,
            'total_to_pay' => Tools::displayPrice($order->total_paid, null, false),
            'shop_name' => $this->context->shop->name,
        ));
        return $this->fetch('module:' . $this->name . '/tpl/order-confirmation.tpl');
    }
	
	public function getUrl()
    {        				
		$cart = $this->context->cart;
		$customer = new Customer($cart->id_customer);
		
		$amount = number_format($cart->getOrderTotal(true, Cart::BOTH),2);
		$order_id = $cart->id;				
				
		$iaddress = new Address($cart->id_address_invoice);				
		$icountry_code = Country::getIsoById($iaddress->id_country) ;		
		$total = ($cart->getOrderTotal());
		$currency = $this->context->currency;
		
		$returnURL = $this->context->link->getModuleLink('pay10', 'validation');		
		$notifyURL  = $this->context->link->getModuleLink('pay10', 'notify');
					  
		$apiEndpoint = (Configuration::get('PAY10_MODE') == 'N') ? 'https://secure.pay10.com/pgui/jsp/paymentrequest' : 'https://uat.pay10.com/pgui/jsp/paymentrequest';  				
		
        
        $post_variables = array();
        $post_variables["PAY_ID"] = Configuration::get('PAY10_APP_ID');
        $post_variables["ORDER_ID"] = $order_id.'_'.time();
   		$post_variables["RETURN_URL"] = $returnURL;
   		$post_variables["CUST_EMAIL"] =  $customer->email;
        $post_variables["CUST_NAME"] = $iaddress->firstname . ' ' . $iaddress->lastname;
        $post_variables["CUST_STREET_ADDRESS1"] = "";
        $post_variables["CUST_CITY"] = "";
        $post_variables["CUST_STATE"] = "";
        $post_variables["CUST_COUNTRY"] = "";
        $post_variables["CUST_ZIP"] = "";
        $post_variables["CUST_PHONE"] =$iaddress->phone;
        $post_variables["CURRENCY_CODE"] = 356;
        $post_variables["AMOUNT"] =round($total, 2)*100;
        $post_variables["PRODUCT_DESC"] = "";
        $post_variables["CUST_SHIP_STREET_ADDRESS1"] = "";
        $post_variables["CUST_SHIP_CITY"] = "";
        $post_variables["CUST_SHIP_STATE"] = "";
        $post_variables["CUST_SHIP_COUNTRY"] = "";
        $post_variables["CUST_SHIP_ZIP"] = "";
        $post_variables["CUST_SHIP_PHONE"] = "";
        $post_variables["CUST_SHIP_NAME"] = "";
        $post_variables["TXNTYPE"] ="SALE";
        
        
        $postdata = $this->createTransactionRequest($post_variables);
        $this->redirectForm($postdata,$apiEndpoint);		

    }


   //Pay10 Custom Functions

     public function generateHash($postdata)
    {
        ksort($postdata);
        $all = '';
        foreach ($postdata as $name => $value) {
            $all .= $name."=".$value."~";
        }
        $all = substr($all, 0, -1);
        $all .= Configuration::get('PAY10_SKEY');

        //print_r($all);exit;
        return strtoupper(hash('sha256', $all));
    }

    /**
     * [createTransactionRequest description]
     * @return string [description]
     */
    public function createTransactionRequest($post_variables)
    {
        $post_variables['HASH'] = $this->generateHash($post_variables);

        return $post_variables;
    }

    /**
     * [performChecks description]
     * @return [type] [description]
     */
    public function performChecks()
    {
        // required values
        $check_values = array(
            'PAY_ID' => $this->pay_id,
            'ORDER_ID' => $this->order_id,
            'RETURN_URL' => $this->return_url,
            'CUST_EMAIL' => $this->cust_email,
            'CUST_PHONE' => $this->cust_phone,
            'AMOUNT' => $this->amount,
            'TXNTYPE' => $this->txn_type,
            'CURRENCY_CODE' => $this->currency_code
        );

        foreach ($check_values as $key => $value) {
            if (!isset($value)) {
                die('<h4>'.$key.' value is missing</h4>');
            }
        }
    }

    /**
     * [validateResponse description]
     * @param  [type] $response [description]
     * @return [type]           [description]
     */
    public function validateResponse($response)
    {
        $postdata = $response;
        $salt=$this->salt;
        ksort($postdata);
        unset($postdata["HASH"]);
        unset($postdata["CARD_ISSUER_BANK"]);
        
        $all = '';
        foreach ($postdata as $name => $value) {
            $all .= $name."=".$value."~";
        }
        $all = substr($all, 0, -1);
        $all .= $salt;
        
      

        $generated_hash = strtoupper(hash('sha256', $all));
    
        if ($response['HASH'] == $generated_hash) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * [redirectForm description]
     * @param  [type] $postdata [description]
     * @return [type]           [description]
     */
    public function redirectForm($postdata,$apiEndpoint)
    {
        $output = '<form id="payForm" action="'.$apiEndpoint.'" method="post">';
        foreach ($postdata as $key => $value) {
            $output .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\n";
        }

        
        //echo "<textarea> $output</textarea>";exit;
        $output .= '</form><script> document.getElementById("payForm").submit(); </script><h2>Redirecting...</h2>';
        echo $output;
        exit();
    }


    //for aes encrytion

    public function aes_encyption($hash_string){
     $CryptoKey= Configuration::get('PAY10_HKEY'); //Prod Key
     $iv = substr($CryptoKey, 0, 16); //or provide iv
     $method = "AES-256-CBC";
     $ciphertext = openssl_encrypt($hash_string, $method, $CryptoKey, OPENSSL_RAW_DATA, $iv);
     $ENCDATA= base64_encode($ciphertext);
     return $ENCDATA;
    }       

    public function aes_decryption($ENCDATA){
    $CryptoKey= Configuration::get('PAY10_HKEY'); //Prod Key
    $iv = substr($CryptoKey, 0, 16); //or provide iv
    $method = "AES-256-CBC";
    $encrptedString  = openssl_decrypt($ENCDATA, $method, $CryptoKey, 0, $iv);
    return $encrptedString;
    }  

    public function split_decrypt_string($value)
    {
        $plain_string=explode('~',$value);
        $final_data = array();
        foreach ($plain_string as $key => $value) {
            $simple_string=explode('=',$value);
           $final_data[$simple_string[0]]=$simple_string[1];
        } 
        return $final_data;
    }


   //Pay10 Custom Functions 

}
