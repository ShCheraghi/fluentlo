<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAssessment extends Model
{
    use Cachable;
    protected $table = 'user_assessments';

    protected $fillable = [
        'user_id',
        'target_language',
        'native_language',
        'motivations',
        'topics',
        'self_level',
        'improve_areas',
        'timeline',
        'daily_words'
    ];

    protected $casts = [
        'motivations' => 'array',
        'topics' => 'array',
        'improve_areas' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
