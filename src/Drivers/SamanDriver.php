<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Facades\Http;

class SamanDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'saman';
    }

    protected function getTargetCurrency(): string
    {
        return 'rial';
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $terminalId = $this->config['terminal_id'] ?? $this->config['merchant_id'] ?? '';
        $amount = (int) $this->getConvertedAmount();
        $resNum = rand(1000000, 9999999);

        $payload = [
            'action' => 'token',
            'TerminalId' => $terminalId,
            'Amount' => $amount,
            'ResNum' => $resNum,
            'RedirectUrl' => $this->callbackUrl,
        ];

        try {
            $response = Http::post('https://sep.shaparak.ir/onlinepg/onlinepg', $payload);
            $data = $response->json();

            if (isset($data['status']) && (int)$data['status'] === 1 && isset($data['token'])) {
                $token = $data['token'];

                if ($transaction->exists) {
                    $transaction->update(['transaction_id' => $token, 'ref_id' => (string) $resNum]);
                }

                return new GatewayResponse(
                    success: true,
                    transactionId: $token,
                    authority: $token,
                    redirectUrl: "https://sep.shaparak.ir/OnlinePG/OnlinePG?Token={$token}",
                    rawResponse: $data
                );
            }

            $msg = $data['errorDesc'] ?? 'Saman SEP request token failed.';
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
        $state = $params['State'] ?? $params['state'] ?? null;
        $refNum = $params['RefNum'] ?? $params['refNum'] ?? null;
        $resNum = $params['ResNum'] ?? $params['resNum'] ?? null;
        $traceNo = $params['TraceNo'] ?? $params['traceNo'] ?? null;

        $transaction = $this->findTransactionRecord(null, $resNum ?: $refNum);

        if ($state !== 'OK' && ! empty($state)) {
            $msg = "Saman payment state is not OK: {$state}";
            $this->logTransactionFailure(new VerificationFailedException($msg), $params, $transaction);
            throw new VerificationFailedException($msg);
        }

        $terminalId = $this->config['terminal_id'] ?? $this->config['merchant_id'] ?? '';

        $payload = [
            'RefNum' => $refNum,
            'TerminalNumber' => $terminalId,
        ];

        try {
            $response = Http::post('https://sep.shaparak.ir/verifyTxnRandomSession' , $payload);
            $data = $response->json();

            if (isset($data['Success']) && $data['Success'] === true) {
                $amount = $transaction ? $transaction->amount : $this->amount;

                $receipt = new Receipt(
                    success: true,
                    transactionId: $refNum,
                    refId: $traceNo ?: $refNum,
                    gateway: $this->getDriverName(),
                    amount: $amount,
                    cardNumber: $params['SecurePan'] ?? null,
                    message: 'Saman payment verified successfully.',
                    rawResponse: $data
                );

                $this->logTransactionSuccess($receipt, $transaction);
                return $receipt;
            }

            $msg = $data['ResultDescription'] ?? 'Saman verify transaction failed.';
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
