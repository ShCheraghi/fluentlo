<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnboardingScreenRequest extends FormRequest
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
            'title'            => 'required|string|max:255',
            'subtitle'         => 'nullable|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'image'            => 'required|file|mimes:jpg,jpeg,png,webp|max:4096',
            'background_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color'       => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'button_color'     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'order_index'      => 'required|integer|min:0',
            'is_active'        => 'boolean',
        ];
    }
}
