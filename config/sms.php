<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS service provider
    |
    */

    'username' => env('SMS_USERNAME'),
    'password' => env('SMS_PASSWORD'),
    'base_url' => env('SMS_BASE_URL', 'http://107.20.199.106'),
    'from' => env('SMS_FROM', 'GRAVITY_CBC'),
    
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for SMS service
    |
    */
    
    'timeout' => env('SMS_TIMEOUT', 30),
    'retry_attempts' => env('SMS_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('SMS_RETRY_DELAY', 1000), // milliseconds
    
    /*
    |--------------------------------------------------------------------------
    | System SMS Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic system SMS notifications
    |
    */
    
    'send_system_sms' => env('SEND_SYSTEM_SMS', false) === '1' || env('SEND_SYSTEM_SMS', false) === true,
    
    /*
    |--------------------------------------------------------------------------
    | SMS Templates
    |--------------------------------------------------------------------------
    |
    | Templates for different types of system SMS notifications
    | Use placeholders like {name}, {phone}, {tokens}, {amount}, {code}
    |
    */
    
    'templates' => [
        'registration' => env('SMS_REGISTRATION_TEMPLATE', 'Welcome {name}! Your Gravity CBC account has been created successfully. You can now access assessments and track your progress.'),
        'payment_success' => env('SMS_PAYMENT_SUCCESS_TEMPLATE', 'Payment successful! {tokens} tokens worth KES {amount} have been credited to your Gravity CBC account. Your new balance is {balance} tokens.'),
        'password_reset' => env('SMS_PASSWORD_RESET_TEMPLATE', 'Your Gravity CBC password reset code is: {code}. This code expires in 15 minutes. Do not share this code with anyone.'),
    ],
];
