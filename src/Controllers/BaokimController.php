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

        $result = $baokim->createOrder(json_decode('{
            "mrc_order_id": "string",
            "total_amount": 200000,
            "description": "test",
            "url_success": "https://baokim.vn/",
            "merchant_id": '.env('BAOKIM_MERCHANT_ID').',
            "url_detail": "https://baokim.vn/",
            "lang": "en",
            "bpm_id": 128,
            "webhooks": "https://baokim.vn/",
            "customer_email": "test@gmail.vn",
            "customer_phone": "0888888888",
            "customer_name": "Nguyen Van A",
            "customer_address": "102 Thai Thinh",
            "items": {
            "period": 3,
            "total_amount": "3000000.00",
            "down_payment": "1500000.00",
            "down_payment_percent": "50.00",
            "paylater_amount": "1500000.00",
            "pay_per_month": "500000.00",
            "user_fee": "0",
            "merchant_fee": "120000"
            },
            "extension": {
            "items": [
                {
                "item_id": "abc123",
                "item_code": "ABC123",
                "item_name": "tủ lạnh",
                "price_amount": 10000000,
                "quantity": 3,
                "url": "http://baokim.vn/tu-lanh/abc123"
                }
            ]
            }
        }', true));

        var_dump($result);
    }
}
