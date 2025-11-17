<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'unit_id', 'sequence', 'title_fa', 'title_en',
        'scenario_fa', 'scenario_en', 'conversation_en', 'conversation_fa',
        'image_url', 'audio_url', 'hint_1', 'hint_2', 'hint_3',
        'explanation_fa', 'explanation_en', 'is_published'
    ];
    protected $casts = ['is_published' => 'boolean'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TopicAttempt::class);
    }

    /**
     * Get hints as array
     */
    public function getHints(): array
    {
        return [1 => $this->hint_1, 2 => $this->hint_2, 3 => $this->hint_3];
    }

    /**
     * Check if topic is completed by a user
     */
    public function isCompletedBy(User $user): bool
    {
        return $this->attempts()->where('user_id', $user->id)->where('status', 'pass')->exists();
    }

    /**
     * Get best attempt for user
     */
    public function getBestAttemptFor(User $user): ?TopicAttempt
    {
        return $this->attempts()->where('user_id', $user->id)->where('status', 'pass')
            ->orderBy('similarity_score', 'desc')->first();
    }

    /**
     * Count user attempts
     */
    public function getAttemptsCountFor(User $user): int
    {
        return $this->attempts()->where('user_id', $user->id)->count();
    }
}
