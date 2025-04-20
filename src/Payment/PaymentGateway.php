<?php

namespace Gcorpllc\Paypey\Payment;

interface PaymentGateway
{
    public function amount(int $amount):static;
    public function callbackUrl(string $callbackUrl);
    public function reverse();
    public function purchase();
}
