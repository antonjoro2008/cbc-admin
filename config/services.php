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

    'coop' => [
        'base_url' => env('COOP_BASE_URL', 'https://openapi.co-opbank.co.ke'),
        'token_url' => env('COOP_TOKEN_URL', 'https://openapi.co-opbank.co.ke/token'),
        'stk_url' => env('COOP_STK_URL', 'https://openapi.co-opbank.co.ke/FT/stk/1.0.0'),
        'status_url' => env('COOP_STK_STATUS_URL', 'https://openapi.co-opbank.co.ke/Enquiry/STK/1.0.0/'),
        'consumer_key' => env('COOP_CONSUMER_KEY'),
        'consumer_secret' => env('COOP_CONSUMER_SECRET'),
        'operator_code' => env('COOP_OPERATOR_CODE', 'GRAVITYCBC'),
        'narration' => env('COOP_NARRATION', 'GRAVITY CBC CENTER'),
        'callback_url' => env('COOP_CALLBACK_URL'),
        'callback_token' => env('COOP_CALLBACK_TOKEN'),
        'ipn_username' => env('COOP_IPN_USERNAME'),
        'ipn_password' => env('COOP_IPN_PASSWORD'),
        'ipn_token' => env('COOP_IPN_TOKEN'),
        'account_number' => env('COOP_ACCOUNT_NUMBER'),
        'timeout' => (int) env('COOP_TIMEOUT', 30),
    ],

];
