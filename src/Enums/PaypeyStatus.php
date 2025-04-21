<?php

namespace Gcorpllc\Paypey\Enums;

use Illuminate\Support\Facades\Lang;
// Arrayable نیازی نیست مگر اینکه Enum شما آن را پیاده‌سازی کند
// use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use Throwable;

/**
 * Enum representing various payment statuses and errors for Paypey package.
 *
 * Backed by string values.
 */
enum PaypeyStatus: string
{
    // --- خطاهای عمومی یا خطاهایی که می‌توانند در هر درگاهی رخ دهند ---
    case GENERAL_ERROR = 'general_error'; // استفاده از یک رشته توصیفی بهتر
    case INVALID_AMOUNT = 'invalid_amount';
    case MISSING_CALLBACK_URL = 'missing_callback_url';
    case CONNECTION_FAILED = 'connection_failed'; // خطای ارتباط با سرور درگاه
    case INVALID_RESPONSE = 'invalid_response'; // پاسخ نامعتبر یا غیرمنتظره از درگاه
    case TRANSACTION_NOT_FOUND = 'transaction_not_found'; // تراکنش برای تایید یافت نشد (در سیستم خودمان)
    case AUTHORITY_MISMATCH = 'authority_mismatch'; // عدم تطابق Authority حین تایید
    case TRANSACTION_ALREADY_PROCESSED = 'transaction_already_processed'; // تراکنش قبلاً در سیستم ما پردازش شده

    // --- خطاهای خاص درگاه زرین‌پال (استفاده از کدهای خطای واقعی زرین‌پال به صورت رشته) ---
    // استفاده از رشته‌های توصیفی یا کدهای واقعی درگاه به صورت رشته
    case ZARINPAL_INVALID_MERCHANT_ID = 'zarinpal_-9'; // مثال: اضافه کردن پیشوند درگاه + کد
    case ZARINPAL_INVALID_AMOUNT = 'zarinpal_-10';
    case ZARINPAL_CALLBACK_URL_INVALID = 'zarinpal_-12';
    case ZARINPAL_EXPIRED_AUTHORITY = 'zarinpal_-15';
    case ZARINPAL_INSUFFICIENT_FUNDS = 'zarinpal_-16'; // خطای بانکی درگاه
    case ZARINPAL_PAYMENT_CANCELED_BY_USER = 'zarinpal_-18';
    case ZARINPAL_AUTHORITY_NOT_FOUND = 'zarinpal_-21';
    case ZARINPAL_TRANSACTION_ALREADY_VERIFIED = 'zarinpal_101'; // این کد موفقیت است اما ممکن است در بلوک خطا گرفته شود
    case ZARINPAL_SUCCESS = 'zarinpal_100'; // کد موفقیت درگاه

    // --- وضعیت‌های اصلی چرخه پرداخت ---
    case PENDING = 'pending';
    case PAID = 'paid'; // یا Completed
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded'; // اگر پشتیبانی می‌کنید
    case REFUND_FAILED = 'refund_failed'; // اگر پشتیبانی می‌کنید

    /**
     * Get the translation key for the enum case.
     *
     * This method determines the key used for translation in language files.
     * It follows the pattern 'package_namespace::filename.enum_case_name_lowercase'.
     *
     * @return string
     */
    public function transKey(): string
    {
        // استفاده از فضای نام پکیج (paypey) و نام فایل زبان (paypay) و نام کیس با حروف کوچک
        return 'paypey::errors.' . strtolower($this->name);
    }

    /**
     * Get the translated error message for the enum case.
     *
     * @param array $replace Placeholder replacements for the translation string (e.g., [':amount' => $amount]).
     * @param string|null $locale The locale to use for translation.
     * @return string
     */
    public function trans(array $replace = [], ?string $locale = null): string
    {
        // Lang::get به دنبال کلید ترجمه در فایل‌های زبان می‌گردد.
        // کلید توسط transKey() تولید می‌شود.
        return Lang::get($this->transKey(), $replace, $locale);
    }

    /**
     * Get the raw string value associated with the enum case.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }


}
