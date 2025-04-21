<?php
return [
    // ترجمه خطاهای عمومی (کلیدها مطابق با خروجی strtolower($this->name) در Enum)
    'general_error' => 'خطای نامشخص در فرآیند پرداخت رخ داده است.',
    'invalid_amount' => 'مبلغ پرداخت نامعتبر است.',
    'missing_callback_url' => 'آدرس بازگشت پرداخت تعیین نشده است.',
    'connection_failed' => 'خطا در ارتباط با سرور درگاه پرداخت رخ داده است. لطفاً بعداً تلاش کنید.',
    'invalid_response' => 'پاسخ دریافتی از درگاه پرداخت نامعتبر است.',
    'transaction_not_found' => 'تراکنش مورد نظر در سیستم ما یافت نشد یا وضعیت آن معتبر نیست.',
    'authority_mismatch' => 'اطلاعات بازگشتی از درگاه با اطلاعات اولیه مطابقت ندارد.',
    'transaction_already_processed' => 'این تراکنش قبلاً در سیستم شما پردازش و ثبت نهایی شده است.',

    // ترجمه خطاهای خاص زرین‌پال (کلیدها مطابق با خروجی strtolower($this->name) در Enum)
    'zarinpal_invalid_merchant_id' => 'شناسه پذیرنده (Merchant ID) زرین‌پال نامعتبر است.',
    'zarinpal_invalid_amount' => 'مبلغ تراکنش در زرین‌پال نامعتبر است.',
    'zarinpal_callback_url_invalid' => 'آدرس بازگشت (Callback URL) در زرین‌پال نامعتبر است.',
    'zarinpal_expired_authority' => 'مهلت استفاده از کد پیگیری (Authority) زرین‌پال منقضی شده است.',
    'zarinpal_insufficient_funds' => 'اعتبار حساب بانکی پرداخت کننده کافی نیست.',
    'zarinpal_payment_canceled_by_user' => 'پرداخت توسط کاربر در صفحه درگاه بانکی لغو شد.',
    'zarinpal_authority_not_found' => 'کد پیگیری (Authority) مورد نظر در سیستم زرین‌پال یافت نشد.',
    'zarinpal_transaction_already_verified' => 'این تراکنش قبلاً در زرین‌پال تایید نهایی شده بود.',
    'zarinpal_success' => 'تراکنش با موفقیت در زرین‌پال انجام و تایید شد.',
    'refund_failed'=>'درگاه نا متعبر است'
];
