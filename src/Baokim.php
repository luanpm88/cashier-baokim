<?php

namespace Acelle\Baokim;

use Acelle\Model\Setting;
use Acelle\Model\Plugin;
use Acelle\Cashier\Library\TransactionVerificationResult;
use Acelle\Baokim\Services\BaokimPaymentGateway;
use Acelle\Cashier\Library\AutoBillingData;

class Baokim
{
    public const NAME = 'acelle/baokim';
    public const GATEWAY = 'baokim';

    public $gateway;
    public $plugin;

    public function __construct()
    {
        $publicKey = Setting::get('cashier.baokim.public_key');
        $secretKey = Setting::get('cashier.baokim.secret_key');
        $this->gateway = new BaokimPaymentGateway($publicKey, $secretKey);
        $this->plugin = Plugin::where('name', self::NAME)->first();
    }

    public static function initialize()
    {
        return (new self());
    }

    /**
     * Request PayPal service.
     *
     * @return void
     */
    private function request($uri, $type = 'GET', $options = [])
    {
        $client = new \GuzzleHttp\Client();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->gateway->secretKey,
        ];

        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $response = $client->request($type, $uri, [
            'headers' => $headers,
            'form_params' => isset($options['form_params']) ? $options['form_params'] : [],
            'body' => isset($options['body']) ? (is_array($options['body']) ? json_encode($options['body']) : $options['body']) : '',
        ]);

        return json_decode($response->getBody(), true);
    }

    public function saveAPISettings($params)
    {
        $baokim = $this;

        // make validator
        $validator = \Validator::make($params, [
            'public_key' => 'required',
            'secret_key' => 'required',
        ]);

        $baokim->publicKey = isset($params['public_key']) ? $params['public_key'] : null;
        $baokim->secretKey = isset($params['secret_key']) ? $params['secret_key'] : null;

        // test service
        $validator->after(function ($validator) use ($params, $baokim) {
            try {
                // $baokim->test();
            } catch(\Exception $e) {
                $validator->errors()->add('field', 'Can not connect to ' . $baokim->gateway->getName() . '. Error: ' . $e->getMessage());
            }
        });

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        // save settings
        Setting::set('cashier.baokim.public_key', $params['public_key']);
        Setting::set('cashier.baokim.secret_key', $params['secret_key']);

        return $validator;
    }

    public function test()
    {
        throw new \Exception('test() function not emplement yet!');
    }

    public function updateData($invoice, $data)
    {
        $invoice->updateMetadata($data);
    }

    public function getData($invoice, $name=null)
    {
        return $invoice->getMetadata($name);
    }

    public function runVerify($invoice) : TransactionVerificationResult
    {
        try {
            $result = $this->verifyCheckout($invoice);
        } catch (\Exception $e) {
            return new TransactionVerificationResult(
                TransactionVerificationResult::RESULT_FAILED,
                'Baokim remote transaction is failed with error data: ' . $e->getMessage()
            );
        }

        if (!$result || !isset($result['status']) || $result['status'] != 'success') {
            return new TransactionVerificationResult(
                TransactionVerificationResult::RESULT_FAILED,
                'Baokim remote transaction is failed with error data: ' . json_encode($result)
            );
        } else {
            $this->updateCard($invoice, $result);            
            return new TransactionVerificationResult(TransactionVerificationResult::RESULT_DONE);
        }
    }

    public function verifyCheckout($invoice)
    {
        $checkout = $this->getData($invoice, 'checkout');

        $result = $this->request('https://api.baokim.com/v3/transactions/'.$checkout['transaction_id'].'/verify', 'GET');

        return $result;
    }

    public function updateCard($invoice, $cardInfo)
    {
        // update auto billing data
        $autoBillingData = new AutoBillingData($this->gateway, $cardInfo);
        $invoice->customer->setAutoBillingData($autoBillingData);
    }

    public function getCard($invoice)
    {
        $autoBillingData = $invoice->customer->getAutoBillingData();        

        if ($autoBillingData == null) {
            return false;
        }

        $metadata = $autoBillingData->getData();

        // check last transaction
        if (!isset($metadata['data']) ||
            !isset($metadata['data']['card'])
        ) {
            return false;
        }

        // card info
        return [
            'token' => $metadata['data']['card']['token'],
            'type' => $metadata['data']['card']['type'],
            'last4' => $metadata['data']['card']['last_4digits'],
        ];
    }

    public function pay($invoice)
    {
        $card = $this->getCard($invoice);

        if (!$card || !isset($card['token'])) {
            throw new \Exception('Customer dose not have card information!');
        }

        $this->request('https://api.baokim.com/v3/tokenized-charges', 'POST', [
            'form_params' => [
                "token" => $card['token'],
                "currency" => $invoice->currency->code,
                "amount" => $invoice->total(),
                "tx_ref" => "tokenized-c-" . now()->timestamp,
            ]
        ]);
    }
}
