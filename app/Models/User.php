<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'total_points'
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isSocialUser(): bool
    {
        return $this->socialAccounts()->exists();
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(UserAssessment::class);
    }
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function userLevels(): HasMany
    {
        return $this->hasMany(UserLevel::class);
    }

    /**
     * All attempts made by the user
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TopicAttempt::class);
    }

    /**
     * Current active level
     */
    public function currentLevel(): ?Level
    {
        return $this->userLevels()
            ->where('is_current', true)
            ->first()?->level;
    }

    /**
     * Current active unit in current level
     */
    public function currentUnit(): ?Unit
    {
        $userLevel = $this->userLevels()->where('is_current', true)->first();
        return $userLevel?->currentUnit;
    }

    /**
     * Total points (basic implementation: 10 points per passed attempt)
     */
    public function getTotalPoints(): int
    {
        return $this->attempts()->where('status', 'pass')->count() * 10;
    }

    /**
     * Percentage progress in a level
     */
    public function getLevelProgress(Level $level): float
    {
        $topicIds = Topic::whereIn('unit_id', $level->units()->pluck('id'))->pluck('id');
        if ($topicIds->isEmpty()) return 0;

        $completedCount = $this->attempts()
            ->whereIn('topic_id', $topicIds)
            ->where('status', 'pass')
            ->distinct('topic_id')
            ->count();

        return ($completedCount / $topicIds->count()) * 100;
    }

    /**
     * Simple stats for dashboard
     */
    public function getStats(): array
    {
        $totalAttempts = $this->attempts()->count();
        $successfulAttempts = $this->attempts()->where('status', 'pass')->count();

        return [
            'total_points' => $this->getTotalPoints(),
            'total_attempts' => $totalAttempts,
            'successful_attempts' => $successfulAttempts,
            'current_level' => $this->currentLevel()?->code,
            'accuracy' => $totalAttempts > 0
                ? round(($successfulAttempts / $totalAttempts) * 100, 2)
                : 0,
        ];
    }

}
