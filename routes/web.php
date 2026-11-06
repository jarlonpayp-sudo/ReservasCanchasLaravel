<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SoapController;

Route::get('/', function () {
    return view('welcome');
});

Route::match(
    ['GET', 'POST'],
    '/soap',
    [SoapController::class, 'handle']
);