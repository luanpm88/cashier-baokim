<?php

namespace Acelle\Baokim;

use Acelle\Model\Setting;
use Acelle\Cashier\Library\TransactionVerificationResult;
use Acelle\Baokim\Services\BaokimPaymentGateway;
use Acelle\Cashier\Library\AutoBillingData;
use Firebase\JWT\JWT;

class Baokim
{
    public const NAME = 'acelle/baokim';

    const TOKEN_EXPIRE = 86400;

    public $gateway;

    public $_jwt;

    public function __construct()
    {
        $apiKey = env('BAOKIM_API_KEY','');
        $secretKey = env('BAOKIM_SECRET_KEY','');
        $uri = env('BAOKIM_URI','');

        $this->gateway = new BaokimPaymentGateway($apiKey, $secretKey, $uri);
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
        ];

        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $response = $client->request($type, $uri, [
            'headers' => $headers,
            'query' => isset($options['query']) ? $options['query'] : [],
            'form_params' => isset($options['form_params']) ? $options['form_params'] : [],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function createOrder($data)
    {
        $client = new \GuzzleHttp\Client(['timeout' => 20.0]);
        $options['query']['jwt'] = $this->getToken();

        $options['form_params'] = $data;

        $response = $client->request("POST", $this->gateway->uri . "/api/v5/order/send", $options);
        $dataResponse = json_decode($response->getBody()->getContents(), true);

        // echo "Response status code: " . $response->getStatusCode();
        // echo "<br/>";
        // echo "Response data: ";
        // echo '<pre>'.print_r($dataResponse, true).'</pre>';

        // var_dump($this->getToken());die();
        // var_dump($data);die();


        // return $this->request($this->gateway->uri . '/api/v5/order/send', 'POST', [
        //     'query' => [
        //         'jwt' => $this->getToken(),
        //     ],
        //     'form_params' => $data,
        // ]);

        return $dataResponse;
    }

    public function checkOrder($bkId, $invoice_uid)
    {
        $client = new \GuzzleHttp\Client(['timeout' => 20.0]);
        $options['query']['jwt'] = $this->getToken();

        $options['form_params'] = [
            'id' => $bkId,
            'mrc_order_id' => $invoice_uid,
        ];

        $response = $client->request("GET", $this->gateway->uri . "/api/v5/order/detail", $options);
        $dataResponse = json_decode($response->getBody()->getContents(), true);

        // echo "Response status code: " . $response->getStatusCode();
        // echo "<br/>";
        // echo "Response data: ";
        // echo '<pre>'.print_r($dataResponse, true).'</pre>';

        // var_dump($this->getToken());die();
        // var_dump($data);die();


        // return $this->request($this->gateway->uri . '/api/v5/order/send', 'POST', [
        //     'query' => [
        //         'jwt' => $this->getToken(),
        //     ],
        //     'form_params' => $data,
        // ]);

        return $dataResponse;
    }

    public function getKey()
    {
        return $this->_jwt;
    }

    public function refreshToken($key, $sec){

		$tokenId    = base64_encode(random_bytes(32));
		$issuedAt   = time();
		$notBefore  = $issuedAt;
		$expire     = $notBefore + self::TOKEN_EXPIRE;

		/*
		 * Payload data of the token
		 */
		$data = [
			'iat'  => $issuedAt,         // Issued at: time when the token was generated
			'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
			'iss'  => $key,     // Issuer
			'nbf'  => $notBefore,        // Not before
			'exp'  => $expire,           // Expire
			'form_params' => [
			]
		];

		/*
		 * Encode the array to a JWT string.
		 * Second parameter is the key to encode the token.
		 *
		 * The output string can be validated at http://jwt.io/
		 */
		$this->_jwt = JWT::encode(
			$data,      //Data to be encoded in the JWT
			$sec, // The signing key
			'HS256'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
		);

		return $this->_jwt;
	}

	/**
	 * Get JWT
	 */
	public function getToken(){
		if(!$this->_jwt)
        $this->refreshToken($this->gateway->apiKey, $this->gateway->secretKey);

		try {
			JWT::decode($this->_jwt, $this->gateway->secretKey, array('HS256'));
		}catch(\Exception $e){
			$this->refreshToken($this->gateway->apiKey, $this->gateway->secretKey);
		}

		return $this->_jwt;
	}
}
