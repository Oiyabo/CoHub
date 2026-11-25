<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityAboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:5000'],
            'guidelines' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'discord' => ['nullable', 'url', 'max:255'],
            'github' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.url' => 'Format tautan website tidak valid (contoh: https://domain.com).',
            'discord.url' => 'Format tautan Discord tidak valid (contoh: https://discord.gg/xyz).',
            'github.url' => 'Format tautan GitHub/Medsos tidak valid (contoh: https://github.com/xyz).',
        ];
    }
}