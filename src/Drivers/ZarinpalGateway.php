<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Enums\PaymentError;
use Gcorpllc\Paypey\Exceptions\PaymentRequestException;
use Gcorpllc\Paypey\Exceptions\VerificationException;
use Gcorpllc\Paypey\Payment\Arr;
use Gcorpllc\Paypey\Payment\GatewayApiException;
use Gcorpllc\Paypey\Payment\PaymentException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

// اضافه کردن use
// برای لاگ کردن خطاها
// برای redirect
// مثال: یک Exception سفارشی
// مثال: یک Exception سفارشی

class ZarinpalGateway implements PaymentGateway
{
    protected ?int $amount = null; // مقداردهی اولیه به null
    protected ?string $callbackUrl = null;
    protected ?string $description = ''; // مقداردهی اولیه به رشته خالی
    protected ?string $email = null;
    protected ?string $mobile = null;

    // پراپرتی‌های مربوط به تنظیمات درگاه
    protected string $merchantId;
    protected bool $isSandbox;
    protected string $requestUrl;
    protected string $verifyUrl;
    protected string $gatewayUrl;

    // ثابت برای کدهای وضعیت زرین‌پال
    const ZARINPAL_STATUS_OK = 100;
    const ZARINPAL_STATUS_ALREADY_VERIFIED = 101;


    public function __construct()
    {
        // خواندن تنظیمات از کانفیگ در سازنده
        $this->merchantId = config('paypey.gateways.zarinpal.merchantId');
        $this->isSandbox = config('paypey.sandbox'); // پیش‌فرض false اگر تعریف نشده باشد
        // مقداردهی اولیه callbackUrl از کانفیگ در صورت عدم تنظیم دستی
        $this->callbackUrl = config('paypey.callbackUrl');

        // تنظیم URL ها بر اساس حالت sandbox
        if ($this->isSandbox) {
            $this->requestUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
            $this->verifyUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
            $this->gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';
        } else {
            $this->requestUrl = 'https://api.zarinpal.com/pg/v4/payment/request.json';
            $this->verifyUrl = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
            $this->gatewayUrl = 'https://www.zarinpal.com/pg/StartPay/';
        }


    }

    public function amount(int $amount): static { $this->amount = $amount; return $this; }
    public function callbackUrl(string $callbackUrl): static { $this->callbackUrl = $callbackUrl; return $this; }
    public function description(string $description): static { $this->description = $description; return $this; }
    public function email(?string $email): static { $this->email = $email; return $this; }
    public function mobile(?string $mobile): static { $this->mobile = $mobile; return $this; }

    /**
     * Initiate the payment purchase process.
     * Sends request to Zarinpal and returns a redirect response to the gateway.
     *
     * @return RedirectResponse
     * @throws PaymentRequestException|\Exception If the request fails.
     */
    public function purchase(): RedirectResponse
    {
        // اعتبارسنجی مقادیر ضروری
        if (empty($this->amount) || $this->amount <= 0) {
            throw new \InvalidArgumentException('Amount is required and must be positive.');
        }
        if (empty($this->callbackUrl)) {
            throw new \InvalidArgumentException('Callback URL is required.');
        }
        if (empty($this->merchantId)) {
            throw new \InvalidArgumentException(PaymentError::ZARINPAL_INVALID_MERCHANT_ID->trans());
        }


        // ساخت داده‌های پرداخت با استفاده از مقادیر کلاس
        $paymentData = [
            'merchant_id' => $this->merchantId,
            'amount' => $this->amount, // Amount in Rials
            'callback_url' => $this->callbackUrl,
            'description' => $this->description ?: 'تراکنش زرین‌پال', // توضیحات پیش‌فرض اگر خالی بود
        ];

        // اضافه کردن متادیتا در صورت وجود
        $metadata = [];
        if (!empty($this->mobile)) {
            $metadata['mobile'] = $this->mobile;
        }
        if (!empty($this->email)) {
            $metadata['email'] = $this->email;
        }
        if (!empty($metadata)) {
            $paymentData['metadata'] = $metadata;
        }

        try {
            // ارسال درخواست به زرین‌پال
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->requestUrl, $paymentData);

            $result = $response->json();

            // بررسی خطا در پاسخ یا عدم موفقیت درخواست HTTP
            if ($response->failed() || !isset($result['data']['authority']) || (isset($result['errors']) && $result['errors'] != [])) {
                $errorMessage = $this->extractErrorMessage($result, $response->status());
                Log::error('Zarinpal Payment Request Failed:', [
                    'status' => $response->status(),
                    'response' => $result,
                    'request_data' => $paymentData
                ]);
                // پرتاب یک Exception سفارشی
                throw new PaymentRequestException(PaymentError::ZARINPAL_INVALID_MERCHANT_ID->trans());
            }

            // اگر موفق بود
            $authority = $result['data']['authority'];

            // <<<<<<<< نقطه مهم برای جایگزینی Session >>>>>>>>
            // در اینجا باید authority و amount (و شاید شناسه سفارش/کاربر) را
            // در دیتابیس ذخیره کنید.
            // مثلاً:
            // PaymentTransaction::create([
            //    'authority' => $authority,
            //    'amount' => $this->amount,
            //    'status' => 'pending',
            //    // ... other fields like user_id, order_id
            // ]);
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

            // ذخیره موقت در session (روش فعلی - توصیه می‌شود تغییر کند)
            session(['payment_authority' => $authority, 'payment_amount' => $this->amount]);

            // ایجاد و بازگرداندن پاسخ Redirect
            return Redirect::to($this->gatewayUrl . $authority);


        } catch (RequestException $e) {
            // خطای اتصال
            Log::error('Zarinpal Connection Error on Purchase: ' . $e->getMessage());
            throw new PaymentRequestException('خطا در اتصال به درگاه پرداخت زرین‌پال.', $e->getCode(), $e);
        } catch (\Exception $e) {
            // سایر خطاها (شامل PaymentRequestException که خودمان throw کردیم)
            // اگر از نوع PaymentRequestException نبود لاگ کن
            if (!($e instanceof PaymentRequestException)) {
                Log::error('Zarinpal General Error on Purchase: ' . $e->getMessage());
            }
            // Exception را مجدد پرتاب کن تا کنترلر بالادستی مدیریت کند
            throw $e;
        }
    }

    /**
     * Verify the payment after callback from Zarinpal.
     *
     * @param Request $request The callback request object.
     * @return array An array containing verification result ['success' => bool, 'refId' => ?, 'cardPan' => ?, 'message' => ?]
     * @throws VerificationException|\Exception If verification fails critically or invalid callback data.
     */
    public function verify(Request $request): array
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status'); // e.g., "OK" or "NOK"

        // <<<<<<<< نقطه مهم برای جایگزینی Session >>>>>>>>
        // در اینجا باید اطلاعات تراکنش را بر اساس authority از دیتابیس بخوانید
        // مثلاً:
        // $transaction = PaymentTransaction::where('authority', $authority)->where('status', 'pending')->first();
        // if (!$transaction) {
        //    throw new VerificationException('تراکنش یافت نشد یا قبلاً پردازش شده است.');
        // }
        // $amount = $transaction->amount;
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

        // خواندن از session (روش فعلی - توصیه می‌شود تغییر کند)
        $storedAuthority = session('payment_authority');
        $amount = session('payment_amount');

        // پاک کردن session در هر صورت (موفق یا ناموفق) در انتهای فرآیند verify
        session()->forget(['payment_authority', 'payment_amount']);


        // بررسی اولیه و ضروری
        if (!$authority || !$status) {
            throw new VerificationException('اطلاعات بازگشتی از درگاه ناقص است.');
        }
        // مقایسه authority (مهم برای امنیت)
        if ($authority !== $storedAuthority) {
            Log::warning('Zarinpal Verification Authority Mismatch:', [
                'request_authority' => $authority,
                'session_authority' => $storedAuthority
            ]);
            throw new VerificationException('اطلاعات پرداخت نامعتبر است (عدم تطابق Authority).');
        }
        if (!$amount){
            // اگر amount در سشن نبود (یا از دیتابیس خوانده نشد)
            throw new VerificationException('مبلغ تراکنش برای تایید یافت نشد.');
        }


        // اگر کاربر پرداخت را لغو کرده بود (Status != OK)
        if ($status !== 'OK') {
            // <<<< به‌روزرسانی وضعیت در دیتابیس به 'canceled' یا 'failed' >>>>
            // $transaction->update(['status' => 'canceled']);
            return [
                'success' => false,
                'refId' => null,
                'cardPan' => null,
                'message' => 'پرداخت توسط کاربر لغو شد یا ناموفق بود.'
            ];
        }

        // اگر Status = OK بود، درخواست Verify را ارسال کن
        $data = [
            'merchant_id' => $this->merchantId,
            'amount' => $amount, // مبلغ از دیتابیس یا سشن
            'authority' => $authority,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->verifyUrl, $data);

            $result = $response->json();

            // بررسی خطا در پاسخ Verify
            if ($response->failed() || (isset($result['errors']) && $result['errors'] != [])) {
                $errorCode = $result['errors']['code'] ?? null;
                $errorMessage = $this->extractErrorMessage($result, $response->status());

                // اگر کد خطا 101 بود (قبلا تایید شده)
                if ($errorCode == self::ZARINPAL_STATUS_ALREADY_VERIFIED) {
                    Log::info('Zarinpal Transaction Already Verified:', ['response' => $result, 'authority' => $authority]);
                    // <<<< اینجا باید بررسی کنید که آیا در دیتابیس شما وضعیت 'completed' است یا خیر >>>>
                    // اگر نبود، وضعیت را completed کنید و RefID را ذخیره کنید (اگر در پاسخ آمده بود)
                    // $transaction->update(['status' => 'completed', 'ref_id' => $result['data']['ref_id'] ?? null]);
                    return [
                        'success' => true, // در نظر گرفتن به عنوان موفقیت
                        'refId' => $result['data']['ref_id'] ?? null,
                        'cardPan' => $result['data']['card_pan'] ?? null,
                        'message' => 'تراکنش قبلاً با موفقیت تایید شده بود.'
                    ];
                }

                // برای سایر خطاها
                Log::error('Zarinpal Verification Failed:', [
                    'status' => $response->status(),
                    'response' => $result,
                    'request_data' => $data
                ]);
                // <<<< به‌روزرسانی وضعیت در دیتابیس به 'failed' >>>>
                // $transaction->update(['status' => 'failed', 'error_message' => $errorMessage]);
                throw new VerificationException('خطا در تایید پرداخت زرین‌پال: ' . $errorMessage);
            }


            // اگر درخواست verify موفق بود (کد 100)
            if (isset($result['data']['code']) && $result['data']['code'] == self::ZARINPAL_STATUS_OK) {
                $refId = $result['data']['ref_id'];
                $cardPan = $result['data']['card_pan'] ?? null;

                // <<<< به‌روزرسانی وضعیت در دیتابیس به 'completed' و ذخیره refId و cardPan >>>>
                // $transaction->update([
                //    'status' => 'completed',
                //    'ref_id' => $refId,
                //    'card_pan' => $cardPan
                // ]);

                return [
                    'success' => true,
                    'refId' => $refId,
                    'cardPan' => $cardPan,
                    'message' => 'پرداخت با موفقیت تایید شد.'
                ];
            }

            // اگر کد وضعیت دیگری برگشت (غیر از 100 و 101 و خطا در errors)
            $errorMessage = 'وضعیت نامشخص از زرین‌پال دریافت شد.';
            if(isset($result['data']['message'])) {
                $errorMessage .= ' پیام: ' . $result['data']['message'];
            }
            Log::error('Zarinpal Verification Unknown Status:', ['response' => $result, 'request_data' => $data]);
            // <<<< به‌روزرسانی وضعیت در دیتابیس به 'failed' >>>>
            // $transaction->update(['status' => 'failed', 'error_message' => $errorMessage]);
            throw new VerificationException($errorMessage);


        } catch (RequestException $e) {
            Log::error('Zarinpal Connection Error on Verify: ' . $e->getMessage());
            // <<<< به‌روزرسانی وضعیت در دیتابیس به 'failed' (اگر تراکنش قابل شناسایی بود) >>>>
            throw new VerificationException('خطا در ارتباط برای تایید پرداخت زرین‌پال.', $e->getCode(), $e);
        } catch (\Exception $e) {
            // سایر خطاها (شامل VerificationException که خودمان throw کردیم)
            if (!($e instanceof VerificationException)) {
                Log::error('Zarinpal General Error on Verify: ' . $e->getMessage());
            }
            // <<<< به‌روزرسانی وضعیت در دیتابیس به 'failed' (اگر تراکنش قابل شناسایی بود) >>>>
            throw $e;
        }
    }


    /**
     * Helper function to extract error message from Zarinpal response.
     *
     * @param ?array $result The JSON decoded response body.
     * @param int $httpStatus The HTTP status code.
     * @return string The extracted error message.
     */
    private function extractErrorMessage(?array $result, int $httpStatus): string
    {
        if (isset($result['errors']['message'])) {
            return $result['errors']['message'] . (isset($result['errors']['code']) ? ' (Code: ' . $result['errors']['code'] . ')' : '');
        } elseif (isset($result['errors']['code'])) {
            // TODO: Map known error codes to user-friendly messages if needed
            return 'خطا با کد: ' . $result['errors']['code'];
        } elseif ($httpStatus >= 400) {
            return 'خطای سرور درگاه پرداخت (HTTP Status: ' . $httpStatus . ')';
        }
        return 'خطای نامشخص در ارتباط با درگاه پرداخت.';
    }


    /**
     * @throws PaymentException
     */
    public function reverse(string $authority, ?int $amount = null): array // برمی‌گرداند آرایه استاندارد
    {
        // *** نکته مهم: ***
        // در این پیاده‌سازی، پکیج خودش تراکنش را از دیتابیس *پیدا نمی‌کند*.
        // مسئولیت پیدا کردن تراکنش بر اساس RefId یا Authority در دیتابیس برنامه استفاده‌کننده و پاس دادن Authority به این متد بر عهده برنامه استفاده‌کننده است.
        if (empty($authority)) {
            throw new \InvalidArgumentException("Authority is required for Zarinpal refund.");
        }
        // مقدار مبلغ برای ریفاند (اگر null باشد، زرین‌پال کل مبلغ را ریفاند می‌کند)
        if ($amount !== null && $amount <= 0) {
            throw new \InvalidArgumentException(PaymentError::INVALID_AMOUNT->trans() . ' مبلغ ریفاند.');
        }

        $refundData = [
            'merchant_id' => $this->config['merchantId'],
            'authority' => $authority, // API ریفاند زرین‌پال نیاز به Authority تراکنش اصلی دارد
        ];

        if ($amount !== null) {
            $refundData['amount'] = $amount; // ارسال مبلغ برای ریفاند جزئی (اگر درگاه پشتیبانی کند)
        }


        try {
            $response = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->refundUrl, $refundData); // یا Http Client تزریق شده

            $result = $response->json();

            $errorCode = Arr::get($result, 'errors.code');
            $errorMessageRaw = Arr::get($result, 'errors.message');
            $resultCode = Arr::get($result, 'data.code'); // کد موفقیت یا خطای درگاه در پاسخ ریفاند

            // بررسی پاسخ API ریفاند
            if ($response->failed() || $errorCode !== null) {
                $errorCase = PaymentError::fromValue($errorCode);
                $errorMessage = $errorCase ? $errorCase->trans() : ($errorMessageRaw ?? 'خطای نامشخص در بازگشت وجه.');

                Log::error('Zarinpal Refund Failed:', [
                    'status' => $response->status(),
                    'response' => $result,
                    'request_data' => Arr::except($refundData, ['merchant_id']),
                    'authority' => $authority, // Authority ارسال شده برای ریفاند
                    'error_code' => $errorCode,
                    'error_enum' => $errorCase?->name,
                ]);

                // پرتاب Exception دانه‌بندی‌شده بر روی خطای API ریفاند
                throw new GatewayApiException($errorMessage, $errorCode ?? $response->status());

            } elseif ($resultCode === PaymentError::ZARINPAL_SUCCESS->getValue()) { // فرض می‌کنیم کد موفقیت ریفاند هم 100 است
                $successCase = PaymentError::ZARINPAL_SUCCESS; // یا Enum Case مخصوص موفقیت ریفاند
                // Ref ID ریفاند (اگر متفاوت باشد و در پاسخ باشد)
                $refundId = Arr::get($result, 'data.ref_id') ?? Arr::get($result, 'data.authority'); // ممکن است Authority در پاسخ ریفاند باشد

                Log::info('Zarinpal Refund Successful', [
                    'authority' => $authority, // Authority تراکنش اصلی
                    'refund_id' => $refundId, // شناسه ریفاند در درگاه
                    'amount_requested' => $amount ?? 'Full',
                    'response' => $result
                ]);

                // برمی‌گرداند آرایه استاندارد موفقیت ریفاند
                return [
                    'success' => true,
                    'message' => $successCase->trans(),
                    'refundId' => $refundId,
                    'errorCode' => $successCase->getValue(),
                    'details' => $result // پاسخ کامل درگاه
                ];

            } else {
                // وضعیت ناموفق یا نامشخص دیگر برای ریفاند (کدهای دیگر غیر از موفقیت)
                $errorCase = PaymentError::fromValue($resultCode) ?? PaymentError::GENERAL_ERROR;
                $errorMessage = $errorCase->trans(); // از ترجمه کد خطا استفاده کن
                $errorMessageRawFromGateway = Arr::get($result, 'data.message'); // پیام درگاه در صورت وجود
                if($errorMessageRawFromGateway && $errorCase->getValue() === PaymentError::GENERAL_ERROR->getValue()){
                    $errorMessage = $errorMessageRawFromGateway;
                } else if ($errorMessageRawFromGateway) {
                    $errorMessage .= ' (' . $errorMessageRawFromGateway . ')';
                }


                Log::error('Zarinpal Refund Unknown Status:', [
                    'response' => $result,
                    'authority' => $authority,
                    'code' => $resultCode,
                    'enum' => $errorCase->name,
                ]);

                // برمی‌گرداند آرایه استاندارد ناموفق ریفاند
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'refundId' => Arr::get($result, 'data.ref_id'), // ممکن است در خطا هم باشد
                    'errorCode' => $resultCode, // کد خطای درگاه
                    'details' => $result
                ];
            }

        } catch (\Exception $e) {
            // خطاهای اتصال یا سایر خطاهای پیش‌بینی نشده حین Refund API Call
            if (!($e instanceof PaymentException)) {
                Log::error('Zarinpal General Error on Refund:', ['message' => $e->getMessage(), 'authority' => $authority, 'trace' => $e->getTraceAsString()]);
                throw new PaymentException('خطای نامشخص در فرآیند بازگشت وجه.', $e->getCode(), $e);
            }
            throw $e; // مجدد Exception دانه‌بندی‌شده را پرتاب کنید
        }
    }
}
