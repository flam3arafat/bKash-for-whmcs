<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use Illuminate\Database\Capsule\Manager as Capsule;

class bkashcheckoutUrl
{

    /**
     * @var self
     */
    private static $instance;

    /**
     * @var string
     */
    protected $gatewayModuleName;

    /**
     * @var array
     */
    protected $gatewayParams;

    /**
     * @var boolean
     */


    public $isSandbox;

    /**
     * @var boolean
     */
    public $isActive;

    /**
     * @var integer
     */
    protected $customerCurrency;

    /**
     * @var object
     */
    protected $gatewayCurrency;

    /**
     * @var integer
     */
    protected $clientCurrency;

    /**
     * @var float
     */
    protected $convoRate;

    /**
     * @var array
     */
    protected $invoice;

    /**
     * @var float
     */
    protected $due;

    /**
     * @var float
     */
    protected $fee;

    /**
     * @var float
     */
    public $total;

    /**
     * @var string
     */
    protected $baseUrl;

    protected $callbackurl;
    public $siteurl;
    public $invoiceID;



    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    public $request;

    /**
     * @var array
     */
    private $credential;

    /**
     * bKashCheckout constructor.
     */
    public function __construct()
    {
        $this->setGateway();
        $this->setRequest();
        $this->setInvoice();
    }

    public function debug($params)
    {
        return die(var_dump($params));
    }
    function redirect($url, $statusCode = 303)
    {
        header('Location: ' . $url, true, $statusCode);
        die();
    }
    /**
     * The instance.
     *
     * @return self
     */
    public static function init()
    {

        self::$instance = new bkashcheckoutUrl;


        return self::$instance;
    }

    /**
     * Set the payment gateway.
     */
    private function setGateway()
    {
        $this->gatewayModuleName = basename(__FILE__, '.php');
        $this->gatewayParams     = getGatewayVariables($this->gatewayModuleName);
        $this->isSandbox         = !empty($this->gatewayParams['sandbox']);
        $this->isActive          = !empty($this->gatewayParams['type']);
        $this->callbackurl = $this->gatewayParams['systemurl'] . 'modules/gateways/callback/' . $this->gatewayModuleName . '.php';
        $this->siteurl = $this->gatewayParams['systemurl'];
        $this->credential = [
            'username'  => $this->gatewayParams['username'],
            'password'  => $this->gatewayParams['password'],
            'appKey'    => $this->gatewayParams['appKey'],
            'appSecret' => $this->gatewayParams['appSecret'],
        ];

        //$this->baseUrl = $this->isSandbox ? 'https://checkout.sandbox.bka.sh/v1.2.0-beta/checkout/' : 'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/';
        $this->baseUrl = $this->isSandbox ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/' : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/';
    }

    /**
     * Set request.
     */
    private function setRequest()
    {
        $this->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    }

    /**
     * Set the invoice.
     */
    private function setInvoice()
    {
        $invoiceIdFromrequest = $this->request->get('invoiceid');


        $this->invoice = localAPI('GetInvoice', [
            'invoiceid' => $invoiceIdFromrequest,
        ]);

        $this->setCurrency();
        $this->setDue();
        $this->setFee();
        $this->setTotal();
    }

    /**
     * Set currency.
     */
    private function setCurrency()
    {
        $this->gatewayCurrency  = (int) $this->gatewayParams['convertto'];
        $this->customerCurrency = (int) \WHMCS\Database\Capsule::table('tblclients')
            ->where('id', '=', $this->invoice['userid'])
            ->value('currency');

        if (!empty($this->gatewayCurrency) && ($this->customerCurrency !== $this->gatewayCurrency)) {
            $this->convoRate = \WHMCS\Database\Capsule::table('tblcurrencies')
                ->where('id', '=', $this->gatewayCurrency)
                ->value('rate');
        } else {
            $this->convoRate = 1;
        }
    }

    /**
     * Set due.
     */
    private function setDue()
    {
        $this->due = $this->invoice['balance'];
    }

    /**
     * Set fee.
     */
    private function setFee()
    {
        $this->fee = empty($this->gatewayParams['fee']) ? 0 : (($this->gatewayParams['fee'] / 100) * $this->due);
    }

    /**
     * Set total.
     */
    private function setTotal()
    {
        $this->total = ceil(($this->due + $this->fee) * $this->convoRate);
    }



    /**
     * Grant and get token from API.
     *
     * @return mixed
     */
    private function getToken()
    {
        $fields   = [
            'app_key'    => $this->credential['appKey'],
            'app_secret' => $this->credential['appSecret'],
        ];
        $context  = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "username: {$this->credential['username']}\r\n" .
                    "password: {$this->credential['password']}\r\n",
                'content' => json_encode($fields),
                'timeout' => 30,
            ],
        ];
        $context  = stream_context_create($context);
        $url      = $this->baseUrl . 'checkout/token/grant';
        $body_data_json = json_encode($fields);

        $response = file_get_contents($url, true, $context);
        $token    = json_decode($response, true);
        // $this->debug($token);


        return (is_array($token) && isset($token['id_token'])) ? $token['id_token'] : null;
    }

    //      "mode": "0011",
    //    "payerReference": "01723888888",
    //    "callbackURL": "yourDomain.com",
    //    "merchantAssociationInfo": "MI05MID54RF09123456One"

    /**
     * Create payment session.
     *
     * @return array
     */
    public function createPayment()
    {
        //$this->debug($this->total);

        $fields   = [
            'mode'                => '0011',
            'payerReference'      => ' ',
            'callbackURL'         => $this->callbackurl . "?" . "invoiceid=" . $this->invoice['invoiceid'],
            'amount'                => $this->total,
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $this->invoice['invoiceid'] . '-' . rand(1000000, 9999999),
        ];

        $context  = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: {$this->getToken()}\r\n" .
                    "X-APP-KEY: {$this->credential['appKey']}\r\n",
                'content' => json_encode($fields),
                'timeout' => 30,
            ],
        ];
        $context  = stream_context_create($context);
        $url      = $this->baseUrl . 'checkout/create';
        $response = file_get_contents($url,false, $context);
        $data     = json_decode($response, true);
        
       if (is_array($data)) {
             $this->redirect($data['bkashURL']);
        }

        return [
            'status'    => 'error',
            'message'   => 'Invalid response from bKash API.',
            'errorCode' => 'irs',
        ];

    }

    /**
     * Execute the payment by ID.
     *
     * @return array
     */
    private function executePayment()
    {
        $paymentID = $this->request->get('paymentID');
        $body_data = array(
            'paymentID' => $paymentID
        );
        $context   = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: {$this->getToken()}\r\n" .
                    "X-APP-KEY: {$this->credential['appKey']}\r\n",
                'content' => json_encode($body_data),
                'timeout' => 30,
            ],
        ];

        $context  = stream_context_create($context);
        $url      = $this->baseUrl . 'checkout/execute/';
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

    private function queryPayment()
    {
        $paymentID = $this->request->get('paymentID');
        $body_data = array(
            'paymentID' => $paymentID
        );
        $context   = [
            'http' => [
                'method'  => 'GET',
                'header'  => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: {$this->getToken()}\r\n" .
                    "X-APP-KEY: {$this->credential['appKey']}\r\n",
                'content' => json_encode($body_data),
                'timeout' => 30,
            ],
        ];

        $context  = stream_context_create($context);
        $url      = $this->baseUrl . 'checkout/query/';
        $response = file_get_contents($url, false, $context);
        $data     = json_decode($response, true);

        if (is_array($data)) {
            return $data;
        }

        return [
            'status'    => 'error',
            'message'   => 'Invalid response from bKash API.',
            'errorCode' => 'irs',
        ];
    }

    /**
     * Check if transaction if exists.
     *
     * @param string $trxId
     *
     * @return mixed
     */
    private function checkTransaction($trxId)
    {
        return localAPI(
            'GetTransactions',
            ['transid' => $trxId]
        );
    }

    /**
     * Log the transaction.
     *
     * @param array $payload
     *
     * @return mixed
     */
    private function logTransaction($payload)
    {
        return logTransaction(
            $this->gatewayParams['name'],
            [
                $this->gatewayModuleName => $payload,
                'request_data'           => $this->request->request->all(),
            ],
            $payload['transactionStatus']
        );
    }

    /**
     * Add transaction to the invoice.
     *
     * @param string $trxId
     *
     * @return array
     */
    private function addTransaction($trxId, $paymentID)
    {
        $fields = [
            'invoiceid' => $this->invoice['invoiceid'],
            'transid'   => $trxId,
            'PaymentID' => $paymentID,
            'gateway'   => $this->gatewayModuleName,
            'date'      => \Carbon\Carbon::now()->toDateTimeString(),
            'amount'    => $this->due,
            'fees'      => $this->fee,
        ];
        //$this->debug($fields);
        $add    = localAPI('AddInvoicePayment', $fields);

        try {
            //key value pair.
            $insert_array = [
                "trxID" => $trxId,
                "paymentID" => $paymentID,

            ];
            Capsule::table('Bkash_refund')
                ->insert($insert_array);
        } catch (\Illuminate\Database\QueryException $ex) {
            echo $ex->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return array_merge($add, $fields);
    }

    /**
     * Make the transaction.
     *
     * @return array
     */
    public function makeTransaction()
    {
        $executePayment = $this->executePayment();



        // if (!isset($executePayment['transactionStatus']) && !isset($executePayment['errorCode'])) {
        //     $executePayment = $this->queryPayment();
        // }

        if (isset($executePayment['transactionStatus']) && $executePayment['transactionStatus'] === 'Completed') {
            $existing = $this->checkTransaction($executePayment['trxID']);

            if ($existing['totalresults'] > 0) {
                return [
                    'statusCode'    => 'tau',
                    'message'   => 'The transaction has been already used.',

                ];
            }

            if ($executePayment['amount'] < $this->total) {
                return [
                    'statusCode'    => 'lpa',
                    'message'   => 'You\'ve paid less than amount is required.',

                ];
            }

            $this->logTransaction($executePayment);

            $trxAddResult = $this->addTransaction($executePayment['trxID'], $executePayment['paymentID']);

            if ($trxAddResult['result'] === 'success') {
                return "success";
            }
        }

        //$this->debug($executePayment);
        return $executePayment;
    }
}

// if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
//     die("Direct access forbidden.");
// }

if (!(new \WHMCS\ClientArea())->isLoggedIn()) {
    die("You will need to login first.");
}

$bKashCheckout = bkashcheckoutUrl::init();



if (!$bKashCheckout->isActive) {
    die("The gateway is unavailable.");
}

$response = [
    'status'  => 'error',
    'message' => 'Invalid action.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = filter_input(INPUT_POST, 'hash', FILTER_SANITIZE_STRING);

    if (!$token || $token !== $_SESSION['hash']) {
        // return 405 http status code
        header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
        exit;
    } else {
        $_SESSION['hash']=null;
        global $CONFIG;
        $invoiceID = $_POST['invoiceid'];
        switch (isset($_POST['action'])) {
            case 'init':
                if ($bKashCheckout->total < 0) {
                    invoiceRespone($invoiceID, "fail", "");
                }
                $response = $bKashCheckout->createPayment();
                
                 if(is_array($response) && isset($response['status'])){
                    
                    invoiceRespone($invoiceID, "fail",$response['errorCode']);
                     break;
                    
                }
              
        }
    }
    // …
} //check post block




if ($bKashCheckout->request->get('status')) {
    $invoiceID = $bKashCheckout->request->get('invoiceid');
    $status = $bKashCheckout->request->get('status');
    if ($status == 'cancel') {
        invoiceRespone($invoiceID, "fail", "ucnl");
    } else if ($status == 'failure') {
        invoiceRespone($invoiceID, "fail", "2003");
    } else if ($status == 'success') {
        $response = $bKashCheckout->makeTransaction();
        if ($response != "success") {
            invoiceRespone($invoiceID, "fail", $response["statusCode"]);
        }
        invoiceRespone($invoiceID, "pass", "");
    } else {
        invoiceRespone($invoiceID, "fail", "");
    }
}





function invoiceRespone($invoiceid, $status, $statusCode)
{
    global $CONFIG;
    $url = "";
    if ($status == "pass") {
        $url = $CONFIG['SystemURL']  . "/viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true";
    } else {
        $url = $CONFIG['SystemURL']  . "/viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true&bkashErrorCode=" . $statusCode;
    }

    header('Location: ' . $url, true, 303);
    die();
}

header('Content-Type: application/json');
die(json_encode($response));
