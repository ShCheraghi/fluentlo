<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingScreenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'subtitle'         => $this->subtitle,
            'description'      => $this->description,
            'image_url'        => $this->image_url, // از اکسسور مدل
            'background_color' => $this->background_color,
            'text_color'       => $this->text_color,
            'button_color'     => $this->button_color,
            'order_index'      => $this->order_index,
            'is_active'        => $this->is_active,
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
