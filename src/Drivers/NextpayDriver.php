<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Facades\Http;

class NextpayDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'nextpay';
    }

    protected function getTargetCurrency(): string
    {
        return 'rial';
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $apiKey = $this->config['api_key'] ?? '';
        $amount = (int) $this->getConvertedAmount();
        $orderId = rand(1000000, 9999999);

        $payload = [
            'api_key' => $apiKey,
            'amount' => $amount,
            'order_id' => (string) $orderId,
            'callback_uri' => $this->callbackUrl,
        ];

        try {
            $response = Http::post('https://nextpay.org/nx/gateway/token', $payload);
            $data = $response->json();

            if (isset($data['code']) && (int)$data['code'] === -1 && isset($data['trans_id'])) {
                $transId = $data['trans_id'];

                if ($transaction->exists) {
                    $transaction->update(['transaction_id' => $transId, 'ref_id' => (string) $orderId]);
                }

                return new GatewayResponse(
                    success: true,
                    transactionId: $transId,
                    authority: $transId,
                    redirectUrl: "https://nextpay.org/nx/gateway/payment/{$transId}",
                    rawResponse: $data
                );
            }

            $msg = "NextPay request token failed with code: " . ($data['code'] ?? 'unknown');
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
        $transId = $params['trans_id'] ?? $params['transId'] ?? null;
        $orderId = $params['order_id'] ?? $params['orderId'] ?? null;

        $transaction = $this->findTransactionRecord($transId, $orderId);

        $apiKey = $this->config['api_key'] ?? '';
        $amount = $transaction ? $transaction->amount : $this->amount;

        $this->amount = $amount;
        $convertedAmount = (int) $this->getConvertedAmount();

        $payload = [
            'api_key' => $apiKey,
            'amount' => $convertedAmount,
            'trans_id' => $transId,
        ];

        try {
            $response = Http::post('https://nextpay.org/nx/gateway/verify', $payload);
            $data = $response->json();

            if (isset($data['code']) && (int)$data['code'] === 0) {
                $receipt = new Receipt(
                    success: true,
                    transactionId: $transId,
                    refId: $data['Shaparak_Ref_Id'] ?? $transId,
                    gateway: $this->getDriverName(),
                    amount: $amount,
                    cardNumber: $data['card_holder'] ?? null,
                    message: 'NextPay payment verified successfully.',
                    rawResponse: $data
                );

                $this->logTransactionSuccess($receipt, $transaction);
                return $receipt;
            }

            $msg = "NextPay verify failed with code: " . ($data['code'] ?? 'unknown');
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
