<?php

namespace DzlyLoginHook\Http\Controllers;

use Illuminate\Http\Request;
use DzlyLoginHook\Events\OtpLoginStatusUpdated;
use DzlyLoginHook\Http\Requests\DzlyHookLoginRequest;
use DzlyLoginHook\Services\WhatsappAuthHandler;
use DzlyLoginHook\Services\Dzly;
use Illuminate\Routing\Controller;

class WhatsappAuthHookController extends Controller
{

    public function __construct(protected WhatsappAuthHandler $whatsappAuthHandler)
    {
    }

    public function dzlyWebhook(Request $request)
    {
        $parsed = $this->whatsappAuthHandler->handleDzlyWebhook($request);
        $otpRequest = $this->whatsappAuthHandler->verifyOtp($parsed['message']);
        if (! $otpRequest) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP'], 422);
        }

        if ($otpRequest->is_expired) {
            broadcast(new OtpLoginStatusUpdated($otpRequest->serial_number, 'failed', __('OTP expired')));

            return response()->json(['status' => 'error', 'message' => 'OTP expired'], 422);
        }

        $handleMobileNumber = validatePhoneNumber('',$parsed['phone_number']);

        if (! $handleMobileNumber['success']) {
            broadcast(new OtpLoginStatusUpdated($otpRequest->serial_number, 'failed', __('Invalid phone number')));

            return response()->json(['status' => 'error', 'message' => 'Invalid phone number'], 422);
        }

        //phone is verfied , event take otprequest and phone number
        // $user = $this->auth->loginOrRegister($handleMobileNumber['phone_number']);

        $user->refresh();
        if ($user->first_login) {
            $user->update(['name' => $parsed['profile_name']]);
        }

        $otpRequest->user_id = $user->id;
        $otpRequest->save();

        $message = $this->whatsappAuthHandler->getMessage($otpRequest->locale);
        Dzly::sendMessage($message, $handleMobileNumber['phone_number']);

        broadcast(new OtpLoginStatusUpdated($otpRequest->serial_number, 'success'));

        return response()->json(['status' => 'success'], 200);
    }

    public function requestOtp(Request $request, $model)
    {
        $otp = $this->whatsappAuthHandler->generateOtp($request->serial_number,$model);
        return response()->json(['status' => 'success', 'data' => $otp], 200);
    }

    public function login(DzlyHookLoginRequest $request)
    {
        $requestStatus = $this->whatsappAuthHandler->getRequestStatus($request->request_id, $request->serial_number);

        if (! $requestStatus) {
            return response()->json(['status' => 'error', 'message' => 'Request not found'], 422);
        }

        if ($requestStatus->status == 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Request is pending'], 422);
        }

        if ($requestStatus->is_expired) {
            return response()->json(['status' => 'error', 'message' => 'OTP expired'], 422);
        }

        $user = $requestStatus->user;
        $requestStatus->delete();

        return $this->tokenResponse($user);
    }
}
