# Paypey - Laravel Payment Gateway Aggregator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gcorpllc/paypey.svg?style=flat-square)](https://packagist.org/packages/gcorpllc/paypey)
[![Total Downloads](https://img.shields.io/packagist/dt/gcorpllc/paypey.svg?style=flat-square)](https://packagist.org/packages/gcorpllc/paypey)
[![License](https://img.shields.io/packagist/l/gcorpllc/paypey.svg?style=flat-square)](LICENSE)

**Paypey** is a production-ready, driver-based payment gateway aggregator for Laravel applications. It provides a unified, fluent API for integrating major Iranian payment gateways (Zarinpal, Mellat, Saman, Parsian, IdPay, NextPay) as well as sandbox and international gateways.

---

## Readme Languages
- [English Documentation](README.md)
- [راهنمای فارسی (Persian Documentation)](README.fa.md)

---

## Features

- **Fluent API:** Expressive methods for payment creation and verification (`Paypey::via('zarinpal')->amount(10000)->request()`).
- **Driver Pattern (Strategy):** Easily switch between payment drivers or extend with custom drivers via `Paypey::extend()`.
- **Supported Gateways:**
  - **Sandbox / Fake** (Zero network calls required for local testing)
  - **Zarinpal** (Normal & ZarinGate)
  - **Mellat** (BAM)
  - **Saman** (SEP)
  - **Parsian** (PEC)
  - **IdPay**
  - **NextPay**
  - **Stripe & PayPal** (Secondary international drivers)
- **Automatic Currency Conversion:** Seamless handling between **Toman** and **Rial**.
- **Auto-Verification:** Automatic gateway detection on `Paypey::verify()` from HTTP request parameters or transaction history.
- **Transaction History:** Built-in migration and `Transaction` Eloquent model with helper scopes (`successful()`, `failed()`, `pending()`).
- **Laravel Events:** Dispatches `TransactionCreated`, `TransactionSuccessful`, and `TransactionFailed`.
- **Testing Engine:** Built-in mocking via `Paypey::fake()`.

---

## Requirements

- PHP `>= 8.2`
- Laravel `>= 10.0` or `>= 11.0`

---

## Installation

1. Install the package via Composer:

```bash
composer require gcorpllc/paypey
```

2. Publish the configuration file and database migrations:

```bash
php artisan vendor:publish --provider="Gcorpllc\Paypey\Providers\PaypeyServiceProvider"
```

3. Run migrations to create `paypey_transactions` table:

```bash
php artisan migrate
```

---

## Configuration

The published `config/paypey.php` allows you to set default driver, global currency unit, and gateway credentials:

```php
return [
    'default' => env('PAYPEY_DEFAULT_GATEWAY', 'zarinpal'),
    'currency' => env('PAYPEY_CURRENCY', 'toman'), // 'toman' or 'rial'
    'database_logging' => env('PAYPEY_DB_LOGGING', true),
    'sandbox' => env('PAYPEY_SANDBOX', true),

    'gateways' => [
        'zarinpal' => [
            'merchant_id' => env('ZARINPAL_MERCHANT_ID', ''),
            'sandbox' => env('ZARINPAL_SANDBOX', true),
            'currency' => 'toman',
            'mode' => 'normal', // 'normal' or 'zaringate'
        ],
        'mellat' => [
            'terminal_id' => env('MELLAT_TERMINAL_ID', ''),
            'username' => env('MELLAT_USERNAME', ''),
            'password' => env('MELLAT_PASSWORD', ''),
            'currency' => 'rial',
        ],
        // ... additional gateway configs
    ],
];
```

---

## Usage Examples

### 1. Requesting Payment

```php
use Gcorpllc\Paypey\Facades\Paypey;

// Using default gateway or specific driver via via() / driver()
$response = Paypey::via('zarinpal')
    ->amount(10000) // Amount in global currency (Toman by default)
    ->callbackUrl(route('payment.callback'))
    ->description('Order #1024')
    ->with(['order_id' => 1024, 'user_id' => 42])
    ->request(); // purchase() is an identical alias

if ($response->isSuccessful()) {
    // Redirect user to gateway payment page
    return $response->redirect();
}

return back()->with('error', $response->getErrorMessage());
```

### 2. Verifying Payment

In your callback controller action:

```php
use Gcorpllc\Paypey\Facades\Paypey;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;

try {
    // Auto-detects driver from request query/POST params and database
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

## Automatic Currency Conversion

When `paypey.currency` is set to `'toman'`, Paypey automatically multiplies amounts by 10 before constructing request payloads for drivers expecting Rials (e.g., Mellat, Saman, Parsian, IdPay, NextPay). Receipt object values reflect original transaction amounts cleanly.

---

## Mocking & Testing

Use `Paypey::fake()` in your unit/feature tests without contacting live HTTP endpoints:

```php
use Gcorpllc\Paypey\Facades\Paypey;

public function test_checkout_flow()
{
    // Default success fake for all drivers
    Paypey::fake();

    $response = Paypey::via('zarinpal')->amount(5000)->request();
    $this->assertTrue($response->isSuccessful());

    $receipt = Paypey::verify(['Authority' => $response->getAuthority()]);
    $this->assertTrue($receipt->isSuccessful());
}

public function test_failed_payment_scenario()
{
    Paypey::fake([
        'zarinpal' => Paypey::fakeFailed('Card balance insufficient'),
    ]);

    $this->expectException(\Gcorpllc\Paypey\Exceptions\VerificationFailedException::class);

    Paypey::via('zarinpal')->verify(['Authority' => 'INVALID_AUTH']);
}
```

---

## Adding Custom Drivers

Register custom payment gateways using `Paypey::extend()`:

```php
use Gcorpllc\Paypey\Facades\Paypey;
use Gcorpllc\Paypey\Drivers\AbstractDriver;

Paypey::extend('my_custom_gateway', function ($app) {
    return new MyCustomDriver();
});
```

---

## License

The Paypey package is open-sourced software licensed under the [MIT license](LICENSE).
