<?php

namespace Gcorpllc\Paypey\Exceptions;
use Exception;
use Gcorpllc\Paypey\Enums\PaypeyStatus;
class PaypeyException extends Exception
{
    protected PaypeyStatus $PaypeyStatus;

    public function __construct(PaypeyStatus $PaypeyStatus, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $this->PaypeyStatus = $PaypeyStatus;
        // شما می‌توانید پیام پیش‌فرض یا پیام وضعیت پرداخت را استفاده کنید
        parent::__construct($message ?: $PaypeyStatus->trans(), $code, $previous);

    }

    // متدی برای دسترسی به وضعیت پرداخت
    public function getPaypeyStatus(): PaypeyStatus
    {
        return $this->PaypeyStatus;
    }

    // می‌توانید متدهای دیگری برای گرفتن اطلاعات مورد نیاز در JSON اضافه کنید
    public function getJsonErrorDetails(): array
    {
        return [
            'code' => $this->PaypeyStatus->value, // یا هر کد دیگری که نیاز دارید
            'message' => $this->getMessage(),
            // اطلاعات دیگر در صورت نیاز
        ];
    }
}
