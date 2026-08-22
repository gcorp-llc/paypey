<?php

namespace Gcorpllc\Paypey\Classes;

use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class GatewayResponse implements GatewayResponseInterface
{
    public function __construct(
        protected bool $success,
        protected string|int|null $transactionId = null,
        protected string|int|null $authority = null,
        protected ?string $redirectUrl = null,
        protected ?string $errorMessage = null,
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

    public function getAuthority(): string|int|null
    {
        return $this->authority;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function redirect(): RedirectResponse
    {
        if (! $this->redirectUrl) {
            throw new \RuntimeException('No redirect URL available for this gateway response.');
        }

        return Redirect::to($this->redirectUrl);
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
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
            'authority' => $this->authority,
            'redirect_url' => $this->redirectUrl,
            'error_message' => $this->errorMessage,
            'raw_response' => $this->rawResponse,
        ];
    }
}
