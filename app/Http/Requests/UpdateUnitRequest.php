<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // یا منطق احراز هویت خود را پیاده‌سازی کنید
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sequence' => ['nullable', 'integer', 'min:1'],
            'title_fa' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_fa' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'introduction_fa' => ['nullable', 'string'],
            'introduction_en' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
