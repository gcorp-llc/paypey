<?php

namespace Gcorpllc\Paypey\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $table = 'paypay_transactions';

    protected $fillable = [
        'driver',
        'amount',
        'currency',
        'authority',
        'transaction_id',
        'status',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
    ];
}
