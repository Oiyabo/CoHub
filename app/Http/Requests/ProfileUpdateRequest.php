<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'alpha_dash',
                'lowercase',
                Rule::unique(User::class, 'username')
                    ->ignore($this->user()->id),
            ],

            'bio' => [
                'nullable',
                'string',
                'max:500',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)
                    ->ignore($this->user()->id),
            ],
        ];
    }
}
