<?php

namespace DzlyLoginHook\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DzlyHookLoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'request_id' => 'required|exists:dzly_hook_otp_requests,id',
            'serial_number' => 'required',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
