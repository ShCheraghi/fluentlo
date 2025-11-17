<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevel extends Model
{
    protected $table = 'user_levels';
    protected $fillable = [
        'user_id', 'level_id', 'is_current', 'is_completed',
        'current_unit_id', 'started_at', 'completed_at', 'last_activity_at'
    ];
    protected $casts = [
        'is_current' => 'boolean',
        'is_completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'current_unit_id');
    }

    /**
     * Set current unit for this level
     */
    public function setCurrentUnit(Unit $unit): void
    {
        $this->update(['current_unit_id' => $unit->id]);
    }

    /**
     * Progress percentage for this level
     */
    public function getProgressPercentage(): float
    {
        $unitIds = $this->level->units()->pluck('id');
        if ($unitIds->isEmpty()) return 0;

        $completedUnitIds = Topic::whereIn('unit_id', $unitIds)
            ->whereIn('id', $this->user->attempts()->where('status', 'pass')->pluck('topic_id'))
            ->pluck('unit_id')
            ->unique();

        return (count($completedUnitIds) / count($unitIds)) * 100;
    }
}
