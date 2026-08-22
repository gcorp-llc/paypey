<?php

namespace Gcorpllc\Paypey\Contracts;

interface ReceiptInterface
{
    public function isSuccessful(): bool;
    public function getTransactionId(): string|int|null;
    public function getRefId(): string|int|null;
    public function getCardNumber(): ?string;
    public function getGateway(): string;
    public function getAmount(): int|float;
    public function getMessage(): ?string;
    public function getRawResponse(): array;
    public function toArray(): array;
}
