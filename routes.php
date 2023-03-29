<?php

// Client View Groups
Route::group(['middleware' => ['web'], 'namespace' => '\Acelle\Baokim\Controllers'], function () {
    Route::match(['get', 'post'], 'plugins/acelle/baokim/{invoice_uid}/checkout/hook', 'BaokimController@checkoutHook');
    Route::match(['get', 'post'], 'plugins/acelle/baokim/{invoice_uid}/checkout/success', 'BaokimController@checkoutSuccess');
    Route::match(['get', 'post'], 'plugins/acelle/baokim/{invoice_uid}/checkout/detail', 'BaokimController@checkoutDetail');
    Route::match(['get', 'post'], 'plugins/acelle/baokim/{invoice_uid}/checkout', 'BaokimController@checkout');
});
