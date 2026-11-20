<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommunityRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul aturan wajib diisi.',
            'title.max' => 'Judul aturan maksimal 120 karakter.',
            'description.max' => 'Deskripsi aturan maksimal 500 karakter.',
        ];
    }
}