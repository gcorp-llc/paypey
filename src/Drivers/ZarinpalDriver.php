<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Facades\Http;

class ZarinpalDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'zarinpal';
    }

    protected function getTargetCurrency(): string
    {
        return 'toman';
    }

    protected function isSandbox(): bool
    {
        return $this->config['sandbox'] ?? config('paypey.sandbox', true);
    }

    protected function getApiUrl(string $endpoint): string
    {
        $sub = $this->isSandbox() ? 'sandbox' : 'api';
        return "https://{$sub}.zarinpal.com/pg/v4/payment/{$endpoint}.json";
    }

    protected function getRedirectUrl(string $authority): string
    {
        $sub = $this->isSandbox() ? 'sandbox' : 'www';
        $mode = ($this->config['mode'] ?? 'normal') === 'zaringate' ? '/ZarinGate' : '';
        return "https://{$sub}.zarinpal.com/pg/StartPay/{$authority}{$mode}";
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $merchantId = $this->config['merchant_id'] ?? 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
        $amount = $this->getConvertedAmount();

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => (int) $amount,
            'callback_url' => $this->callbackUrl,
            'description' => $this->description ?: 'Payment via Paypey',
            'metadata' => $this->metadata,
        ];

        try {
            $response = Http::post($this->getApiUrl('request'), $payload);
            $data = $response->json();

            if (isset($data['data']['code']) && ($data['data']['code'] === 100 || $data['data']['code'] === 101)) {
                $authority = $data['data']['authority'];

                if ($transaction->exists) {
                    $transaction->update(['transaction_id' => $authority]);
                }

                return new GatewayResponse(
                    success: true,
                    transactionId: $authority,
                    authority: $authority,
                    redirectUrl: $this->getRedirectUrl($authority),
                    rawResponse: $data
                );
            }

            $errorMessage = $data['errors']['message'] ?? 'Zarinpal payment request failed.';
            $this->logTransactionFailure(new GatewayException($errorMessage), $data, $transaction);

            return new GatewayResponse(
                success: false,
                errorMessage: $errorMessage,
                rawResponse: $data ?? []
            );
        } catch (\Throwable $e) {
            $this->logTransactionFailure($e, [], $transaction);
            throw new GatewayException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function verify(?array $params = null): ReceiptInterface
    {
        $params = $params ?? request()->all();

        $authority = $params['Authority'] ?? $params['authority'] ?? null;
        $status = $params['Status'] ?? $params['status'] ?? null;

        $transaction = $this->findTransactionRecord($authority, null);

        if ($status !== 'OK') {
            $receipt = new Receipt(
                success: false,
                transactionId: $authority,
                refId: null,
                gateway: $this->getDriverName(),
                amount: $this->amount,
                message: 'Payment was canceled or failed by user.',
                rawResponse: $params
            );

            $this->logTransactionFailure(new VerificationFailedException($receipt->getMessage()), $params, $transaction);
            throw new VerificationFailedException($receipt->getMessage());
        }

        $merchantId = $this->config['merchant_id'] ?? 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
        $amount = $transaction ? $transaction->amount : $this->amount;

        // Ensure conversion according to driver target currency
        $this->amount = $amount;
        $convertedAmount = $this->getConvertedAmount();

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => (int) $convertedAmount,
            'authority' => $authority,
        ];

        try {
            $response = Http::post($this->getApiUrl('verify'), $payload);
            $data = $response->json();

            if (isset($data['data']['code']) && ($data['data']['code'] === 100 || $data['data']['code'] === 101)) {
                $refId = $data['data']['ref_id'] ?? null;
                $cardNumber = $data['data']['card_pan'] ?? null;

                $receipt = new Receipt(
                    success: true,
                    transactionId: $authority,
                    refId: $refId,
                    gateway: $this->getDriverName(),
                    amount: $amount,
                    cardNumber: $cardNumber,
                    message: 'Transaction verified successfully.',
                    rawResponse: $data
                );

                $this->logTransactionSuccess($receipt, $transaction);

                return $receipt;
            }

            $errorMessage = $data['errors']['message'] ?? 'Zarinpal verification failed.';
            $receipt = new Receipt(
                success: false,
                transactionId: $authority,
                refId: null,
                gateway: $this->getDriverName(),
                amount: $amount,
                message: $errorMessage,
                rawResponse: $data ?? []
            );

            $this->logTransactionFailure(new VerificationFailedException($errorMessage), $data, $transaction);
            throw new VerificationFailedException($errorMessage);
        } catch (\Throwable $e) {
            if (! ($e instanceof VerificationFailedException)) {
                $this->logTransactionFailure($e, [], $transaction);
                throw new VerificationFailedException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }
}
