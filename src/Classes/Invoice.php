<?php

namespace Gcorpllc\Paypey\Classes;

class Invoice
{
    protected int $amount;
    protected string $currency = 'IRR';
    protected ?string $callbackUrl = null;
    protected ?string $description = null;
    protected ?string $email = null;
    protected ?string $mobile = null;
    protected array $metadata = [];

    public function amount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function currency(string $currency): self
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function callbackUrl(string $url): self
    {
        $this->callbackUrl = $url;
        return $this;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function email(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function mobile(string $mobile): self
    {
        $this->mobile = $mobile;
        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
