<?php
namespace  DzlyLoginHook\Events;

class DzlyHookEvent
{
    public function __construct(public $otpRequest,public $user)
    {
        //
    }
}
