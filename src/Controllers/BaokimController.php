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
    public function settings(Request $request)
    {
        $baokim = Baokim::initialize();      

        if ($request->isMethod('post')) {
            // save Baokim setting
            $validator = $baokim->saveAPISettings($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('baokim::settings', [
                    'baokim' => $baokim,
                    'errors' => $validator->errors(),
                ], 400);
            }

            if ($request->enable_gateway) {
                $baokim->plugin->activate();
                Billing::enablePaymentGateway($baokim->gateway->getType());
            }

            if ($baokim->plugin->isActive()) {
                return redirect()->action("Admin\PaymentController@index")
                    ->with('alert-success', trans('cashier::messages.gateway.updated'));
            } else {
                return redirect()->action("Admin\PluginController@index")
                    ->with('alert-success', trans('cashier::messages.gateway.updated'));
            }
        }

        return view('baokim::settings', [
            'baokim' => $baokim,
        ]);
    }

    public function checkout(Request $request, $invoice_uid)
    {        
        $invoice = Invoice::findByUid($invoice_uid);
        $baokim = Baokim::initialize($invoice);
        
        // Save return url
        if ($request->return_url) {
            $request->session()->put('checkout_return_url', $request->return_url);
        }

        // exceptions
        if (!$invoice->isNew()) {
            throw new \Exception('Invoice is not new');
        }

        // free plan. No charge
        if ($invoice->total() == 0) {
            $invoice->checkout($baokim->gateway, function($invoice) {
                return new TransactionVerificationResult(TransactionVerificationResult::RESULT_DONE);
            });

            return redirect()->action('SubscriptionController@index');
        }

        // use new card
        if ($request->transaction_id) {
            // save checkout information
            $baokim->updateData($invoice, [
                'checkout' => $request->all(),
            ]);
            
            try {
                // check pay
                $result = $baokim->runVerify($invoice);

                $invoice->checkout($baokim->gateway, function($invoice) use ($result) {
                    return $result;
                });

                return redirect()->action('SubscriptionController@index');
            } catch (\Exception $e) {
                // return with error message
                $request->session()->flash('alert-error', $e->getMessage());
                return redirect()->action('SubscriptionController@index');
            }
        }

        // use old card
        if ($request->isMethod('post')) {
            // Use current card
            if ($request->current_card) {
                try {
                    // charge invoice
                    $baokim->gateway->autoCharge($invoice);

                    return redirect()->action('SubscriptionController@index');
                } catch (\Exception $e) {
                    // invoice checkout
                    $invoice->checkout($baokim->gateway, function($invoice) use ($e) {
                        return new TransactionVerificationResult(TransactionVerificationResult::RESULT_FAILED, $e->getMessage());
                    });
                    return redirect()->action('SubscriptionController@index');
                }
            }
        }

        return view('baokim::checkout', [
            'baokim' => $baokim,
            'invoice' => $invoice,
        ]);
    }
}
