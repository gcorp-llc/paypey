<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway driver that will be
    | used by the library. You can specify any of the supported drivers.
    |
    */
    'default' => env('PAYPEY_DEFAULT_GATEWAY', 'zarinpal'),

    /*
    |--------------------------------------------------------------------------
    | Global Currency & Conversion
    |--------------------------------------------------------------------------
    |
    | Default app currency unit: 'toman' or 'rial'.
    | Iranian gateways expecting Rials will automatically convert Toman amounts
    | by multiplying by 10 if currency is set to 'toman'.
    |
    */
    'currency' => env('PAYPEY_CURRENCY', 'toman'),

    /*
    |--------------------------------------------------------------------------
    | Database Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic transaction logging to `paypey_transactions`.
    |
    */
    'database_logging' => env('PAYPEY_DB_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Global Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | Toggle sandbox mode globally across all gateways if supported.
    |
    */
    'sandbox' => env('PAYPEY_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and configurations for each supported gateway driver.
    |
    */
    'gateways' => [

        'sandbox' => [
            'mode' => 'sandbox',
        ],

        'zarinpal' => [
            'merchant_id' => env('ZARINPAL_MERCHANT_ID', 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'),
            'sandbox' => env('ZARINPAL_SANDBOX', true),
            'currency' => env('ZARINPAL_CURRENCY', 'toman'), // toman or rial
            'mode' => env('ZARINPAL_MODE', 'normal'), // normal or zaringate
        ],

        'mellat' => [
            'terminal_id' => env('MELLAT_TERMINAL_ID', ''),
            'username' => env('MELLAT_USERNAME', ''),
            'password' => env('MELLAT_PASSWORD', ''),
            'currency' => 'rial',
        ],

        'saman' => [
            'terminal_id' => env('SAMAN_TERMINAL_ID', ''),
            'merchant_id' => env('SAMAN_MERCHANT_ID', ''),
            'currency' => 'rial',
        ],

        'parsian' => [
            'pin' => env('PARSIAN_PIN', ''),
            'currency' => 'rial',
        ],

        'idpay' => [
            'api_key' => env('IDPAY_API_KEY', ''),
            'sandbox' => env('IDPAY_SANDBOX', true),
            'currency' => 'rial',
        ],

        'nextpay' => [
            'api_key' => env('NEXTPAY_API_KEY', ''),
            'currency' => 'rial',
        ],

        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
            'currency' => env('STRIPE_CURRENCY', 'USD'),
        ],

        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'currency' => env('PAYPAL_CURRENCY', 'USD'),
        ],
    ],
];
