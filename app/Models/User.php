<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution_id',
        'name',
        'phone_number',
        'admission_number',
        'email',
        'password',
        'user_type',
        'grade_level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the institution that the user belongs to.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the wallet for the user.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the effective wallet for the user.
     * For institution students, returns the institution admin's wallet.
     * For other users, returns their own wallet.
     */
    public function getEffectiveWallet()
    {
        if ($this->isStudent() && $this->institution_id) {
            // For institution students, get the institution admin's wallet
            $institutionAdmin = User::where('institution_id', $this->institution_id)
                ->where('user_type', 'institution')
                ->first();
            
            return $institutionAdmin ? $institutionAdmin->wallet : null;
        }
        
        // For individual users (parents, individual students), return their own wallet
        return $this->wallet;
    }

    /**
     * Get the assessments created by the user.
     */
    public function createdAssessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'created_by');
    }

    /**
     * Get the assessment attempts for the user.
     */
    public function assessmentAttempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class, 'student_id');
    }

    /**
     * Get the payments for the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if the user is an institution.
     */
    public function isInstitution(): bool
    {
        return $this->user_type === 'institution';
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->user_type === 'student';
    }

    /**
     * Check if the user is a parent.
     */
    public function isParent(): bool
    {
        return $this->user_type === 'parent';
    }

    /**
     * Get the learners for the parent.
     */
    public function parentLearners(): HasMany
    {
        return $this->hasMany(ParentLearner::class, 'parent_id');
    }
}
