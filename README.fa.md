# پکیج پرداخت آرپایج Paypey برای لاراول

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gcorpllc/paypey.svg?style=flat-square)](https://packagist.org/packages/gcorpllc/paypey)
[![Total Downloads](https://img.shields.io/packagist/dt/gcorpllc/paypey.svg?style=flat-square)](https://packagist.org/packages/gcorpllc/paypey)
[![License](https://img.shields.io/packagist/l/gcorpllc/paypey.svg?style=flat-square)](LICENSE)

**Paypey** یک پکیج حرفه‌ای و قدرتمند مبتنی بر الگوی Driver (Strategy Pattern) برای اتصال به درگاه‌های پرداخت ایرانی و بین‌المللی در پروژه‌های لاراول است.

---

## زبان‌های مستندات
- [راهنمای انگلیسی (English Documentation)](README.md)
- [راهنمای فارسی (Persian Documentation)](README.fa.md)

---

## ویژگی‌ها

- **API روان و تمیز:** متدهای زنجیره‌ای و خوانا (`Paypey::via('zarinpal')->amount(10000)->request()`).
- **معماری درایور محور:** تغییر آسان درگاه‌ها یا تعریف درایور اختصاصی با `Paypey::extend()`.
- **درگاه‌های پشتیبانی‌شده:**
  - **تست / Sandbox** (بدون نیاز به شبکه برای محیط توسعه)
  - **زرین‌پال** (عادی و زرین‌گیت)
  - **بانک ملت** (به‌پرداخت)
  - **سامان** (SEP)
  - **پارسیان** (PEC)
  - **آیدی پی** (IdPay)
  - **نکست پی** (NextPay)
  - **استرایپ و پی‌پال** (درگاه‌های بین‌المللی)
- **تبدیل خودکار واحد پول (تومان / ریال):** تبدیل هوشمند مبلغ بر اساس درگاه مقصد.
- **تأیید خودکار (Auto Verification):** تشخیص هوشمند درگاه پرداختی از پارامترهای درخواست callback و دیتابیس.
- **ثبت تراکنش‌ها در دیتابیس:** همراه با مایگریشن و مدل Eloquent `Transaction` و اسکوپ‌های کاربردی (`successful()`, `failed()`, `pending()`).
- **رویدادهای لاراول (Events):** ارسال رویدادهای `TransactionCreated`, `TransactionSuccessful`, `TransactionFailed`.
- **سیستم الفای تست (Faking Engine):** تست آسان با `Paypey::fake()`.

---

## پیش‌نیازها

- PHP `>= 8.2`
- Laravel `>= 10.0` یا `>= 11.0`

---

## نصب و راه‌اندازی

۱. نصب پکیج از طریق کامپوزر:

```bash
composer require gcorpllc/paypey
```

۲. انتشار فایل پیکربندی و مایگریشن‌ها:

```bash
php artisan vendor:publish --provider="Gcorpllc\Paypey\Providers\PaypeyServiceProvider"
```

۳. اجرای مایگریشن‌ها جهت ساخت جدول `paypey_transactions`:

```bash
php artisan migrate
```

---

## پیکربندی (Config)

در فایل منتشر شده `config/paypey.php` می‌توانید درگاه پیش‌فرض، واحد پول و اطلاعات اتصال را تنظیم کنید:

```php
return [
    'default' => env('PAYPEY_DEFAULT_GATEWAY', 'zarinpal'),
    'currency' => env('PAYPEY_CURRENCY', 'toman'), // 'toman' یا 'rial'
    'database_logging' => env('PAYPEY_DB_LOGGING', true),
    'sandbox' => env('PAYPEY_SANDBOX', true),

    'gateways' => [
        'zarinpal' => [
            'merchant_id' => env('ZARINPAL_MERCHANT_ID', ''),
            'sandbox' => env('ZARINPAL_SANDBOX', true),
            'currency' => 'toman',
            'mode' => 'normal', // 'normal' یا 'zaringate'
        ],
        'mellat' => [
            'terminal_id' => env('MELLAT_TERMINAL_ID', ''),
            'username' => env('MELLAT_USERNAME', ''),
            'password' => env('MELLAT_PASSWORD', ''),
            'currency' => 'rial',
        ],
        // سایر درگاه‌ها...
    ],
];
```

---

## نحوه استفاده

### ۱. ایجاد درخواست پرداخت

```php
use Gcorpllc\Paypey\Facades\Paypey;

// استفاده از درگاه پیش‌فرض یا مشخص کردن درگاه با via() یا driver()
$response = Paypey::via('zarinpal')
    ->amount(10000) // مبلغ به تومان (بر اساس config)
    ->callbackUrl(route('payment.callback'))
    ->description('سفارش شماره ۱۰۲۴')
    ->with(['order_id' => 1024, 'user_id' => 42])
    ->request(); // متد purchase() نیز معادل است

if ($response->isSuccessful()) {
    // هدایت کاربر به صفحه پرداخت بانک
    return $response->redirect();
}

return back()->with('error', $response->getErrorMessage());
```

### ۲. تأیید پرداخت (Verification)

در متد کنترلر بازگشت از بانک (Callback):

```php
use Gcorpllc\Paypey\Facades\Paypey;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;

try {
    // تشخیص خودکار درگاه و استخراج پارامترها از درخواست HTTP
    $receipt = Paypey::verify();

    $transactionId = $receipt->getTransactionId();
    $refId = $receipt->getRefId();
    $cardNumber = $receipt->getCardNumber();

    return view('payment.success', compact('receipt'));
} catch (VerificationFailedException $e) {
    return view('payment.failed', ['message' => $e->getMessage()]);
}
```

---

## تبدیل خودکار تومان و ریال

اگر `paypey.currency` روی `'toman'` تنظیم شده باشد، پکیج Paypey به‌صورت خودکار قبل از ارسال درخواست به درگاه‌هایی که ریال دریافت می‌کنند (مانند ملت، سامان، پارسیان، آیدی‌پی و نکست‌پی)، مبلغ را در ۱۰ ضرب می‌کند.

---

## تست و Fake کردن درگاه‌ها

برای اجرای یونیت تست‌ها بدون ارسال درخواست واقعی به شبکه:

```php
use Gcorpllc\Paypey\Facades\Paypey;

public function test_checkout_flow()
{
    // شبیه‌سازی موفقیت‌آمیز تمام درگاه‌ها
    Paypey::fake();

    $response = Paypey::via('zarinpal')->amount(5000)->request();
    $this->assertTrue($response->isSuccessful());

    $receipt = Paypey::verify(['Authority' => $response->getAuthority()]);
    $this->assertTrue($receipt->isSuccessful());
}

public function test_failed_payment_scenario()
{
    Paypey::fake([
        'zarinpal' => Paypey::fakeFailed('موجودی حساب کافی نیست'),
    ]);

    $this->expectException(\Gcorpllc\Paypey\Exceptions\VerificationFailedException::class);

    Paypey::via('zarinpal')->verify(['Authority' => 'INVALID_AUTH']);
}
```

---

## تعریف درگاه اختصاصی

می‌توانید درگاه سفارشی خود را با استفاده از متد `Paypey::extend()` اضافه کنید:

```php
use Gcorpllc\Paypey\Facades\Paypey;

Paypey::extend('my_custom_gateway', function ($app) {
    return new MyCustomDriver();
});
```

---

## لایسنس

این پکیج نرم‌افزار آزاد است و تحت [لایسنس MIT](LICENSE) منتشر شده است.
