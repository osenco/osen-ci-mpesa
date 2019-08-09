# M-PESA for Codeigniter
This libraries seeks to provide a straightforward way to integrate M-PESA payments into apps created with Codeigniter

## Installation
Simply copy the directories into your app.

## Edits & Usage
Modify the controller inside `application/controllers/Pesa.php` to suit your needs
### Processing Payment Request
In your payment processing view, point the form to `pesa/pay`
Modify the `pay` method of the controller. For example:
```php
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
 ```

### Processing Payment Notification
Modify the `reconcile` method of the controller. For example:
```php
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

    $query 		= "SELECT * FROM {$this->table} WHERE request='{$merchantRequestID}';";
    $payment		= $this->db->query($query)->row();
    $payment->phone    	= $phone;
    $payment->amount   	= round($amount);
    $payment->receipt   = $mpesaReceiptNumber;
    $payment->status 	= 'paid';

    $this->db->update($this->table, $payment, array('id' => $payment->id));

    return true;
});

echo json_encode($result);
 ```
 Note that your callback funcction should always return a boolean (true or false)

## Callback URLs
### Registering URLs
Register your callback URLs by navigating to `pesa/register`

### Processing C2B Payment Notification
Use the `confirm()` method to process the payment notification from M-PESA.
```php
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

    $query		= "SELECT * FROM {$this->table} WHERE reference='{$BillRefNumber}';";
    $payment		= $this->db->query($query)->row();
    $amount 		= round($TransAmount);
    $payment->phone    	= $MSISDN;
    $payment->amount   	= $amount;
    $payment->receipt   = $TransID;
    $payment->status 	= 'paid';

    $this->db->update($this->table, $payment, array('id' => $payment->id));

    return true;
});

echo json_encode($result);
```
