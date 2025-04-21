<?php

namespace Gcorpllc\Paypey\Payment;

use Gcorpllc\Paypey\Drivers\PaymentGateway;
use Gcorpllc\Paypey\Drivers\ZarinpalGateway;
use Gcorpllc\Paypey\Enums\PaypeyStatus;
use Gcorpllc\Paypey\Exceptions\PaypeyException;
use InvalidArgumentException;

class PaymentManager
{
    protected array $gateways = [
        'zarinpal' => ZarinpalGateway::class,
    ];

    public function driver(string $gateway = null): PaymentGateway
    {
        $gateway = $gateway ?? config('paypey.default_gateway');
        if (!isset($this->gateways[$gateway])) {
            // اگر درگاه پیدا نشد، خطا پرتاب می‌شود
            throw new PaypeyException(PaypeyStatus::REFUND_FAILED); // یا هر وضعیت دیگری که مناسب است
        }
        return new $this->gateways[$gateway]();
    }

}
