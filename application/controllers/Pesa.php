<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pesa extends CI_Controller 
{
	public $table = 'mpesa';
    
    public function __construct()
    {
        parent::__construct();
		$this->load->database();
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
				'passkey'           => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
				'validation_url'    => site_url('pesa/validate'),
				'confirmation_url'  => site_url('pesa/confirm'),
				'callback_url'      => site_url('pesa/reconcile'),
				'timeout_url'      	=> site_url('pesa/timeout'),
			)
		);

		header('Access-Control-Allow-Origin: *');
    }

	public function index()
	{
		$phone 		= isset($_POST['phone']) ? $_POST['phone'] : 2547;
		$amount 	= isset($_POST['amount']) ? $_POST['amount'] : 10;
		$reference 	= isset($_POST['reference']) ? $_POST['reference'] : $phone;
		$data 		= ['config' => $this->mpesa->config, 'reference' => $reference, 'amount' => $amount, 'phone' => $phone];
		$this->load->view('mpesa', $data);
	}
    
	public function pay()
	{
		if (isset($_POST['amount'])) {
        	$phone 		= strip_tags($_POST['phone']); 
			$amount 	= strip_tags($_POST['amount']); 
			$reference 	= strip_tags($_POST['reference']);

			$response 	= $this->mpesa->request($phone, $amount, $reference);
			if($response){
				if (!isset($response['errorCode'])) {
					$request = isset($response['MerchantRequestID']) ? $response['MerchantRequestID'] : time();
					$payment = array(
						'phone' 	=> $phone,
						'amount' 	=> $amount,
						'reference' => $reference,
						'request'	=> $request,
						'status'	=> 'pending',
						'receipt'	=> 'N/A'
					);
					$this->db->insert($this->table, $payment);
				}
			} else {
				$response = array(
					'errorCode' => 1, 
					'errorMessage' => 'Could not connect to Daraja'
				);
			}

			# You can do something with $response, or just redirect to payments page
			# header('Location: '.site_url('payments'));
			# exit();
			# or output the response in JSON, to be processed by an AJAX request
			# header('Content-type:Application/json');
			# echo json_encode($response);
		}
	}
    
	public function reconcile()
	{
        header('Content-type: Application/json');
		header('Access-Control-Allow-Origin: *');
		$result = $this->mpesa->reconcile(function ($response)
		{
			$response                   = $response['Body'];
			$resultCode 			    = $response['stkCallback']['ResultCode'];
			$resultDesc 			    = $response['stkCallback']['ResultDesc'];
			$merchantRequestID 			= $response['stkCallback']['MerchantRequestID'];

			if(isset($response['stkCallback']['CallbackMetadata'])){
				$CallbackMetadata       = $response['stkCallback']['CallbackMetadata']['Item'];

				$amount                 = $CallbackMetadata[0]['Value'];
				$mpesaReceiptNumber     = $CallbackMetadata[1]['Value'];
				$phone                  = $CallbackMetadata[4]['Value'];
			}

			$payment 			= $this->db->query("SELECT FROM {$this->table} WHERE request='{$response['MerchantRequestID']}';")->row();
			$payment->phone    	= $phone;
			$payment->amount   	= round($amount);
			$payment->receipt   = $mpesaReceiptNumber;
			$payment->status 	= 'paid';

			$this->db->update($this->table, $payment, array('id' => $payment->id));

            return true;
		});

		echo json_encode($result);
	}
    
	public function confirm()
	{
        header('Content-type: Application/json');
		$result = $this->mpesa->confirm(function ($response)
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

            $payment 			= $this->db->query("SELECT FROM {$this->table} WHERE reference='{$BillRefNumber}';")->row();
            $amount 			= round($TransAmount);
			$payment->phone    	= $MSISDN;
			$payment->amount   	= $amount;
			$payment->receipt   = $TransID;
			$payment->status 	= 'paid';

			$this->db->update($this->table, $payment, array('id' => $payment->id));

            return true;
		});
		
		echo json_encode($result);
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
