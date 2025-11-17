<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'level_id', 'sequence', 'title_fa', 'title_en',
        'description_fa', 'description_en', 'image_url',
        'introduction_fa', 'introduction_en', 'topics_count', 'is_published'
    ];
    protected $casts = ['is_published' => 'boolean'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sequence');
    }

    /**
     * Check if user completed this unit
     */
    public function isCompletedBy(User $user): bool
    {
        $topicCount = $this->topics()->count();
        $completedCount = $this->topics()
            ->whereIn('id', $user->attempts()->where('status', 'pass')->pluck('topic_id'))
            ->count();

        return $topicCount > 0 && $topicCount === $completedCount;
    }

    /**
     * Get progress percentage for a user
     */
    public function getProgressFor(User $user): float
    {
        $topicCount = $this->topics()->count();
        if ($topicCount === 0) return 0;

        $completedCount = $this->topics()
            ->whereIn('id', $user->attempts()->where('status', 'pass')->pluck('topic_id'))
            ->count();

        return ($completedCount / $topicCount) * 100;
    }

    /**
     * Get next topic for the user
     */
    public function getNextTopicFor(User $user): ?Topic
    {
        $completedIds = $user->attempts()->where('status', 'pass')->pluck('topic_id')->toArray();
        return $this->topics()->whereNotIn('id', $completedIds)->first();
    }
}
