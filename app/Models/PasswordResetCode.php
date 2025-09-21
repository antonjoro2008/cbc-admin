<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PasswordResetCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'email',
        'code',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Check if the code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the code is valid (not used and not expired)
     */
    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    /**
     * Mark the code as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Generate a 6-digit reset code
     */
    public static function generateCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new reset code for a phone number
     */
    public static function createForPhone(string $phoneNumber, ?string $email = null): self
    {
        // Delete any existing unused codes for this phone number
        self::where('phone_number', $phoneNumber)
            ->where('used', false)
            ->delete();

        return self::create([
            'phone_number' => $phoneNumber,
            'email' => $email,
            'code' => self::generateCode(),
            'expires_at' => Carbon::now()->addMinutes(15), // 15 minutes expiry
            'used' => false
        ]);
    }

    /**
     * Find a valid reset code
     */
    public static function findValidCode(string $phoneNumber, string $code): ?self
    {
        return self::where('phone_number', $phoneNumber)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }
}
