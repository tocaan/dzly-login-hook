<?php

namespace DzlyLoginHook\Services;

use DzlyLoginHook\Models\OtpRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WhatsappAuthHandler
{
    private function getOtp()
    {
        $otp = rand(1000, 9999);

        if (OtpRequest::where('otp', $otp)->exists()) {
            // If this OTP already exists in cache, recursively call generateOtp to generate a new one
            return $this->getOtp();
        }

        return $otp;
    }

    public function enctyptOtp($id, $otp)
    {
        // Break otp into 3 digits, append id, then 3 digits
        $firstPart = substr($otp, 0, 2);
        $secondPart = substr($otp, 2, 2);
        $idPart = $id;
        $otp = $firstPart.$idPart.$secondPart;

        return $otp;
    }

    public function decryptOtp($otp)
    {
        // Extract the first number found between parentheses in the message
        preg_match('/\((\d+)\)/', $otp, $matches);
        if (! isset($matches[1])) {
            return false;
        }
        $otp = $matches[1];
        // Extract first 3 digits as OTP part, next is the ID, then last 3 digits as OTP part
        $firstPart = substr($otp, 0, 2);
        $idLength = strlen($otp) - 4;
        $idPart = substr($otp, 2, $idLength);
        $secondPart = substr($otp, 2 + $idLength, 2);

        $extractedOtp = $firstPart.$secondPart;
        $extractedId = $idPart;

        if (! $extractedOtp || ! $extractedId) {
            return false;
        }

        return [
            'otp' => $extractedOtp,
            'id' => $extractedId,
        ];
    }

    public function generateOtp($serialNumber = null, $model)
    {
        if ($serialNumber) {
            $requestData = OtpRequest::where('serial_number', $serialNumber)
            ->where('model_type', config("dzly-login-hook.supported_models.$model"))->first();

            if ($requestData) {
                $requestData->updated_at = Carbon::now();
                $requestData->save();
            }
        }

        if (! $requestData) {
            $requestData = OtpRequest::create([
                'serial_number' => $serialNumber ?? encrypt(uniqid()),
                'locale' => app()->getLocale(),
                'sender_type' => 'whatsapp',
                'model_type' => config("dzly-login-hook.supported_models.$model"),
            ]);
        }

        $requestData->otp = $this->getOtp();
        $requestData->save();

        return $this->buildOtpPayload($requestData);
    }

    private function buildOtpPayload(OtpRequest $requestData): array
    {
        $encryptedOtp = $this->enctyptOtp($requestData->id, $requestData->otp);

        return [
            'request_id' => $requestData->id,
            'is_expired' => $requestData->is_expired,
            'expires_at' => $requestData->expir_at->toIso8601String(),
            'serial_number' => $requestData->serial_number,
            'channel' => self::otpChannelName($requestData->serial_number),
            'otp' => $encryptedOtp,
            'url' => $this->generateWhatsappUrl($encryptedOtp),
        ];
    }

    public static function otpChannelName(string $serialNumber): string
    {
        return 'otp.'.sha1($serialNumber);
    }

    public function handleDzlyWebhook(Request $request): ?array
    {
        if ($request->event !== 'message.received') {

            return null;
        }

        $value = $request->data['data']['value'];
        $message = $value['messages'][0] ?? null;

        if (! $message || ($message['type'] ?? null) !== 'text') {
            \Log::channel('info')->info('DZLY Webhook message not found');
            return null;
        }

        $contact = $value['contacts'][0] ?? null;

        $parsed = [
            'message' => $message['text']['body'] ?? null,
            'phone_number' => $message['from'] ?? null,
            'profile_name' => $contact['profile']['name'] ?? null,
        ];

        return $parsed;
    }

    public function verifyOtp($otp)
    {
        $decryptedOtp = $this->decryptOtp($otp);
        if (! $decryptedOtp) {
            return false;
        }

        $request = OtpRequest::where($decryptedOtp)->first();
        if (! $request) {
            return false;
        }

        $request->status = 'verified';
        $request->save();

        return $request;
    }

    public function getMessage($locale = 'ar')
    {
        $messages = __("Login successful", locale: $locale)." 👍🏻 ".__("Please follow the instructions inside the app", locale: $locale);
        return $messages;
    }

    public function getRequestStatus($requestId, $serialNumber)
    {
        $request = OtpRequest::where('serial_number', $serialNumber)->find($requestId);
        if (! $request) {
            return false;
        }

        return $request;
    }

    private function generateWhatsappUrl($otp)
    {
        $reciverNumber = preg_replace('/\D/', '', config('dzly-login-hook.whatsapp_reciver_number'));
        $message = __("Send Message Without Modification", locale: locale());
        $message .= " ($otp)";

        return 'https://wa.me/'.$reciverNumber.'?text='.rawurlencode($message);
    }
    static function sendDzlyMessage($message, $phone)
    {
        try {
            $dzly = new DzlyClient(config('dzly.base_url'), config('dzly.token'));
            $dzly->messages()->send([
                'phone' => $phone,
                'message' => $message,
            ]);

        } catch (\Exception $e) {

            return ["Result" => "false"];
        }
    }
}
