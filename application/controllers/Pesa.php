<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class pesa extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
		$this->load->database();
        $this->load->model('payments_model');
        $this->load->helper('form');
        $this->load->helper('url');
        $this->load->library(
			'mpesa', 
			array(
				'env'               => 'sandbox',
				'type'              => 4,
				'shortcode'         => '174379',
				'headoffice'        => '174379',
				'key'               => 'WiGveilGB2SKbXWi9IShIHDK7XfCtvWK',
				'secret'            => 'mJBnR94sTlGFUkvM',
				'username'          => 'apitest',	
				'passkey'           => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
				'validation_url'    => site_url('pesa/validate'),
				'confirmation_url'  => site_url('pesa/confirm'),
				'callback_url'      => site_url('pesa/reconcile'),
				'timeout_url'      	=> site_url('pesa/timeout'),
			)
		);
    }

	public function index()
	{
		$this->load->view('mpesa');
	}
    
	public function pay()
	{
        header('Content-type:Application/json');
		if (isset($_POST['amount'])) {
			$phone 		= trim($_POST['phone']); 
			$amount 	= trim($_POST['amount']); 
			$reference 	= trim($_POST['reference']);

			echo json_encode($this->mpesa->stk($phone, $amount, $reference));
		}
	}
    
	public function reconcile()
	{
        header('Content-type: Application/json');
		header('Access-Control-Allow-Origin: *');
		echo json_encode($this->mpesa->reconcile(function ($response)
		{
			return true;
		}));
	}
    
	public function confirm()
	{
        header('Content-type: Application/json');
		header('Access-Control-Allow-Origin: *');
		echo json_encode($this->mpesa->confirm(function ($response)
		{
            // Process $response
            $TransactionType    = $response['TransactionType'];
            $TransID            = $response['TransID'];
            $TransTime          = $response['TransTime'];
            $TransAmount        = $response['TransAmount'];
            $BusinessShortCode  = $response['BusinessShortCode'];
            $BillRefNumber      = $response['BillRefNumber'];
            $InvoiceNumber      = $response['InvoiceNumber'];
            $OrgAccountBalance  = $response['OrgAccountBalance'];
            $ThirdPartyTransID  = $response['ThirdPartyTransID'];
            $MSISDN             = $response['MSISDN'];
            $FirstName          = $response['FirstName'];
            $MiddleName         = $response['MiddleName'];
            $LastName           = $response['LastName'];

            $payment 						= $this->payments_model->get($BillRefNumber);
            $amount 						= round($TransAmount);
			$this->payments_model->phone    = $MSISDN;
			$this->payments_model->amount   = $amount;
			$this->payments_model->update_entry();

            return true;
		}));
	}
    
	public function validate()
	{
        header('Content-type:Application/json');
		echo json_encode($this->mpesa->validate());
	}
    
	public function register()
	{
        header('Content-type:Application/json');
		echo json_encode($this->mpesa->register());
	}
    
}
