<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OnboardingScreen extends Model
{
    use HasFactory, Cachable;

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image_path',
        'background_color',
        'text_color',
        'button_color',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'order_index' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public static function currentVersion(): int
    {
        return optional(static::max('updated_at'))->timestamp ?? 0;
    }
}
