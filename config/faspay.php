<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Faspay Mode (Sandbox vs Production)
    |--------------------------------------------------------------------------
    */
    'is_production' => (bool) env('FASPAY_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Merchant Credentials
    |--------------------------------------------------------------------------
    */
    'merchant_id'   => env('FASPAY_MERCHANT_ID', ''),
    'merchant_name' => env('FASPAY_MERCHANT_NAME', 'The Arena'),
    'user_id'       => env('FASPAY_USER_ID', ''),
    'password'      => env('FASPAY_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'base_url' => env('FASPAY_BASE_URL') 
        ?: (env('FASPAY_IS_PRODUCTION', false)
            ? 'https://xpress.faspay.co.id/v4/post'
            : 'https://xpress-sandbox.faspay.co.id/v4/post'
        ),

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    | CRITICAL: Callback URL harus bisa diakses dari internet (bukan localhost)
    | Gunakan ngrok untuk local testing
    |--------------------------------------------------------------------------
    */
    'callback_url' => env(
        'FASPAY_CALLBACK_URL', 
        rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/api/payment/faspay/callback'
    ),
    
    'return_url' => env(
        'FASPAY_RETURN_URL', 
        rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/payment/faspay/return'
    ),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'payment_timeout'       => (int) env('FASPAY_PAYMENT_TIMEOUT', 120), // minutes
    'currency'              => 'IDR',
    'price_per_booking'     => (int) env('BASKET_PRICE_PER_BOOKING', 150000),

    /*
    |--------------------------------------------------------------------------
    | Available Payment Channels
    |--------------------------------------------------------------------------
    */
    'channels' => [
        '402' => ['code' => '402', 'name' => 'BRI Virtual Account',     'icon' => 'bank-bri'],
        '403' => ['code' => '403', 'name' => 'BNI Virtual Account',     'icon' => 'bank-bni'],
        '401' => ['code' => '401', 'name' => 'Mandiri Virtual Account', 'icon' => 'bank-mandiri'],
        '408' => ['code' => '408', 'name' => 'Permata Virtual Account', 'icon' => 'bank-permata'],
        '701' => ['code' => '701', 'name' => 'QRIS',                    'icon' => 'qris'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Developer Mode
    |--------------------------------------------------------------------------
    */
    'dev_mode' => [
        'enabled'        => env('APP_ENV') === 'local',
        'auto_approve'   => false,
        'simulate_delay' => 0,
    ],
];