<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'level' => new LevelResource($this->whenLoaded('level')),
            'level_id' => $this->level_id,
            'is_current' => $this->is_current,
            'is_completed' => $this->is_completed,
//            'current_unit' => new UnitResource($this->whenLoaded('currentUnit')),
            'current_unit_id' => $this->current_unit_id,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'last_activity_at' => $this->last_activity_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
