<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AppVersion extends Model
{
    use HasFactory, Cachable;

    protected $fillable = [
        'platform',
        'version',
        'build_number',
        'force_update',
        'title',
        'description',
        'store_links',
        'is_active',
    ];

    protected $casts = [
        'force_update' => 'boolean',
        'is_active' => 'boolean',
        'store_links' => 'array',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('build_number', 'desc');
    }

    // Helper methods
    public function getAppStoreLink(): ?string
    {
        return $this->store_links['app_store'] ?? null;
    }

    public function getGooglePlayLink(): ?string
    {
        return $this->store_links['google_play'] ?? null;
    }

    public function getStoreLink(): ?string
    {
        return match ($this->platform) {
            'ios' => $this->getAppStoreLink(),
            'android' => $this->getGooglePlayLink(),
            default => null,
        };
    }

    /**
     * مقایسه ورژن با ورژن ارسالی از کلاینت
     */
    public static function shouldForceUpdate(string $platform, string $clientVersion, int $clientBuildNumber): bool
    {
        $latestVersion = static::active()
            ->forPlatform($platform)
            ->where('force_update', true)
            ->latest()
            ->first();

        if (!$latestVersion) {
            return false;
        }

        // مقایسه build number (راحت‌ترین روش)
        return $clientBuildNumber < $latestVersion->build_number;
    }
}
