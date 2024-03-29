<?php

namespace Acelle\Baokim\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Model\Invoice;
use Acelle\Baokim\Baokim;
use Acelle\Library\Facades\Billing;

class BaokimController extends BaseController
{
    public function checkout(Request $request, $invoice_uid)
    {        
        $invoice = Invoice::findByUid($invoice_uid);
        $baokim = Baokim::initialize($invoice);

        // $data = $invoice->getMetadata('baokim');
        
        // // exist order
        // if($data && isset($data['data']) &&isset($data['data']['order_id'])) {
        //     $cancel = $baokim->cancelOrder($data['data']['order_id']);
        // }

        $result = $baokim->createOrder([
            'mrc_order_id' => uniqid(),
            'total_amount' => $invoice->total(),
            'description' => $invoice->description,
            'url_success' => action('\Acelle\Baokim\Controllers\BaokimController@checkoutSuccess', $invoice->uid),
            'merchant_id' => env('BAOKIM_MERCHANT_ID'),
            'url_detail' => action('\Acelle\Http\Controllers\Brand\HomeController@index', $invoice->uid),
            'lang' => 'en',
            'webhooks' => action('\Acelle\Baokim\Controllers\BaokimController@checkoutHook', $invoice->uid),
            'customer_email' => $invoice->billing_email,
            'customer_phone' => $invoice->billing_phone,
            'customer_name' => $invoice->getBillingName(),
            'customer_address' => $invoice->billing_address,
        ]);

        // success get payment url
        if($result && isset($result['data']) && isset($result['data']['payment_url'])) {
            // // update metadata
            // $invoice->updateMetadata([
            //     'baokim' => $result
            // ]);
            
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
            echo "Signature is valid...";
            if (!$invoice->isPaid()) {
                echo "Invoice is not paid. Set as paid now...";
                // success
                $invoice->checkout($baokim->gateway, function () {
                    return new \Acelle\Library\TransactionResult(\Acelle\Library\TransactionResult::RESULT_DONE);
                });
                echo "Pay invoice success";
            } else {
                echo "Invoice is already paid";
            }
        } else {
            echo "Signature is invalid";
        }

        die();
    }

    public function checkoutSuccess(Request $request, $invoice_uid)
    {
        $invoice = Invoice::findByUid($invoice_uid);
        $baokim = Baokim::initialize($invoice);
        
        $response = $baokim->checkOrder($request->id, $request->mrc_order_id);

        // return back if invoice paid
        if($invoice->isPaid()) {
            return redirect()->away(Billing::getReturnUrl());

        }

        if(!$invoice->isPaid() &&
            $response &&
            isset($response['data']) &&
            isset($response['data']['stat']) &&
            $response['data']['stat'] == 'c') {
                // success
                $invoice->checkout($baokim->gateway, function () {
                    return new \Acelle\Library\TransactionResult(\Acelle\Library\TransactionResult::RESULT_DONE);
                });
                
                return redirect()->away(Billing::getReturnUrl());
        }

        var_dump($response);die();
    }
}
