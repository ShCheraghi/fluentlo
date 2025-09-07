<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnboardingScreenRequest extends FormRequest
{
    public function authorize(): bool { return true; }


    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var(
                    $this->input('is_active'),
                    FILTER_VALIDATE_BOOL,
                    FILTER_NULL_ON_FAILURE
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title'            => 'sometimes|required|string|max:255',
            'subtitle'         => 'sometimes|nullable|string|max:255',
            'description'      => 'sometimes|nullable|string|max:1000',

            // در آپدیت فایل اختیاری است
            'image'            => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:4096',

            'background_color' => 'sometimes|required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color'       => 'sometimes|required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'button_color'     => 'sometimes|required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'order_index'      => 'sometimes|required|integer|min:0',
            'is_active'        => 'sometimes|boolean',
        ];
    }
}
