<?php
return [

    'default_gateway' => env('PAYPEY_DEFAULT_GATEWAY', 'zarinpal'),
    'callbackUrl' =>  env('CALLBACK_URL', '/callback'),
    'sandbox' => env('PAYPEY_SANDBOX', true),
    'gateways' => [
        'zarinpal' => [
            'sandbox' => env('ZARINPAL_SANDBOX', false),// can be normal, sandbox, zaringate
            'merchantId' =>  env('ZARINPAL_MERCHANT_ID', 'zarinpal'),
            'description' => 'payment using zarinpal',
            'currency' => env('CURRENCY', 'T'), //Can be R, T (Rial, Toman)
        ],
    ],

];
