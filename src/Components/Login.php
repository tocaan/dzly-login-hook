<?php
namespace DzlyLoginHook\Components;

use Illuminate\View\Component;

class Login extends Component
{
    public function __construct()
    {
    }

    public function render()
    {
        return view('dzly-login-hook::components.login');
    }
}
