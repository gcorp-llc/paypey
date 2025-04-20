<?php

namespace Gcorpllc\Paypey\Payment;

class PayPalGateway implements PaymentGateway
{
    public function purchase(float $amount, array $options = []): string
    {
        return "پرداخت {$amount} تومان با paypal انجام شد.";
    }
}
