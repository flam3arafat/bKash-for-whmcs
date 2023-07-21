<?php


use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


if (!Capsule::schema()->hasTable('Bkash_refund')) {
    Capsule::schema()->create('Bkash_refund', function ($table) {
        $table->increments('id');
        $table->string('trxID', 30);
        $table->string('paymentID', 30);
    });
}

function bkashcheckoutUrl_MetaData()
{
    return [
        'DisplayName'                 => 'bKash merchant (Checkout-Url)',
        'APIVersion'                  => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage'            => false,
    ];
}

function bkashcheckoutUrl_config()
{
    return [
        'FriendlyName' => [
            'Type'  => 'System',
            'Value' => 'bKash Merchant (Checkout-url)',
        ],
        'username'     => [
            'FriendlyName' => 'Username',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Enter your bKash merchant username',
        ],
        'password'     => [
            'FriendlyName' => 'Password',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Enter your bKash merchant password',
        ],
        'appKey'       => [
            'FriendlyName' => 'App Key',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Enter the bKash app key',
        ],
        'appSecret'    => [
            'FriendlyName' => 'App Secret',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Enter the bKash app secret',
        ],
        'fee'          => [
            'FriendlyName' => 'Fee',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => 1.85,
            'Description'  => 'Gateway fee if you want to add',
        ],
        'sandbox'      => [
            'FriendlyName' => 'Sandbox',
            'Type'         => 'yesno',
            'Description'  => 'Tick to enable sandbox mode',
        ],

    ];
}

function bkashcheckoutUrl_errors($vars)
{
    $errors = [
        "2001" => "Invalid App Key.",
        "2002" => "Invalid Payment ID.",
        "2003" => "Process failed.",
        "2004" => "Invalid firstPaymentDate.",
        "2005" => "Invalid frequency.",
        "2006" => "Invalid amount.",
        "2007" => "Invalid currency.",
        "2008" => "Invalid intent.",
        "2009" => "Invalid Wallet.",
        "2010" => "Invalid OTP.",
        "2011" => "Invalid PIN.",
        "2012" => "Invalid Receiver MSISDN.",
        "2013" => "Resend Limit Exceeded.",
        "2014" => "Wrong PIN.",
        "2015" => "Wrong PIN count exceeded.",
        "2016" => "Wrong verification code.",
        "2017" => "Wrong verification limit exceeded.",
        "2018" => "OTP verification time expired.",
        "2019" => "PIN verification time expired.",
        "2020" => "Exception Occurred.",
        "2021" => "Invalid Mandate ID.",
        "2022" => "The mandate does not exist.",
        "2023" => "Insufficient Balance.",
        "2024" => "Exception occurred.",
        "2025" => "Invalid request body.",
        "2026" => "The reversal amount cannot be greater than the original transaction amount.",
        "2027" => "The mandate corresponding to the payer reference number already exists and cannot be created again.",
        "2028" => "Reverse failed because the transaction serial number does not exist.",
        "2029" => "Duplicate for all transactions.",
        "2030" => "Invalid mandate request type.",
        "2031" => "Invalid merchant invoice number.",
        "2032" => "Invalid transfer type.",
        "2033" => "Transaction not found.",
        "2034" => "The transaction cannot be reversed because the original transaction has been reversed.",
        "2035" => "Reverse failed because the initiator has no permission to reverse the transaction.",
        "2036" => "The direct debit mandate is not in Active state.",
        "2037" => "The account of the debit party is in a state which prohibits execution of this transaction.",
        "2038" => "Debit party identity tag prohibits execution of this transaction.",
        "2039" => "The account of the credit party is in a state which prohibits execution of this transaction.",
        "2040" => "Credit party identity tag prohibits execution of this transaction.",
        "2041" => "Credit party identity is in a state which does not support the current service.",
        "2042" => "Reverse failed because the initiator has no permission to reverse the transaction.",
        "2043" => "The security credential of the subscriber is incorrect.",
        "2044" => "Identity has not subscribed to a product that contains the expected service or the identity is not in Active status.",
        "2045" => "The MSISDN of the customer does not exist.",
        "2046" => "Identity has not subscribed to a product that contains requested service.",
        "2047" => "TLV Data Format Error.",
        "2048" => "Invalid Payer Reference.",
        "2049" => "Invalid Merchant Callback URL.",
        "2050" => "Agreement already exists between payer and merchant.",
        "2051" => "Invalid Agreement ID.",
        "2052" => "Agreement is in incomplete state.",
        "2053" => "Agreement has already been cancelled.",
        "2054" => "Agreement execution pre-requisite hasn't been met.",
        "2055" => "Invalid Agreement State.",
        "2056" => "Invalid Payment State.",
        "2057" => "Not a bKash Account.",
        "2058" => "Not a Customer Wallet.",
        "2059" => "Multiple OTP request for a single session denied.",
        "2060" => "Payment execution pre-requisite hasn't been met.",
        "2061" => "This action can only be performed by the agreement or payment initiator party.",
        "2062" => "The payment has already been completed.",
        "2063" => "Mode is not valid as per request data.",
        "2064" => "This product mode currently unavailable.",
        "2065" => "Mendatory field missing.",
        "2066" => "Agreement is not shared with other merchant.",
        "2067" => "Invalid permission.",
        "2068" => "Transaction has already been completed.",
        "2069" => "Transaction has already been cancelled.",
        "503"  => "System is undergoing maintenance. Please try again later.",
        "lpa"  => "You paid less amount than required.",
        "tau"  => "The transaction already has been used.",
        "irs"  => "Invalid response from the bKash Server.",
        "ucnl" => "You didn't completed the payment process.",
    ];

    $message = null;

    if (!empty($_REQUEST['bkashErrorCode'])) {
        $error   = isset($errors[$_REQUEST['bkashErrorCode']]) ? $errors[$_REQUEST['bkashErrorCode']] : 'Unknown error!';
        $message = '<div class="alert alert-danger" style="margin-top: 10px;" role="alert">' . $error . '</div>';
    }

    return $message;
}

function bkashcheckoutUrl_link($params)
{
    $checkouturl = checkoutUrl($params);
    $errorMessage = bkashcheckoutUrl_errors($params);
    $markup       = <<<HTML
      <style type="text/css">
        #bkashcheckout_button_real { max-width: 175px; height: auto;}
        #bkashcheckout_button_real:hover { cursor: pointer; }
        #bkashcheckout_button_real.loading { opacity: 0.5; pointer-events: none;}
        #bKash_button { display: none; }
    </style>
       $checkouturl
       $errorMessage

HTML;
    return $markup;
}


function bkashcheckoutUrl_refund($params)
{

    $isSandbox         = !empty($params['sandbox']);
    $baseUrl = $isSandbox ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/' : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/';

    // Gateway Configuration Parameters
    //die($params['appKey']);
    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $paymentID = null;
    try {
        $paymentID = Capsule::table('Bkash_refund')
            ->where("trxID", "=", $transactionIdToRefund)
            ->value('paymentID');
        //echo $data;
    } catch (\Illuminate\Database\QueryException $ex) {
        echo $ex->getMessage();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    if (is_null($paymentID)) {
        //die("dukse mama");
        refund_interpret('declined', "No store Payment id found");
    } else {
        $refund_response = Bkash_refund($params, $transactionIdToRefund, $paymentID, $refundAmount, $baseUrl);

        $existing = checkTransaction($refund_response['refundTrxID']);

        // die(var_dump($existing['totalresults'] > 0));
        if (!$existing['totalresults'] > 0) {
            return (is_array($refund_response) && isset($refund_response['statusCode']) && $refund_response['statusCode'] == '0000') ? refund_interpret('success', $refund_response, $refund_response['refundTrxID']) : refund_interpret('declined', $refund_response);
        } else {
            refund_interpret('declined', 'Already refunded');
        }
    }
}


function refund_interpret($status, $rawdata = "", $transid = null)
{
    if ($status == 'success') {
        return array(
            //'success' if successful, otherwise 'declined', 'error' for failure
            'status' => 'success',
            // Data to be recorded in the gateway log - can be a string or array
            'rawdata' => $rawdata,
            // Unique Transaction ID for the refund transaction
            'transid' => $transid,
            // Optional fee amount for the fee value refunded
            'fees' => 0,

        );
    } else {

        return array(
            //'success' if successful, otherwise 'declined', 'error' for failure
            'status' => 'declined',
            'declinereason' => 'rawdata',
            'rawdata' => $rawdata,

        );
    }
}


function checkoutUrl($params)
{
    $apiUrl = $params['systemurl'] . 'modules/gateways/callback/' . $params['paymentmethod'] . '.php';
    $bkashLogo = 'https://scripts.pay.bka.sh/resources/img/bkash_payment.png';
    $_SESSION['hash'] = md5(uniqid(mt_rand(), true));
    $token = $_SESSION['hash'];

    return '<form method="POST" action="' . $apiUrl . '">
       
        <input hidden name="action" value="init" />
        <input type="hidden" name="hash" value="' . $token . '">
        <input hidden name="invoiceid" value="' . $params['invoiceid'] . '" />
        <input id="bkashcheckout_button_real" type="image" src="' . $bkashLogo . '" border="0" alt="Submit" />
          </form>';
}

function getToken($app_key, $app_secret, $username, $password, $baseUrl)
{
    $fields   = [
        'app_key'    => $app_key,
        'app_secret' => $app_secret,
    ];
    $context  = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n" .
                "username: {$username}\r\n" .
                "password: {$password}\r\n",
            'content' => json_encode($fields),
            'timeout' => 30,
        ],
    ];
    $context  = stream_context_create($context);
    $url      = $baseUrl . 'checkout/token/grant';
    $body_data_json = json_encode($fields);

    $response = file_get_contents($url, true, $context);
    $token    = json_decode($response, true);
    // $this->debug($token);


    return (is_array($token) && isset($token['id_token'])) ? $token['id_token'] : null;
}

function Bkash_refund($params, $txrID, $paymentID, $amount, $baseUrl)
{
    $appKey = $params['appKey'];
    $appSecret = $params['appSecret'];
    $username = $params['username'];
    $password = $params['password'];
    $token = getToken($appKey, $appSecret, $username, $password, $baseUrl);

    $body_data = array(
        'paymentID' => $paymentID,
        'trxID' => $txrID,
        'amount' => $amount,
        'sku' => "No",
        'reason' => "NO",

    );
    $context   = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n" .
                "Authorization: {$token}\r\n" .
                "X-APP-KEY: {$appKey}\r\n",
            'content' => json_encode($body_data),
            'timeout' => 30,
        ],
    ];

    $context  = stream_context_create($context);
    $url      = $baseUrl . 'checkout/payment/refund';
    $response = file_get_contents($url, true, $context);
    $data     = json_decode($response, true);

    // $this->debug($data);
    if (is_array($data)) {
        return $data;
    }

    return [
        'status'    => 'error',
        'message'   => 'Invalid response from bKash API.',
        'errorCode' => 'irs',
    ];
}

function checkTransaction($trxId)
{
    return localAPI(
        'GetTransactions',
        ['transid' => $trxId]
    );
}
