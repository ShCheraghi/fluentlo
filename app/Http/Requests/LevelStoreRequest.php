<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LevelStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth/permission is handled by route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'max:10', 'unique:levels,code'],
            'name_fa'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'description_fa' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'order'          => ['required', 'integer', 'min:1', 'unique:levels,order'],
            'icon'           => ['nullable', 'string', 'max:50'],
        ];
    }
}
