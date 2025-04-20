<?php

namespace Gcorpllc\Paypey\Payment;

class StripeGateway implements PaymentGateway
{
    public function purchase(float $amount, array $options = []): string
    {
        return "پرداخت {$amount} تومان با striper انجام شد.";
    }
}
