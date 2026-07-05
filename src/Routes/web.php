<?php
use Illuminate\Support\Facades\Route;
use DzlyLoginHook\Http\Controllers\WhatsappAuthHookController;

Route::name('dzly.login.hook.')
    ->prefix('dzly-login-hook')
    ->group(function () {

    Route::post( 'request-otp/{model}', [WhatsappAuthHookController::class, 'requestOtp'])->name('request.otp');
    Route::any( 'dzly-webhook', [WhatsappAuthHookController::class, 'dzlyWebhook'])->name('dzly.webhook');
    Route::post('hook-otp-login', [WhatsappAuthHookController::class, 'login'])->name('login');
});