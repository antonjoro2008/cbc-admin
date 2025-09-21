<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'available_minutes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'available_minutes' => 'decimal:2',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the token transactions for this wallet.
     */
    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    /**
     * Add tokens to the wallet.
     */
    public function addTokens(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Add minutes to the wallet.
     */
    public function addMinutes(float $amount): void
    {
        $this->increment('available_minutes', $amount);
    }

    /**
     * Deduct tokens from the wallet.
     */
    public function deductTokens(float $amount): bool
    {
        if ($this->balance >= $amount) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Deduct minutes from the wallet.
     */
    public function deductMinutes(float $amount): bool
    {
        if ($this->available_minutes >= $amount) {
            $this->decrement('available_minutes', $amount);
            return true;
        }
        return false;
    }

    /**
     * Check if the wallet has sufficient token balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Check if the wallet has sufficient minutes.
     */
    public function hasSufficientMinutes(float $amount): bool
    {
        return $this->available_minutes >= $amount;
    }

    /**
     * Add both tokens and minutes to the wallet.
     */
    public function addTokensAndMinutes(float $tokens, float $minutes): void
    {
        $this->addTokens($tokens);
        $this->addMinutes($minutes);
    }

    /**
     * Deduct both tokens and minutes from the wallet.
     */
    public function deductTokensAndMinutes(float $tokens, float $minutes): bool
    {
        if ($this->hasSufficientBalance($tokens) && $this->hasSufficientMinutes($minutes)) {
            $this->deductTokens($tokens);
            $this->deductMinutes($minutes);
            return true;
        }
        return false;
    }
}
