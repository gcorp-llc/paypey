<?php

namespace Gcorpllc\Paypey;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayInterface;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Gcorpllc\Paypey\Models\Transaction;

class FakeGateway implements GatewayInterface
{
    protected int|float $amount = 0;
    protected string $callbackUrl = '';
    protected string $description = '';
    protected array $metadata = [];

    public function __construct(
        protected string $driverName,
        protected mixed $expectation = null
    ) {
    }

    public function amount(int|float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function with(array $metadata): static
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    public function purchase(): GatewayResponseInterface
    {
        return $this->request();
    }

    public function request(): GatewayResponseInterface
    {
        $transactionId = 'FAKE_TX_' . rand(100000, 999999);

        if (config('paypey.database_logging', true)) {
            Transaction::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'gateway' => $this->driverName,
                'amount' => $this->amount,
                'currency' => config('paypey.currency', 'toman'),
                'status' => 'pending',
                'transaction_id' => $transactionId,
                'description' => $this->description,
                'callback_url' => $this->callbackUrl,
                'metadata' => $this->metadata,
            ]);
        }

        if (is_array($this->expectation) && isset($this->expectation['request'])) {
            return $this->expectation['request'];
        }

        if ($this->expectation instanceof GatewayResponseInterface) {
            return $this->expectation;
        }

        return new GatewayResponse(
            success: true,
            transactionId: $transactionId,
            authority: $transactionId,
            redirectUrl: 'https://fake-gateway.local/pay/' . $transactionId,
            rawResponse: ['fake' => true, 'status' => 'OK']
        );
    }

    public function verify(?array $params = null): ReceiptInterface
    {
        $params = $params ?? request()->all();

        if (is_array($this->expectation) && isset($this->expectation['verify'])) {
            $verifyResult = $this->expectation['verify'];
            if ($verifyResult instanceof ReceiptInterface) {
                if (! $verifyResult->isSuccessful()) {
                    throw new VerificationFailedException($verifyResult->getMessage() ?: 'Fake verification failed');
                }
                return $verifyResult;
            }
            if (is_array($verifyResult) && isset($verifyResult['success']) && ! $verifyResult['success']) {
                throw new VerificationFailedException($verifyResult['message'] ?? 'Fake verification failed');
            }
        }

        if ($this->expectation instanceof ReceiptInterface) {
            if (! $this->expectation->isSuccessful()) {
                throw new VerificationFailedException($this->expectation->getMessage() ?: 'Fake verification failed');
            }
            return $this->expectation;
        }

        $transactionId = $params['transaction_id'] ?? $params['Authority'] ?? $params['authority'] ?? 'FAKE_TX_12345';
        $refId = 'FAKE_REF_' . rand(100000, 999999);

        return new Receipt(
            success: true,
            transactionId: $transactionId,
            refId: $refId,
            gateway: $this->driverName,
            amount: $this->amount ?: 10000,
            cardNumber: '6037********1234',
            message: 'Fake transaction verified successfully.',
            rawResponse: ['fake' => true, 'ref_id' => $refId]
        );
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }
}
