<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Allowed values for learner gender (CBC reporting & inclusion analytics).
     * Optional at registration; institutions should collect where policy allows.
     *
     * @var list<string>
     */
    public const GENDER_VALUES = ['female', 'male', 'non_binary', 'prefer_not_to_say', 'other'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution_id',
        'classroom_id',
        'name',
        'phone_number',
        'admission_number',
        'email',
        'password',
        'user_type',
        'grade_level',
        'gender',
        'guardian_email',
        'guardian_phone',
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
     * Optional class grouping for institution learners (CBC pilot / analytics).
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
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
     * Check if the user is a teacher (institution-scoped).
     */
    public function isTeacher(): bool
    {
        return $this->user_type === 'teacher';
    }

    /**
     * Get the learners for the parent.
     */
    public function parentLearners(): HasMany
    {
        return $this->hasMany(ParentLearner::class, 'user_id');
    }

    /**
     * Institution admins use the panel for learner analytics; platform admins retain full access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isInstitution();
    }
}