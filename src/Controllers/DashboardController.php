<?php

namespace Acelle\Baokim\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Baokim\Baokim;

class DashboardController extends BaseController
{
    public function index(Request $request)
    {
        return view('baokim::index', [
            'baokim' => Baokim::initialize(),
        ]);
    }
}
