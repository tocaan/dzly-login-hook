<?php

namespace DzlyLoginHook\Http\Controllers;

use Illuminate\Http\Request;
use DzlyLoginHook\Events\OtpLoginStatusUpdated;
use DzlyLoginHook\Http\Requests\DzlyHookLoginRequest;
use DzlyLoginHook\Services\WhatsappAuthHandler;
use DzlyLoginHook\Events\DzlyHookEvent;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class WhatsappAuthHookController extends Controller
{

    public function __construct(protected WhatsappAuthHandler $whatsappAuthHandler)
    {
    }

    public function dzlyWebhook(Request $request)
    {
        Log::info('Dzly webhook received', ['request' => $request->all()]);
        $parsed = $this->whatsappAuthHandler->handleDzlyWebhook($request);
        $otpRequest = $this->whatsappAuthHandler->verifyOtp($parsed['message']);
        if (! $otpRequest) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP'], 422);
        }

        if ($otpRequest->is_expired) {
            broadcast(new OtpLoginStatusUpdated($otpRequest->serial_number, 'failed', __('OTP expired')));

            return response()->json(['status' => 'error', 'message' => 'OTP expired'], 422);
        }

        $handleMobileNumber = $this->whatsappAuthHandler->validatePhoneNumber($parsed['phone_number']);

        if (! $handleMobileNumber['success']) {
            broadcast(new OtpLoginStatusUpdated($otpRequest->serial_number, 'failed', __('Invalid phone number')));

            return response()->json(['status' => 'error', 'message' => 'Invalid phone number'], 422);
        }

        $otpRequest->update([
            'mobile' => $handleMobileNumber['phone_number'],
            'profile_name' => $parsed['profile_name'],
        ]);

        Event::dispatch(new DzlyHookEvent($otpRequest, $otpRequest->modelable));
        $message = $this->whatsappAuthHandler->getMessage($otpRequest->locale);
        $this->whatsappAuthHandler->sendDzlyMessage($message, $handleMobileNumber['phone_number']);

        broadcast(new OtpLoginStatusUpdated($otpRequest->serial_number, 'success'));

        return response()->json(['status' => 'success'], 200);
    }

    public function requestOtp(Request $request, $model)
    {
        $otp = $this->whatsappAuthHandler->generateOtp($request->serial_number, $model);
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

        $handleMobileNumber = $this->whatsappAuthHandler->validatePhoneNumber($requestStatus->mobile);
        $handleMobileNumber['profile_name'] = $requestStatus->profile_name;
        $requestStatus->delete();

        return $requestStatus->model_type::loggedInResponse($handleMobileNumber);
    }
}
