<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // پیامک — کلیدها ترجیحاً از پنل مدیریت (جدول settings) ثبت می‌شوند
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'sender_number' => env('SMS_SENDER_NUMBER'),
        'providers' => [
            'kavenegar' => ['name' => 'کاوه‌نگار'],
            'smsir' => ['name' => 'SMS.ir'],
            'mellipayamak' => ['name' => 'ملی‌پیامک'],
            'faraz' => ['name' => 'فراز اس‌ام‌اس'],
            'log' => ['name' => 'شبیه‌ساز (لاگ)'],
        ],
    ],

    // هوش مصنوعی (اختیاری)
    'ai' => [
        'provider' => env('AI_PROVIDER', 'none'),
        'openai_key' => env('AI_OPENAI_API_KEY'),
        'gemini_key' => env('AI_GEMINI_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
    ],

    // پرداخت
    'payment' => [
        'gateway' => env('PAYMENT_GATEWAY', 'mock'),
        'zarinpal_merchant_id' => env('PAYMENT_ZARINPAL_MERCHANT_ID'),
    ],

];
