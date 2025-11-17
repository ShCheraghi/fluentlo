<?php

namespace App\Services;

use App\Models\Level;

class LevelService
{
    public function all()
    {
        return Level::orderBy('order')->get();
    }

    public function create(array $data): Level
    {
        return Level::create($data);
    }

    public function update(Level $level, array $data): Level
    {
        $level->update($data);
        return $level->fresh();
    }

    public function delete(Level $level): void
    {
        $level->delete();
    }

    public function reorder(array $items): void
    {
        foreach ($items as $item) {
            Level::where('id', $item['id'])->update(['order' => $item['order']]);
        }
    }
}
