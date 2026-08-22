<?php

namespace Gcorpllc\Paypey\Contracts;

interface GatewayResponseInterface
{
    public function isSuccessful(): bool;
    public function getTransactionId(): string|int|null;
    public function getAuthority(): string|int|null;
    public function getRedirectUrl(): ?string;
    public function redirect();
    public function getErrorMessage(): ?string;
    public function getRawResponse(): array;
    public function toArray(): array;
}
