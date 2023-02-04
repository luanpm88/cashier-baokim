<html lang="en">
    <head>
        <title>{{ trans('flutterwave::messages.flutterwave') }}</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <link rel="stylesheet" href="{{ \Acelle\Cashier\Cashier::public_url('/vendor/acelle-cashier/css/main.css') }}">
    </head>
    
    <body>
        <div class="main-container row mt-40">
            <div class="col-md-2"></div>
            <div class="col-md-4 mt-40 pd-60">
                <label class="text-semibold text-muted mb-20 mt-0">
                    <strong>
                        {{ trans('flutterwave::messages.flutterwave') }}
                    </strong>
                </label>
                <img class="rounded" width="80%" src="{{ $flutterwave->plugin->getIconUrl() }}" />
            </div>
            <div class="col-md-4 mt-40 pd-60">                
                <h2 class="mb-40">{{ $invoice->title }}</h2>
                <p>{!! trans('flutterwave::messages.checkout.intro', [
                    'price' => $invoice->formattedTotal(),
                ]) !!}</p>



                @if (false && $flutterwave->getCard($invoice))
                    <div class="sub-section mb-5">

                        <h4 class="fw-600 mb-3 mt-0">{!! trans('flutterwave::messages.current_card') !!}</h4>
                        
                        <ul class="dotted-list topborder section">
                            <li>
                                <div class="unit size1of2">
                                    <strong>{{ trans('flutterwave::messages.card.type') }}</strong>
                                </div>
                                <div class="lastUnit size1of2">
                                    <mc:flag><strong>{{ $flutterwave->getCard($invoice)['type'] }}</strong></mc:flag>
                                </div>
                            </li>
                            <li class="selfclear">
                                <div class="unit size1of2">
                                    <strong>{{ trans('flutterwave::messages.card.last4') }}</strong>
                                </div>
                                <div class="lastUnit size1of2">
                                    <mc:flag><strong>{{ $flutterwave->getCard($invoice)['last4'] }}</strong></mc:flag>
                                </div>
                            </li>
                        </ul>
                        
                        <form method="POST" action="{{ action("\Acelle\Flutterwave\Controllers\FlutterwaveController@checkout", [
                            'invoice_uid' => $invoice->uid,
                        ]) }}">
                            {{ csrf_field() }}
                            <input type="hidden" name="current_card" value="yes" />
                            <button  id="payWithCurrentCard" type="submit" class="mt-2 btn btn-secondary">{{ trans('cashier::messages.stripe.pay_with_this_card') }}</button>
                        </form>
                    </div>

                    <hr>
                    <h4 class="fw-600 mb-3 mt-0">{!! trans('flutterwave::messages.new_card') !!}</h4>
                    <p>{!! trans('cashier::messages.stripe.new_card.intro') !!}</p>

                    <form>
                        <button type="button" class="btn btn-secondary" id="start-payment-button" onclick="makePayment()">
                            {!! trans('flutterwave::messages.pay_with_new_card') !!}
                        </button>
                    </form>
                @else
                    <form>
                        <button type="button" class="btn btn-secondary" id="start-payment-button" onclick="makePayment()">
                            {!! trans('flutterwave::messages.pay_now') !!}
                        </button>
                    </form>
                @endif

                <script src="https://checkout.flutterwave.com/v3.js"></script>
                
                <script>
                    function makePayment() {
                        FlutterwaveCheckout({
                            public_key: "{{ $flutterwave->gateway->publicKey }}",
                            tx_ref: "ace-{{ $invoice->uid }}",
                            amount: {{ $invoice->total() }},
                            currency: "{{ $invoice->currency->code }}",
                            payment_options: "card, banktransfer, ussd",
                            redirect_url: "{{ action('\Acelle\Flutterwave\Controllers\FlutterwaveController@checkout', [
                                'invoice_uid' => $invoice->uid
                            ]) }}",
                            meta: {
                                consumer_id: '{{ $invoice->customer->uid }}',
                            },
                            customer: {
                                email: "{{ $invoice->billing_email }}",
                                phone_number: "{{ $invoice->billing_phone }}",
                                name: "{{ $invoice->billing_first_name }} {{ $invoice->billing_last_name }}",
                            },
                        });
                    }
                </script>

                <div class="my-4">
                    <hr>
                    <form id="cancelForm" method="POST" action="{{ action('SubscriptionController@cancelInvoice', [
                                'invoice_uid' => $invoice->uid,
                    ]) }}">
                        {{ csrf_field() }}
                        <a href="javascript:;" onclick="$('#cancelForm').submit()">
                            {{ trans('messages.subscription.cancel_now_change_other_plan') }}
                        </a>
                    </form>
                    
                </div>

            </div>
            <div class="col-md-2"></div>
        </div>
        <br />
        <br />
        <br />

        
    </body>
</html>