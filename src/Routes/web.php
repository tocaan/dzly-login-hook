<?php
use Illuminate\Support\Facades\Route;
use DzlyLoginHook\Http\Controllers\WhatsappAuthHookController;

Route::name('api.dzly.login.hook.')
    ->prefix('api/dzly-login-hook')
    ->middleware('api')
    ->group(function () {

        Route::post('request-otp/{model}', [WhatsappAuthHookController::class, 'requestOtp'])->name('request.otp');
        Route::any('dzly-webhook', [WhatsappAuthHookController::class, 'dzlyWebhook'])->name('dzly.webhook');
        Route::post('hook-otp-login', [WhatsappAuthHookController::class, 'login'])->name('login');
    });