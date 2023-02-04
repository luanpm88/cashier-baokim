<?php

namespace Acelle\Baokim\Services;

use Acelle\Cashier\Interfaces\PaymentGatewayInterface;
use Acelle\Cashier\Library\TransactionVerificationResult;
use Acelle\Model\Transaction;
use Acelle\Baokim\Baokim;

class BaokimPaymentGateway implements PaymentGatewayInterface
{
    public $apiKey;
    public $secretKey;
    public $uri;

    public const TYPE = 'baokim';

    /**
     * Construction
     */
    public function __construct($apiKey, $secretKey, $uri)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->uri = $uri;
    }

    public function getName() : string
    {
        return 'BaoKim';
    }

    public function getType() : string
    {
        return self::TYPE;
    }

    public function getDescription() : string
    {
        return trans('baokim::messages.baokim.description');
    }

    public function getShortDescription() : string
    {
        return trans('baokim::messages.baokim.short_description');
    }

    public function isActive() : bool
    {
        return ($this->publicKey && $this->secretKey);
    }

    public function getSettingsUrl() : string
    {
        return action("\Acelle\Baokim\Controllers\BaokimController@settings");
    }

    public function getCheckoutUrl($invoice) : string
    {
        return action("\Acelle\Baokim\Controllers\BaokimController@checkout", [
            'invoice_uid' => $invoice->uid,
        ]);
    }

    public function verify(Transaction $transaction) : TransactionVerificationResult
    {
        $invoice = $transaction->invoice;
        $baokim = Baokim::initialize();

        return $baokim->runVerify($invoice);
    }
    

    public function allowManualReviewingOfTransaction() : bool
    {
        return false;
    }

    public function autoCharge($invoice)
    {
        $gateway = $this;
        $baokim = Baokim::initialize();

        $invoice->checkout($this, function($invoice) use ($baokim) {
            try {
                // charge invoice
                $baokim->pay($invoice);

                return new TransactionVerificationResult(TransactionVerificationResult::RESULT_DONE);
            } catch (\Exception $e) {
                return new TransactionVerificationResult(TransactionVerificationResult::RESULT_FAILED, $e->getMessage() .
                    '. <a href="' . $baokim->gateway->getCheckoutUrl($invoice) . '">Click here</a> to manually charge.');
            }
        });
    }

    public function getAutoBillingDataUpdateUrl($returnUrl='/') : string
    {
        return \Acelle\Cashier\Cashier::lr_action("\Acelle\Baokim\Controllers\BaokimController@autoBillingDataUpdate", [
            'return_url' => $returnUrl,
        ]);
    }

    public function supportsAutoBilling() : bool
    {
        return false;
    }

    /**
     * Check if service is valid.
     *
     * @return void
     */
    public function test()
    {
        $baokim = Baokim::initialize();
        $baokim->test();
    }

    public function getMinimumChargeAmount($currency)
    {
        return 0;
    }
}
