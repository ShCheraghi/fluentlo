<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|string',
            'answers.*.option_ids' => 'required|array|min:1',
            'answers.*.option_ids.*' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'The answers field is required.',
            'answers.*.question_id.required' => 'Each answer must have a question ID.',
            'answers.*.option_ids.required' => 'Each answer must have at least one option.',
            'answers.*.option_ids.*.required' => 'Each option ID is required.',
        ];
    }
}
