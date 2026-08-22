<?php

namespace Gcorpllc\Paypey\Classes;

use Gcorpllc\Paypey\Contracts\ReceiptInterface;

class Receipt implements ReceiptInterface
{
    public function __construct(
        protected bool $success,
        protected string|int|null $transactionId,
        protected string|int|null $refId,
        protected string $gateway,
        protected int|float $amount,
        protected ?string $cardNumber = null,
        protected ?string $message = null,
        protected array $rawResponse = []
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getTransactionId(): string|int|null
    {
        return $this->transactionId;
    }

    public function getRefId(): string|int|null
    {
        return $this->refId;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }

    public function getAmount(): int|float
    {
        return $this->amount;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'ref_id' => $this->refId,
            'card_number' => $this->cardNumber,
            'gateway' => $this->gateway,
            'amount' => $this->amount,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
