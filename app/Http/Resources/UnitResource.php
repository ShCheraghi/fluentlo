<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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
            'level_id' => $this->level_id,
            'sequence' => $this->sequence,
            'title_fa' => $this->title_fa,
            'title_en' => $this->title_en,
            'description_fa' => $this->description_fa,
            'description_en' => $this->description_en,
            'image_url' => $this->image_url,
            'introduction_fa' => $this->introduction_fa,
            'introduction_en' => $this->introduction_en,
            'is_published' => $this->is_published,
            'topics_count' => $this->topics_count,
            'level' => new LevelResource($this->whenLoaded('level')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
