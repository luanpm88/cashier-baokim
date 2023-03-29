<?php

namespace Acelle\Baokim\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Model\Invoice;
use Acelle\Baokim\Baokim;
use Acelle\Library\Facades\Billing;
use Acelle\Cashier\Library\TransactionVerificationResult;

class BaokimController extends BaseController
{
    public function checkout(Request $request, $invoice_uid)
    {        
        $invoice = Invoice::findByUid($invoice_uid);
        $baokim = Baokim::initialize($invoice);

        $data = $invoice->getMetadata('baokim');
        // exist order
        if($data && isset($data['data']) &&isset($data['data']['payment_url'])) {
            return redirect()->away($data['data']['payment_url']);
        }

        $result = $baokim->createOrder(json_decode('{
            "mrc_order_id": "'.$invoice->uid.'",
            "total_amount": '.$invoice->totalWithTax().',
            "description": "'.$invoice->description.'",
            "url_success": "'.action('\Acelle\Baokim\Controllers\BaokimController@checkoutSuccess', $invoice->uid).'",
            "merchant_id": '.env('BAOKIM_MERCHANT_ID').',
            "url_detail": "'.action('\App\Http\Controllers\SummaryController@invoice', $invoice->uid).'",
            "lang": "en",
            "webhooks": "'.action('\Acelle\Baokim\Controllers\BaokimController@checkoutHook', $invoice->uid).'",
            "customer_email": "'.$invoice->billing_email.'",
            "customer_phone": "'.$invoice->billing_phone.'",
            "customer_name": "'.$invoice->getBillingName().'",
            "customer_address": "'.$invoice->billing_address.'"
        }', true));

        // success get payment url
        if($result && isset($result['data']) && isset($result['data']['payment_url'])) {
            // update metadata
            $invoice->updateMetadata([
                'baokim' => $result
            ]);
            
            return redirect()->away($result['data']['payment_url']);
        }

        var_dump($result);die();
    }

    public function checkoutHook(Request $request, $invoice_uid)
    {        
        $invoice = Invoice::findByUid($invoice_uid);
        $baokim = Baokim::initialize();
        
        // BAO KIM
        $jsonWebhookData = file_get_contents('php://input');
        $webhookData = json_decode($jsonWebhookData, true);

        $baokimSign = $webhookData['sign'];
        unset($webhookData['sign']);
        
        $signData = json_encode($webhookData);
        
        $secret = $baokim->gateway->secretKey;
        $mySign = hash_hmac('sha256', $signData, $secret);
        
        if($baokimSign == $mySign) {
            // success
            $invoice->checkout($vnpay->gateway, function () {
                return new \Acelle\Cashier\Library\TransactionVerificationResult(\Acelle\Cashier\Library\TransactionVerificationResult::RESULT_DONE);
            });
        } else {
            echo "Signature is invalid aaa";
        }

        die;
    }

    public function checkoutSuccess(Request $request, $invoice_uid)
    {
        $baokim = Baokim::initialize();

        $bkId = $request->id;
        $mrc_order_id = $invoice_uid;

        var_dump($mrc_order_id);

        die();

        $response = $baokim->checkOrder($bkId, $mrc_order_id);

        var_dump($response);die();

        return redirect()->away(Billing::getReturnUrl());
    }
}
