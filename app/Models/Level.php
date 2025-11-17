<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = [
        'code',
        'name_fa',
        'name_en',
        'description_fa',
        'description_en',
        'order',
        'icon',
    ];

    /**
     * Units (sections) under this level.
     * A level contains multiple learning units.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sequence');
    }

    /**
     * UserLevel records for users who started / completed this level.
     */
    public function userLevels(): HasMany
    {
        return $this->hasMany(UserLevel::class);
    }

    /**
     * Full level display name (icon + name)
     * Useful for UI or admin panel lists.
     */
    public function getFullName(): string
    {
        return trim(($this->icon ? $this->icon . ' ' : '') . "{$this->name_fa} ({$this->code})");
    }
}
