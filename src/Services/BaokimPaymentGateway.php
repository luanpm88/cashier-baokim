<?php

namespace Acelle\Baokim\Services;

use Acelle\Library\Contracts\PaymentGatewayInterface;
use Acelle\Library\TransactionResult;
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

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getDescription(): string
    {
        return 'Những giải pháp trung gian thanh toán đa dạng của Baokim giúp quý đối tác tối ưu quy trình vận hành doanh nghiệp, tối đa hóa lợi nhuận, và mang tới trải nghiệm tốt nhất cho khách hàng dựa trên cam kết giao dịch trực tuyến an toàn, thuận tiện, nhanh chóng.';
    }

    public function getShortDescription(): string
    {
        return 'Baokim - Giải pháp thanh toán hàng đầu Việt Nam';
    }

    public function isActive() : bool
    {
        return ($this->apiKey && $this->secretKey);
    }

    public function getSettingsUrl() : string
    {
        return action("\Acelle\Baokim\Controllers\BaokimController@index");
    }

    public function getCheckoutUrl($invoice) : string
    {
        return action("\Acelle\Baokim\Controllers\BaokimController@checkout", [
            'invoice_uid' => $invoice->uid,
        ]);
    }

    public function verify(Transaction $transaction) : TransactionResult
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

                return new TransactionResult(TransactionResult::RESULT_DONE);
            } catch (\Exception $e) {
                return new TransactionResult(TransactionResult::RESULT_FAILED, $e->getMessage() .
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
