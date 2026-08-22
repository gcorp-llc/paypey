<?php

namespace Gcorpllc\Paypey\Events;

use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionSuccessful
{
    use Dispatchable, SerializesModels;

    public function __construct(public Transaction $transaction, public ReceiptInterface $receipt)
    {
    }
}
