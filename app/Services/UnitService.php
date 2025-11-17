<?php

namespace App\Services;

use App\Models\Level;
use App\Models\Unit;

class UnitService
{
    /**
     * Get all units for a level
     */
    public function getByLevel(Level $level)
    {
        return $level->units()->where('is_published', true)->orderBy('sequence')->get();
    }

    /**
     * Get single unit
     */
    public function get(Unit $unit): Unit
    {
        return $unit;
    }

    /**
     * Create unit
     */
    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    /**
     * Update unit
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);
        return $unit->fresh();
    }

    /**
     * Delete unit
     */
    public function delete(Unit $unit): void
    {
        $unit->delete();
    }
}
