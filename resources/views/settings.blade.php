@extends('layouts.core.backend')

@section('title', trans('flutterwave::messages.flutterwave'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\PaymentController@index") }}">{{ trans('messages.payment_gateways') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.update') }}</li>
        </ul>
        <h1>
            <span class="text-semibold">
                <span class="material-symbols-rounded">
                    payments
                </span>
                {{ trans('flutterwave::messages.flutterwave') }}</span>
        </h1>
    </div>

@endsection

@section('content')
    <h3 class="">{{ trans('flutterwave::messages.connection') }}</h3>
    <p>
        {!! trans('flutterwave::messages.settings.intro') !!}
    </p>

    <form enctype="multipart/form-data" action="{{ $flutterwave->gateway->getSettingsUrl() }}" method="POST" class="form-validate-jquery">
        {{ csrf_field() }}
        <div class="row">
            <div class="col-md-6">
                @include('helpers.form_control', [
                    'type' => 'text',
                    'class' => '',
                    'name' => 'public_key',
                    'value' => $flutterwave->gateway->publicKey,
                    'label' => trans('flutterwave::messages.public_key'),
                    'help_class' => 'payment',
                    'rules' => ['public_key' => 'required'],
                ])

                @include('helpers.form_control', [
                    'type' => 'text',
                    'class' => '',
                    'name' => 'secret_key',
                    'value' => $flutterwave->gateway->secretKey,
                    'label' => trans('flutterwave::messages.secret_key'),
                    'help_class' => 'payment',
                    'rules' => ['secret_key' => 'required'],
                ])
            </div>
        </div>

        <div class="text-left">
            @if ($flutterwave->gateway->isActive())
                @if (!\Acelle\Library\Facades\Billing::isGatewayEnabled($flutterwave->gateway))
                    <input type="submit" name="enable_gateway" class="btn btn-primary me-1" value="{{ trans('cashier::messages.save_and_enable') }}" />
                    <button class="btn btn-default me-1">{{ trans('messages.save') }}</button>
                @else
                    <button class="btn btn-primary me-1">{{ trans('messages.save') }}</button>
                @endif
            @else
                <input type="submit" name="enable_gateway" class="btn btn-primary me-1" value="{{ trans('cashier::messages.connect') }}" />
            @endif

            @if($flutterwave->plugin->isActive())
                <a class="btn btn-default" href="{{ action('Admin\PaymentController@index') }}">{{ trans('cashier::messages.cancel') }}</a>
            @else
                <a class="btn btn-default" href="{{ action('Admin\PluginController@index') }}">{{ trans('cashier::messages.cancel') }}</a>
            @endif
        </div>

    </form>
       
@endsection