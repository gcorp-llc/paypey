<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Facades\Http;

class ParsianDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'parsian';
    }

    protected function getTargetCurrency(): string
    {
        return 'rial';
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $pin = $this->config['pin'] ?? '';
        $amount = (int) $this->getConvertedAmount();
        $orderId = rand(1000000, 9999999);

        $payload = [
            'LoginAccount' => $pin,
            'Amount' => $amount,
            'OrderId' => $orderId,
            'CallBackUrl' => $this->callbackUrl,
        ];

        try {
            $response = Http::post('https://pec.shaparak.ir/NewIPGServices/Sale/SalePaymentRequest.asmx', $payload);
            $data = $response->json();

            if (isset($data['Status']) && (int)$data['Status'] === 0 && isset($data['Token'])) {
                $token = $data['Token'];

                if ($transaction->exists) {
                    $transaction->update(['transaction_id' => $token, 'ref_id' => (string) $orderId]);
                }

                return new GatewayResponse(
                    success: true,
                    transactionId: $token,
                    authority: $token,
                    redirectUrl: "https://pec.shaparak.ir/NewIPG/?Token={$token}",
                    rawResponse: $data
                );
            }

            $msg = $data['Message'] ?? 'Parsian sale request failed.';
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
        $token = $params['Token'] ?? $params['token'] ?? null;
        $status = $params['Status'] ?? $params['status'] ?? null;
        $rrn = $params['RRN'] ?? $params['rrn'] ?? null;

        $transaction = $this->findTransactionRecord($token, $rrn);

        if ((int)$status !== 0) {
            $msg = "Parsian payment failed with status: {$status}";
            $this->logTransactionFailure(new VerificationFailedException($msg), $params, $transaction);
            throw new VerificationFailedException($msg);
        }

        $pin = $this->config['pin'] ?? '';

        $payload = [
            'LoginAccount' => $pin,
            'Token' => $token,
        ];

        try {
            $response = Http::post('https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx', $payload);
            $data = $response->json();

            if (isset($data['Status']) && (int)$data['Status'] === 0) {
                $amount = $transaction ? $transaction->amount : $this->amount;

                $receipt = new Receipt(
                    success: true,
                    transactionId: $token,
                    refId: $rrn ?: $token,
                    gateway: $this->getDriverName(),
                    amount: $amount,
                    cardNumber: $params['CardNumberMasked'] ?? null,
                    message: 'Parsian payment verified successfully.',
                    rawResponse: $data
                );

                $this->logTransactionSuccess($receipt, $transaction);
                return $receipt;
            }

            $msg = $data['Message'] ?? 'Parsian verify failed.';
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
