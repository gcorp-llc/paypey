<?php

namespace Gcorpllc\Paypey\Classes;

class Receipt
{
    protected bool $success;
    protected ?string $transactionId = null;
    protected ?string $referenceId = null;
    protected ?string $message = null;
    protected ?string $cardPan = null;
    protected array $details = [];

    public function __construct(bool $success, ?string $transactionId = null)
    {
        $this->success = $success;
        $this->transactionId = $transactionId;
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function referenceId(string $id): self
    {
        $this->referenceId = $id;
        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function cardPan(string $cardPan): self
    {
        $this->cardPan = $cardPan;
        return $this;
    }

    public function getCardPan(): ?string
    {
        return $this->cardPan;
    }

    public function details(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
