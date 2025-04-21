
# Paypey - Laravel Payment Gateway Package

## English

### Introduction
**Paypey** is a Laravel package designed to simplify integration with various Iranian payment gateways. It offers a clean and flexible API to handle payment requests, verifications, and callbacks seamlessly.

### Features
- Supports multiple Iranian payment gateways (e.g., Zarinpal, Mellat, Saman).
- Simple and intuitive API for initiating and verifying transactions.
- Configurable gateway selection.
- Robust error handling.
- Compatible with Laravel 8.x, 9.x, and 10.x.

### Requirements
- PHP >= 8.0
- Laravel >= 8.0
- Composer

### Installation
1. Install via Composer:
   ```bash
     composer require gcorpllc/paypey
    ```
2. Publish the configuration file:
   ```bash
   php artisan vendor:publish --provider="gcorpllc\Paypey\PaypeyServiceProvider"
   ```
This creates a `config/paypey.php` file.


### Config
Edit the `config/paypey.php` file to set the ports. Example:
```php
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
```

Add to your `.env` file:

```env
PAYPEY_DEFAULT_GATEWAY="zarinpal"
CALLBACK_URL="your-merchant-id"
PAYPEY_SANDBOX=true
```

### Usage
#### Initiating a Payment
```php
use Gcorpllc\Paypey\Facades\Paypey;

$payment = Paypey::driver('zarinpal')
    ->amount(10000) // Amount in IRR
    ->callbackUrl(route('payment.callback'))
    ->purchase();

if ($payment->isSuccessful()) {
    return redirect($payment->getPaymentUrl());
} else {
    return response()->json(['error' => $payment->getErrorMessage()]);
}
```

#### Verifying a Payment
```php
use Gcorpllc\Paypey\Facades\Paypey;

$result = Paypey::driver('zarinpal')->verify();

if ($result->isSuccessful()) {
    $transactionId = $result->getTransactionId();
    return response()->json(['message' => 'Payment verified!', 'transaction_id' => $transactionId]);
} else {
    return response()->json(['error' => $result->getErrorMessage()]);
}
```

### Supported Gateways
- **Zarinpal**: Sandbox and production modes.
- **Mellat**: Secure transactions with terminal ID.
- More gateways to be added soon.

### Error Handling
Access error messages via:
```php
$errorMessage = $payment->getErrorMessage();
```

### Testing
Clone the repository and run:
```bash
composer install
./vendor/bin/phpunit
```

### License
Licensed under the [MIT License](LICENSE.md).

### Support
For issues, open a ticket on [GitHub](https://github.com/your-vendor/paypey/issues) or email support@your-vendor.com.

---

## فارسی

### معرفی
**Paypey** یک پکیج لاراول است که برای ساده‌سازی اتصال به درگاه‌های پرداخت ایرانی طراحی شده است. این پکیج یک API تمیز و انعطاف‌پذیر برای مدیریت درخواست‌های پرداخت، تأیید تراکنش‌ها و callbackها ارائه می‌دهد.

### ویژگی‌ها
- پشتیبانی از درگاه‌های پرداخت ایرانی (مانند زرین‌پال، ملت، سامان).
- API ساده و کاربرپسند برای شروع و تأیید تراکنش‌ها.
- امکان انتخاب درگاه پیش‌فرض.
- مدیریت خطاها به‌صورت جامع.
- سازگار با لاراول نسخه‌های 8.x، 9.x و 10.x.

### پیش‌نیازها
- PHP نسخه >= 8.0
- لاراول نسخه >= 8.0
- Composer

### نصب
1. پکیج را از طریق Composer نصب کنید:
   ```bash
      composer require gcorpllc/paypey
   ```
2. فایل پیکربندی را منتشر کنید:
   ```bash
   php artisan vendor:publish --provider="Gcorpllc\Paypey\PaypeyServiceProvider"
   ```
   این دستور فایل `config/paypey.php` را ایجاد می‌کند.


### پیکربندی
فایل `config/paypey.php` را برای تنظیم درگاه‌ها ویرایش کنید. نمونه:

```php
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
```

در فایل `.env` موارد زیر را اضافه کنید:

```env
PAYPEY_DEFAULT_GATEWAY="zarinpal"
CALLBACK_URL="your-merchant-id"
PAYPEY_SANDBOX=true
```

### استفاده
#### شروع پرداخت
```php
use Gcorpllc\Paypey\Facades\Paypey;

$payment = Paypey::driver('zarinpal')
    ->amount(10000) // Amount in IRR
    ->callbackUrl(route('payment.callback'))
    ->purchase();

if ($payment->isSuccessful()) {
    return redirect($payment->getPaymentUrl());
} else {
    return response()->json(['error' => $payment->getErrorMessage()]);
}
```

#### تأیید پرداخت
```php
use YourVendor\Paypey\Facades\Paypey;

$result = Paypey::driver('zarinpal')->verify();

if ($result->isSuccessful()) {
    $transactionId = $result->getTransactionId();
    return response()->json(['message' => 'پرداخت با موفقیت تأیید شد!', 'transaction_id' => $transactionId]);
} else {
    return response()->json(['error' => $result->getErrorMessage()]);
}
```


### تست
برای اجرای تست‌ها، مخزن را کلون کرده و دستورات زیر را اجرا کنید:
```bash
composer install
./vendor/bin/phpunit
```

### لایسنس
این پکیج تحت [لایسنس MIT](LICENSE.md) منتشر شده است.

### پشتیبانی
برای مشکلات یا سؤالات، یک تیکت در [GitHub](https://github.com/your-vendor/paypey/issues) باز کنید یا به support@your-vendor.com ایمیل بزنید.



---

### توضیحات
- **ساختار**: README به دو بخش انگلیسی و فارسی تقسیم شده است تا برای کاربران بین‌المللی و ایرانی قابل استفاده باشد.
- **جایگزینی**: نام `your-vendor` باید با نام واقعی وندور شما جایگزین شود. همچنین، لینک‌های GitHub و اطلاعات تماس باید به‌روزرسانی شوند.
- **شخصی‌سازی**: می‌توانید درگاه‌های خاص یا قابلیت‌های اضافی پکیج خود را در بخش‌های مربوطه اضافه کنید.
- **استاندارد**: این فایل از استانداردهای رایج README (مانند badges، ساختار واضح و مثال‌های کد) پیروی می‌کند.

اگر نیاز به تغییرات خاصی (مانند افزودن بخش‌های جدید یا تغییر لحن) دارید، لطفاً اطلاع دهید!
