<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicAttempt extends Model
{
    protected $table = 'topic_attempts';
    protected $fillable = [
        'user_id', 'topic_id', 'user_answer', 'expected_answer',
        'similarity_score', 'status', 'attempt_number', 'is_first_try_correct',
        'time_spent_seconds', 'feedback_fa', 'feedback_en', 'analysis'
    ];
    protected $casts = [
        'is_first_try_correct' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Display result badge with emoji
     */
    public function getResultBadge(): string
    {
        if ($this->status === 'pass') {
            return $this->is_first_try_correct ? '🟢 Correct (No Hint)' : '🟡 Correct (With Hint)';
        }
        return '🔴 Incorrect';
    }

    /**
     * Similarity percentage
     */
    public function getSimilarityPercentage(): string
    {
        return $this->similarity_score . '%';
    }

    /**
     * Format time spent
     */
    public function getFormattedTime(): string
    {
        $seconds = $this->time_spent_seconds;
        if ($seconds < 60) return $seconds . ' sec';
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;
        return $minutes . ' min ' . $secs . ' sec';
    }
}
