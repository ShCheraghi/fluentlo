<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'name',
        'avatar',
        'access_token',
        'refresh_token',
        'expires_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'expires_in' => 'integer',
        ];
    }

    /**
     * Get the user that owns the social account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find social account by provider credentials
     */
    public static function findByProviderCredentials(string $provider, string $providerId): ?self
    {
        return static::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    /**
     * Find social account by provider and email
     */
    public static function findByProviderEmail(string $provider, string $email): ?self
    {
        return static::where('provider', $provider)
            ->where('email', $email)
            ->first();
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->expires_in) {
            return false;
        }

        return $this->updated_at->addSeconds($this->expires_in)->isPast();
    }
}
