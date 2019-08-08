# M-PESA for Codeigniter
This libraries seeks to provide a straightforward way to integrate M-PESA payments into apps created with Codeigniter

## Installation
Simply copy the directories into your app.

## Edits & Usage
Modify the controller inside `application/controllers/Pesa.php` to suit your needs
### Routing
Add the followiing in your `application/config/routes.php`
```php
$route['pesa'] = 'pesa';
```

### Processing Payment Request
In your payment processing view, point the form to `pesa/pay`
Modify the `pay` method of the controller. For example:
```php
if (isset($_POST['amount'])) {
    $phone 		= trim($_POST['phone']); 
    $amount 	= trim($_POST['amount']); 
    $reference 	= trim($_POST['reference']);
    
    $response = $this->mpesa->stk($phone, $amount, $reference);

    echo json_encode($response);
 }
 ```

### Processing Payment Notification
Modify the `reconcile` method of the controller. For example:
```php
echo json_encode($this->mpesa->reconcile(function ($response)
{
  return true;
}));
 ```
 Note that your callback funcction should always return a boolean (true or false)

### Callback URLs
Register your callback URLs by navigating to `pesa/register`
