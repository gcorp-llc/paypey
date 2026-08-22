<?php

namespace Gcorpllc\Paypey\Events;

use Gcorpllc\Paypey\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Transaction $transaction)
    {
    }
}
