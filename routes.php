<?php

// Client View Groups
Route::group(['middleware' => ['web'], 'namespace' => '\Acelle\Flutterwave\Controllers'], function () {
    Route::get('plugins/acelle/flutterwave', 'DashboardController@index');

    // 
    Route::match(['get', 'post'], '/plugins/acelle/flutterwave/auto-billing-update', 'FlutterwaveController@autoBillingDataUpdate');
    Route::match(['get', 'post'], 'plugins/acelle/flutterwave/{invoice_uid}/checkout', 'FlutterwaveController@checkout');
    Route::match(['get', 'post'], 'plugins/acelle/flutterwave/settings', 'FlutterwaveController@settings');
});
