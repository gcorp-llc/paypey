<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Facades\Http;

class IdpayDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'idpay';
    }

    protected function getTargetCurrency(): string
    {
        return 'rial';
    }

    protected function isSandbox(): bool
    {
        return $this->config['sandbox'] ?? config('paypey.sandbox', true);
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $apiKey = $this->config['api_key'] ?? '';
        $amount = (int) $this->getConvertedAmount();
        $orderId = rand(1000000, 9999999);

        $headers = [
            'X-API-KEY' => $apiKey,
            'X-SANDBOX' => $this->isSandbox() ? '1' : '0',
        ];

        $payload = [
            'order_id' => (string) $orderId,
            'amount' => $amount,
            'callback' => $this->callbackUrl,
            'desc' => $this->description,
        ];

        try {
            $response = Http::withHeaders($headers)->post('https://api.idpay.ir/v1.1/payment', $payload);
            $data = $response->json();

            if (isset($data['id']) && isset($data['link'])) {
                $id = $data['id'];

                if ($transaction->exists) {
                    $transaction->update(['transaction_id' => $id, 'ref_id' => (string) $orderId]);
                }

                return new GatewayResponse(
                    success: true,
                    transactionId: $id,
                    authority: $id,
                    redirectUrl: $data['link'],
                    rawResponse: $data
                );
            }

            $msg = $data['error_message'] ?? 'IDPay request failed.';
            $this->logTransactionFailure(new GatewayException($msg), $data ?? [], $transaction);

            return new GatewayResponse(
                success: false,
                errorMessage: $msg,
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
        $id = $params['id'] ?? $params['Id'] ?? null;
        $status = $params['status'] ?? $params['Status'] ?? null;
        $orderId = $params['order_id'] ?? $params['OrderId'] ?? null;

        $transaction = $this->findTransactionRecord($id, $orderId);

        if ((int)$status !== 100) {
            $msg = "IDPay payment status is not 100: {$status}";
            $this->logTransactionFailure(new VerificationFailedException($msg), $params, $transaction);
            throw new VerificationFailedException($msg);
        }

        $apiKey = $this->config['api_key'] ?? '';
        $headers = [
            'X-API-KEY' => $apiKey,
            'X-SANDBOX' => $this->isSandbox() ? '1' : '0',
        ];

        $payload = [
            'id' => $id,
            'order_id' => $orderId,
        ];

        try {
            $response = Http::withHeaders($headers)->post('https://api.idpay.ir/v1.1/payment/verify', $payload);
            $data = $response->json();

            if (isset($data['status']) && (int)$data['status'] === 100) {
                $amount = $transaction ? $transaction->amount : $this->amount;

                $receipt = new Receipt(
                    success: true,
                    transactionId: $id,
                    refId: $data['track_id'] ?? $id,
                    gateway: $this->getDriverName(),
                    amount: $amount,
                    cardNumber: $data['payment']['card_no'] ?? null,
                    message: 'IDPay transaction verified successfully.',
                    rawResponse: $data
                );

                $this->logTransactionSuccess($receipt, $transaction);
                return $receipt;
            }

            $msg = $data['error_message'] ?? 'IDPay verify failed.';
            $this->logTransactionFailure(new VerificationFailedException($msg), $data ?? [], $transaction);
            throw new VerificationFailedException($msg);
        } catch (\Throwable $e) {
            if (! ($e instanceof VerificationFailedException)) {
                $this->logTransactionFailure($e, [], $transaction);
                throw new VerificationFailedException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }
}
