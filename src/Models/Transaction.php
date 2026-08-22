<?php

namespace Gcorpllc\Paypey\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'paypey_transactions';

    protected $fillable = [
        'uuid',
        'gateway',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'ref_id',
        'card_number',
        'description',
        'callback_url',
        'metadata',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'raw_response' => 'array',
    ];

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
