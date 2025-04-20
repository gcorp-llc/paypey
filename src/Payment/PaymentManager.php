<?php

namespace Gcorpllc\Paypey\Payment;

use InvalidArgumentException;
class PaymentManager
{
    protected array $gateways = [
        'zarinpal' => ZarinpalGateway::class,
        'paypal' => PayPalGateway::class,
        'stripe' => StripeGateway::class,
    ];

    public function driver(string $gateway = null): PaymentGateway
    {
        $gateway = $gateway ?? config('paypey.default_gateway');
        if (!isset($this->gateways[$gateway])) {

        }
        return new $this->gateways[$gateway]();
    }

}
