<?php

namespace Gcorpllc\Paypey\Events;

use Gcorpllc\Paypey\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TransactionFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Transaction $transaction, public ?Throwable $exception = null, public ?array $rawResponse = null)
    {
    }
}
