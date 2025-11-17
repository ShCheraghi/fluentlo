<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth/permission is handled by route middleware.
        return true;
    }

    public function rules(): array
    {
        $levelId = $this->route('level')?->id ?? null;

        return [
            'code'           => [
                'required',
                'string',
                'max:10',
                Rule::unique('levels', 'code')->ignore($levelId),
            ],
            'name_fa'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'description_fa' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'order'          => [
                'required',
                'integer',
                'min:1',
                Rule::unique('levels', 'order')->ignore($levelId),
            ],
            'icon'           => ['nullable', 'string', 'max:50'],
        ];
    }
}
