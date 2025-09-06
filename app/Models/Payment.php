<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'channel',
        'reference',
        'amount',
        'status',
        'currency',
        'tokens',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'tokens' => 'integer',
    ];

    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the M-Pesa payment details.
     */
    public function mpesaPayment(): HasOne
    {
        return $this->hasOne(MpesaPayment::class);
    }

    /**
     * Get the bank payment details.
     */
    public function bankPayment(): HasOne
    {
        return $this->hasOne(BankPayment::class);
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }

    /**
     * Check if the payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the payment is via M-Pesa.
     */
    public function isMpesa(): bool
    {
        return $this->channel === 'mpesa';
    }

    /**
     * Check if the payment is via bank.
     */
    public function isBank(): bool
    {
        return $this->channel === 'bank';
    }
}