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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'USD'),
        'auto_refund_duplicates' => env('AUTO_REFUND_DUPLICATES', false),
    ],

    'paymob' => [
        'api_key' => env('PAYMOB_API_KEY'),
        'iframe_id' => env('PAYMOB_IFRAME_ID'),
        'integrator_id' => env('PAYMOB_INTEGRATOR'),
        'hmac' => env('PAYMOB_HMAC'),
        'base_url' => env('PAYMOB_API_BASE', 'https://accept.paymob.com/api'),
        'ca_bundle' => env('PAYMOB_CA_BUNDLE', 'C:\\Users\\MSI\\cacert.pem'), // Path to CA certificate bundle for SSL verification
    ],

];
