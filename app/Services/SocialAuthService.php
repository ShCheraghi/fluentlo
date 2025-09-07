<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    /**
     * Supported social providers
     */
    private const SUPPORTED_PROVIDERS = ['google', 'facebook'];

    /**
     * Handle social user authentication
     *
     * @throws SocialAuthException
     */
    public function handleSocialUser(string $provider, SocialiteUser $socialUser): array
    {
        if (!$this->isProviderSupported($provider)) {
            throw new SocialAuthException("Provider {$provider} is not supported");
        }

        if (empty($socialUser->email)) {
            throw new SocialAuthException("Email is required from social provider");
        }

        try {
            return DB::transaction(function () use ($provider, $socialUser) {
                $user = $this->findOrCreateUser($provider, $socialUser);
                $this->updateOrCreateSocialAccount($user, $provider, $socialUser);

                $token = $user->createToken('social-auth', ['*'], now()->addDays(30));

                Log::info('Social authentication successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'email' => $user->email
                ]);

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'provider' => $provider,
                    ],
                    'token' => $token->plainTextToken,
                    'expires_at' => $token->accessToken->expires_at?->toISOString(),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Social authentication failed', [
                'provider' => $provider,
                'email' => $socialUser->email ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new SocialAuthException('Authentication failed. Please try again.');
        }
    }

    /**
     * Find existing user or create new one
     */
    private function findOrCreateUser(string $provider, SocialiteUser $socialUser): User
    {
        // First, try to find by social account
        $socialAccount = SocialAccount::findByProviderCredentials($provider, $socialUser->id);
        if ($socialAccount) {
            $this->updateUserFromSocialData($socialAccount->user, $socialUser);
            return $socialAccount->user;
        }

        // Try to find user by email
        $existingUser = User::where('email', $socialUser->email)->first();
        if ($existingUser) {
            $this->updateUserFromSocialData($existingUser, $socialUser);
            return $existingUser;
        }

        // Create new user
        return $this->createUserFromSocialData($provider, $socialUser);
    }

    /**
     * Create new user from social data
     */
    private function createUserFromSocialData(string $provider, SocialiteUser $socialUser): User
    {

        return User::create([
            'name' => $socialUser->name ?? $socialUser->nickname ?? 'Unknown User',
            'email' => $socialUser->email,
            'avatar' => $socialUser->avatar ?? null,
            'email_verified_at' => $this->isEmailVerifiedByProvider($provider, $socialUser) ? now() : null,
        ]);
    }

    /**
     * Update existing user with fresh social data
     */
    private function updateUserFromSocialData(User $user, SocialiteUser $socialUser): void
    {
        $updateData = [];

        // Update avatar if user doesn't have one
        if (empty($user->avatar) && !empty($socialUser->avatar)) {
            $updateData['avatar'] = $socialUser->avatar;
        }

        // Update name if it's generic and we have a better one
        if (($user->name === 'Unknown User' || empty($user->name)) && !empty($socialUser->name)) {
            $updateData['name'] = $socialUser->name;
        }

        // Mark email as verified if not already
        if (empty($user->email_verified_at)) {
            $updateData['email_verified_at'] = now();
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }
    }

    /**
     * Update or create social account record
     */
    private function updateOrCreateSocialAccount(User $user, string $provider, SocialiteUser $socialUser): SocialAccount
    {
        return SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_id' => $socialUser->id,
                'email' => $socialUser->email,
                'name' => $socialUser->name ?? $socialUser->nickname ?? null,
                'avatar' => $socialUser->avatar ?? null,
                'access_token' => $socialUser->token ?? null,
                'refresh_token' => $socialUser->refreshToken ?? null,
                'expires_in' => null, // Usually not provided by Laravel Socialite
            ]
        );
    }

    /**
     * Check if provider is supported
     */
    private function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS);
    }

    /**
     * Get supported providers list
     */
    public function getSupportedProviders(): array
    {
        return self::SUPPORTED_PROVIDERS;
    }

    /**
     * Unlink social account from user
     */
    public function unlinkSocialAccount(User $user, string $provider): bool
    {
        $socialAccount = $user->socialAccounts()
            ->where('provider', $provider)
            ->first();

        if (!$socialAccount) {
            return false;
        }

        // Check if user has password or other social accounts
        $hasPassword = !empty($user->password);
        $hasOtherSocialAccounts = $user->socialAccounts()
            ->where('provider', '!=', $provider)
            ->exists();

        if (!$hasPassword && !$hasOtherSocialAccounts) {
            throw new SocialAuthException('Cannot unlink the only authentication method');
        }

        return $socialAccount->delete();
    }

    /**
     * Get user's social providers
     */
    public function getUserSocialProviders(User $user): array
    {
        return $user->socialAccounts()
            ->pluck('provider')
            ->toArray();
    }

    private function isEmailVerifiedByProvider(string $provider, \Laravel\Socialite\Contracts\User $socialUser): bool
    {
        if ($provider === 'google') {
            return (bool)($socialUser->user['email_verified'] ?? $socialUser->email_verified ?? true);
        }

        if ($provider === 'facebook') {
            return (bool)($socialUser->user['verified'] ?? false);
        }

        return false;
    }
}
