<?php
class Pay10ValidationModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public $isLogged = false;

    public $display_column_left = false;

    public $display_column_right = false;

    
    public function __construct()
    {
		
        $this->controller_type = 'modulefront';
        
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        if (! $this->module->active) {
            Tools::redirect('index');
        }
        $this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::getInstance()->getController();
		
        
        parent::__construct();
    }

    public function postProcess()
    {  // print_r($_POST);	exit;	
		$Pay10 = new Pay10();
		//$url = $Pay10->returnsuccess($_POST, true);		
        $response = $Pay10->aes_decryption($_POST['ENCDATA']);
        $final_response = $Pay10->split_decrypt_string($response);
        $final_response['AMOUNT'] = $final_response['AMOUNT']/100;
        $url = $Pay10->returnsuccess($final_response);
       // Tools::redirect($url);exit;
        //print_r($final_response);exit;
		if (isset($url)) Tools::redirect($url);
		exit;		                                
    }
    

}
