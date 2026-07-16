<?php

namespace DzlyLoginHook\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpRequest extends Model
{
    protected $table = 'dzly_hook_otp_requests';
    protected $fillable = ['otp', 'mobile', 'profile_name', 'model_type', 'serial_number', 'locale', 'status'];


    public function getIsExpiredAttribute()
    {
        return Carbon::now()->gte(Carbon::parse($this->updated_at)->addMinutes(5));
    }

    public function getExpirAtAttribute()
    {
        return Carbon::parse($this->updated_at)->addMinutes(5);
    }

    public function modelable()
    {
        return $this->morphTo();
    }
}
