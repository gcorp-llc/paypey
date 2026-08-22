<?php

namespace Gcorpllc\Paypey\Contracts;

interface GatewayInterface
{
    /**
     * Set the amount for transaction.
     */
    public function amount(int|float $amount): static;

    /**
     * Set callback URL for payment response.
     */
    public function callbackUrl(string $url): static;

    /**
     * Set description or detail.
     */
    public function description(string $description): static;

    /**
     * Set additional metadata.
     */
    public function with(array $metadata): static;

    /**
     * Request/purchase transaction and return response.
     */
    public function request(): GatewayResponseInterface;

    /**
     * Alias for request().
     */
    public function purchase(): GatewayResponseInterface;

    /**
     * Verify transaction.
     */
    public function verify(?array $params = null): ReceiptInterface;

    /**
     * Get gateway driver name.
     */
    public function getDriverName(): string;
}
