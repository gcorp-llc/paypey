<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Str;

class SandboxDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'sandbox';
    }

    protected function getTargetCurrency(): string
    {
        return 'toman';
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $authority = 'SANDBOX_' . Str::random(16);

        if ($transaction->exists) {
            $transaction->update(['transaction_id' => $authority]);
        }

        $redirectUrl = route('paypey.sandbox.redirect', ['authority' => $authority]);

        return new GatewayResponse(
            success: true,
            transactionId: $authority,
            authority: $authority,
            redirectUrl: $redirectUrl,
            rawResponse: ['authority' => $authority, 'status' => 'OK']
        );
    }

    public function verify(?array $params = null): ReceiptInterface
    {
        $params = $params ?? request()->all();
        $authority = $params['authority'] ?? $params['Authority'] ?? $params['transaction_id'] ?? null;

        $transaction = $this->findTransactionRecord($authority, $authority);

        if ($transaction && isset($params['status']) && $params['status'] === 'failed') {
            $receipt = new Receipt(
                success: false,
                transactionId: $authority,
                refId: null,
                gateway: $this->getDriverName(),
                amount: $this->amount,
                message: 'Sandbox payment cancelled/failed by user.',
                rawResponse: $params
            );

            $this->logTransactionFailure(new VerificationFailedException($receipt->getMessage()), $params, $transaction);
            throw new VerificationFailedException($receipt->getMessage());
        }

        $refId = 'REF_' . rand(100000, 999999);
        $amount = $transaction ? $transaction->amount : $this->amount;

        $receipt = new Receipt(
            success: true,
            transactionId: $authority,
            refId: $refId,
            gateway: $this->getDriverName(),
            amount: $amount,
            cardNumber: '6037********1234',
            message: 'Sandbox payment completed successfully.',
            rawResponse: ['ref_id' => $refId, 'status' => 'OK']
        );

        $this->logTransactionSuccess($receipt, $transaction);

        return $receipt;
    }
}
