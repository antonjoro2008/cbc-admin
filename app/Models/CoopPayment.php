<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoopPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'message_reference',
        'transaction_id',
        'payment_ref',
        'phone_number',
        'account_number',
        'event_type',
        'narration',
        'amount',
        'currency',
        'status',
        'source',
        'stk_response',
        'callback_payload',
        'ipn_payload',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'stk_response' => 'array',
        'callback_payload' => 'array',
        'ipn_payload' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
