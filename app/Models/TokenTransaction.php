<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'transaction_type',
        'tokens',
        'reference',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tokens' => 'decimal:2',
    ];

    /**
     * Get the wallet that this transaction belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Check if the transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->transaction_type === 'credit';
    }

    /**
     * Check if the transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->transaction_type === 'debit';
    }

    /**
     * Get the formatted tokens with sign.
     */
    public function getFormattedTokens(): string
    {
        $sign = $this->isCredit() ? '+' : '-';
        return $sign . $this->tokens;
    }
}
