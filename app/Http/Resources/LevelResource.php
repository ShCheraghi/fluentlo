<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'name_fa'        => $this->name_fa,
            'name_en'        => $this->name_en,
            'description_fa' => $this->description_fa,
            'description_en' => $this->description_en,
            'order'          => $this->order,
            'icon'           => $this->icon,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
