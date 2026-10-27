<?php

use Illuminate\Support\Facades\Route;
use TinoPay\TinoPay\Http\Controllers\TinoPayController;

Route::group([
    'prefix' => 'api/tinopay',
    'as' => 'tinopay.',
    'middleware' => ['api'],
], function () {
    Route::post('/webhook', [TinoPayController::class, 'webhook'])->name('webhook');
});
