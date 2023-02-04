<?php

// Client View Groups
Route::group(['middleware' => ['web'], 'namespace' => '\Acelle\Baokim\Controllers'], function () {
    Route::match(['get', 'post'], 'plugins/acelle/flutterwave/{invoice_uid}/checkout', 'BaokimController@checkout');
});
