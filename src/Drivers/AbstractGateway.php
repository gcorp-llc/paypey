<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\Invoice;
use Gcorpllc\Paypey\Contracts\Gateway;

abstract class AbstractGateway implements Gateway
{
    protected Invoice $invoice;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function invoice(Invoice $invoice): static
    {
        $this->invoice = $invoice;
        return $this;
    }

    abstract public function purchase(): mixed;
}
