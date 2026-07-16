# Dzly Login Hook

Laravel package for WhatsApp OTP login via [Dzly](https://app.dzly.ai).

## Installation

### 1. Add the VCS repository

Add the following to your application's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/tocaan/dzly-login-hook.git"
    }
]
```

### 2. Require the package

```bash
composer require tocaan/dzly-login-hook
```

### 3. Publish the config

```bash
php artisan vendor:publish --provider="DzlyLoginHook\Providers\DzlyLoginHookServiceProvider" --tag=config
```

### 4. Run migrations

```bash
php artisan migrate
```

This creates the `dzly_hook_otp_requests` table.

### 5. Environment variables

Add the following to your `.env` file:

```env
WHATSAPP_RECIVER_NUMBER="your dzly phone number"
DZLY_BASE_URL="https://app.dzly.ai"
DZLY_API_TOKEN="your token"
```

| Variable | Description |
|---|---|
| `WHATSAPP_RECIVER_NUMBER` | Your Dzly WhatsApp receiver number (used by this package) |
| `DZLY_BASE_URL` | Dzly API base URL (used by `dzly/dzly-api`) |
| `DZLY_API_TOKEN` | Your Dzly API token (used by `dzly/dzly-api`) |

### 6. Register the Dzly webhook

In the [Dzly webhooks dashboard](https://app.dzly.ai/developer-tools/webhooks), add a webhook that points to:

```
{{YOUR_PROJECT_URL}}/api/dzly-login-hook/dzly-webhook
```

Replace `{{YOUR_PROJECT_URL}}` with your application's public URL (for example `https://example.com`).

### 7. Register supported models

Open `config/dzly-login-hook.php` and map model keys to their classes:

```php
return [
    'supported_models' => [
        'user' => \App\Models\User::class,
        // 'admin' => \App\Models\Admin::class,
    ],
    'whatsapp_reciver_number' => env('WHATSAPP_RECIVER_NUMBER'),
];
```

The key (`user`) is used in the OTP request URL: `/api/dzly-login-hook/request-otp/{model}`.

### 8. Implement the `DzlyLoginHook` contract

Each model listed in `supported_models` must implement `DzlyLoginHook\Contracts\DzlyLoginHook` and define `loggedInResponse`:

```php
<?php

namespace App\Models;

use DzlyLoginHook\Contracts\DzlyLoginHook;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\JsonResponse;

class User extends Authenticatable implements DzlyLoginHook
{
    public static function loggedInResponse($phoneData): JsonResponse
    {
        $user = static::firstOrCreate(
            ['phone' => $phoneData['phone_number']],
            ['name' => $phoneData['profile_name'] ?? null]
        );

        $token = $user->createToken('dzly-login')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }
}
```

#### `$phoneData` shape

`$phoneData` passed to `loggedInResponse` looks like:

```php
[
  "success" => true,
  "phone_number" => "+201283541206",
  "national_number" => "1283541206",
  "country_code" => "+20",
  "phone_object" => /* libphonenumber\PhoneNumber instance */,
  "profile_name" => "Mostafa Sewidan",
]
```

| Key | Type | Description |
|---|---|---|
| `success` | `bool` | Whether phone validation succeeded |
| `phone_number` | `string` | E.164 formatted number (e.g. `+201283541206`) |
| `national_number` | `string` | National number without country code |
| `country_code` | `string` | Country dial code with `+` (e.g. `+20`) |
| `phone_object` | `libphonenumber\PhoneNumber` | Parsed phone number object |
| `profile_name` | `string\|null` | WhatsApp profile name from the inbound message |

## Available endpoints

| Method | URI | Description |
|---|---|---|
| `POST` | `/api/dzly-login-hook/request-otp/{model}` | Generate an OTP and WhatsApp deep link for the given model key |
| `ANY` | `/api/dzly-login-hook/dzly-webhook` | Inbound Dzly webhook (OTP verification) |
| `POST` | `/api/dzly-login-hook/hook-otp-login` | Complete login after OTP is verified; calls `loggedInResponse` on the model |

## License

MIT
