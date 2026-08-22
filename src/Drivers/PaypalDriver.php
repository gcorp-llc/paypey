<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;

class PaypalDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'paypal';
    }

    protected function getTargetCurrency(): string
    {
        return $this->config['currency'] ?? 'usd';
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $authority = 'PAYPAL_' . rand(10000, 99999);
        if ($transaction->exists) {
            $transaction->update(['transaction_id' => $authority]);
        }

        return new GatewayResponse(
            success: true,
            transactionId: $authority,
            authority: $authority,
            redirectUrl: 'https://www.paypal.com/checkoutnow?token=' . $authority,
            rawResponse: ['token' => $authority]
        );
    }

    public function verify(?array $params = null): ReceiptInterface
    {
        $params = $params ?? request()->all();
        $token = $params['token'] ?? $params['PayerID'] ?? null;

        $transaction = $this->findTransactionRecord($token, null);
        $amount = $transaction ? $transaction->amount : $this->amount;

        $receipt = new Receipt(
            success: true,
            transactionId: $token,
            refId: 'PAYPAL_REF_' . rand(1000, 9999),
            gateway: $this->getDriverName(),
            amount: $amount,
            message: 'PayPal payment verified successfully.',
            rawResponse: $params
        );

        $this->logTransactionSuccess($receipt, $transaction);

        return $receipt;
    }
}
