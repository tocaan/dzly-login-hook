<?php
namespace DzlyLoginHook\Contracts;

use Illuminate\Http\JsonResponse;

interface DzlyLoginHook
{
    public static function loggedInResponse($phoneData): JsonResponse;
}
