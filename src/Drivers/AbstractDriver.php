<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Contracts\GatewayInterface;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Events\TransactionCreated;
use Gcorpllc\Paypey\Events\TransactionFailed;
use Gcorpllc\Paypey\Events\TransactionSuccessful;
use Gcorpllc\Paypey\Exceptions\InvalidAmountException;
use Gcorpllc\Paypey\Models\Transaction;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractDriver implements GatewayInterface
{
    protected int|float $amount = 0;
    protected string $callbackUrl = '';
    protected string $description = '';
    protected array $metadata = [];
    protected ?Transaction $transactionRecord = null;

    public function __construct(protected array $config = [])
    {
    }

    public function amount(int|float $amount): static
    {
        if ($amount <= 0) {
            throw new InvalidAmountException("Amount must be greater than zero.");
        }
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

    abstract public function getDriverName(): string;

    /**
     * Target currency expected by gateway API ('toman', 'rial', 'usd', etc.)
     */
    abstract protected function getTargetCurrency(): string;

    /**
     * Get converted amount based on global and driver currency configuration.
     */
    protected function getConvertedAmount(): int|float
    {
        $appCurrency = strtolower(config('paypey.currency', 'toman'));
        $gatewayCurrency = strtolower($this->config['currency'] ?? $this->getTargetCurrency());

        $amount = $this->amount;

        if ($appCurrency === 'toman' && $gatewayCurrency === 'rial') {
            return $amount * 10;
        }

        if ($appCurrency === 'rial' && $gatewayCurrency === 'toman') {
            return $amount / 10;
        }

        return $amount;
    }

    protected function logTransactionCreation(): Transaction
    {
        $uuid = (string) Str::uuid();

        if (config('paypey.database_logging', true)) {
            $this->transactionRecord = Transaction::create([
                'uuid' => $uuid,
                'gateway' => $this->getDriverName(),
                'amount' => $this->amount,
                'currency' => config('paypey.currency', 'toman'),
                'status' => 'pending',
                'description' => $this->description,
                'callback_url' => $this->callbackUrl,
                'metadata' => $this->metadata,
            ]);

            event(new TransactionCreated($this->transactionRecord));
        } else {
            $this->transactionRecord = new Transaction([
                'uuid' => $uuid,
                'gateway' => $this->getDriverName(),
                'amount' => $this->amount,
                'currency' => config('paypey.currency', 'toman'),
                'status' => 'pending',
            ]);
        }

        return $this->transactionRecord;
    }

    protected function logTransactionSuccess(ReceiptInterface $receipt, ?Transaction $record = null): void
    {
        $transaction = $record ?? $this->findTransactionRecord($receipt->getTransactionId(), $receipt->getRefId());

        if ($transaction && config('paypey.database_logging', true)) {
            $transaction->update([
                'status' => 'successful',
                'transaction_id' => $receipt->getTransactionId() ?: $transaction->transaction_id,
                'ref_id' => $receipt->getRefId() ?: $transaction->ref_id,
                'card_number' => $receipt->getCardNumber() ?: $transaction->card_number,
                'raw_response' => $receipt->getRawResponse(),
            ]);
        }

        if ($transaction) {
            event(new TransactionSuccessful($transaction, $receipt));
        }
    }

    protected function logTransactionFailure(?Throwable $exception = null, ?array $rawResponse = null, ?Transaction $record = null): void
    {
        $transaction = $record ?? $this->transactionRecord;

        if ($transaction && config('paypey.database_logging', true) && $transaction->exists) {
            $transaction->update([
                'status' => 'failed',
                'raw_response' => $rawResponse ?? ['error' => $exception?->getMessage()],
            ]);
        }

        if ($transaction) {
            event(new TransactionFailed($transaction, $exception, $rawResponse));
        }
    }

    protected function findTransactionRecord($transactionId = null, $refId = null): ?Transaction
    {
        if ($this->transactionRecord && $this->transactionRecord->exists) {
            return $this->transactionRecord;
        }

        if ($transactionId) {
            $tx = Transaction::where('transaction_id', $transactionId)->first();
            if ($tx) return $tx;
        }

        if ($refId) {
            $tx = Transaction::where('ref_id', $refId)->first();
            if ($tx) return $tx;
        }

        return null;
    }
}
