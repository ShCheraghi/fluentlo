<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;
use App\Models\UserLevel;

class UserLevelService
{
    /**
     * Get user's current level
     */
    public function getCurrentLevel(User $user): ?UserLevel
    {
        return $user->userLevels()
            ->where('is_current', true)
            ->with('level', 'currentUnit')
            ->first();
    }

    /**
     * Start a level for user
     */
    public function startLevel(User $user, Level $level): UserLevel
    {
        // Set all other levels to not current
        $user->userLevels()->update(['is_current' => false]);

        // Find or create user level
        $userLevel = UserLevel::firstOrCreate(
            ['user_id' => $user->id, 'level_id' => $level->id],
            [
                'is_current' => true,
                'is_completed' => false,
                'started_at' => now(),
                'last_activity_at' => now(),
            ]
        );

        // If already exists, just set it as current
        if (!$userLevel->wasRecentlyCreated) {
            $userLevel->update([
                'is_current' => true,
                'last_activity_at' => now(),
            ]);
        }

        return $userLevel->fresh();
    }

    /**
     * Move to next level
     */
    public function moveToNextLevel(User $user, Level $currentLevel): ?UserLevel
    {
        $nextLevel = Level::where('order', '>', $currentLevel->order)
            ->orderBy('order')
            ->first();

        if (!$nextLevel) {
            return null;
        }

        return $this->startLevel($user, $nextLevel);
    }

    /**
     * Update last activity
     */
    public function updateActivity(UserLevel $userLevel): void
    {
        $userLevel->update(['last_activity_at' => now()]);
    }
}
