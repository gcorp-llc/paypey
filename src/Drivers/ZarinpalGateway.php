<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Enums\PaypeyStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class ZarinpalGateway extends AbstractGateway
{
    protected string $requestUrl;
    protected string $verifyUrl;
    protected string $gatewayUrl;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $isSandbox = $config['sandbox'] ?? config('paypey.sandbox', true);

        if ($isSandbox) {
            $this->requestUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
            $this->verifyUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
            $this->gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';
        } else {
            $this->requestUrl = 'https://api.zarinpal.com/pg/v4/payment/request.json';
            $this->verifyUrl = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
            $this->gatewayUrl = 'https://www.zarinpal.com/pg/StartPay/';
        }
    }

    public function purchase(): RedirectResponse
    {
        $amount = $this->invoice->getAmount();
        $currency = $this->invoice->getCurrency();

        // Zarinpal works with Toman by default in v4, but can be Rials?
        // Actually Zarinpal v4 amount is in Tomans or Rials depending on merchant settings?
        // No, usually it's Tomans in the API.
        // Let's assume input is IRR and we convert to Toman if needed, or vice-versa.
        // For Zarinpal v4, amount is in Tomans (T).
        if ($currency === 'IRR') {
            $amount = (int)($amount / 10);
        }

        $data = [
            'merchant_id' => $this->config['merchantId'] ?? '',
            'amount' => $amount,
            'callback_url' => $this->invoice->getCallbackUrl(),
            'description' => $this->invoice->getDescription() ?? 'Payment',
            'metadata' => array_merge($this->invoice->getMetadata(), [
                'mobile' => $this->invoice->getMobile(),
                'email' => $this->invoice->getEmail(),
            ]),
        ];

        $response = Http::post($this->requestUrl, $data);
        $result = $response->json();

        if ($response->successful() && isset($result['data']['authority'])) {
            $authority = $result['data']['authority'];
            return Redirect::to($this->gatewayUrl . $authority);
        }

        throw new \Exception($result['errors']['message'] ?? 'Zarinpal payment request failed');
    }

    public function verify(Request $request): Receipt
    {
        $authority = $request->get('Authority');
        $status = $request->get('Status');

        if ($status !== 'OK') {
            return new Receipt(false);
        }

        $amount = $this->invoice->getAmount();
        $currency = $this->invoice->getCurrency();
        if ($currency === 'IRR') {
            $amount = (int)($amount / 10);
        }

        $data = [
            'merchant_id' => $this->config['merchantId'] ?? '',
            'amount' => $amount,
            'authority' => $authority,
        ];

        $response = Http::post($this->verifyUrl, $data);
        $result = $response->json();

        if ($response->successful() && isset($result['data']['ref_id'])) {
            $receipt = new Receipt(true, $authority);
            $receipt->referenceId($result['data']['ref_id']);
            $receipt->cardPan($result['data']['card_pan'] ?? '');
            $receipt->details($result['data']);
            return $receipt;
        }

        $receipt = new Receipt(false);
        $receipt->message($result['errors']['message'] ?? 'Verification failed');
        return $receipt;
    }

    public function refund(string $transactionId, ?int $amount = null): array
    {
        // Zarinpal refund API is usually restricted or needs special access.
        // Implementation depends on their specific refund endpoint.
        return ['success' => false, 'message' => 'Refund not implemented for Zarinpal yet'];
    }
}
