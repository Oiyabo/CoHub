<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AddModeratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Akun dengan email tersebut tidak ditemukan di sistem.',
        ];
    }

    // Validasi Lanjutan (Pengecekan Status Member & Moderator)
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $community = $this->route('community');
            $user = User::where('email', $this->email)->first();

            if ($user && $community->isModerator($user)) {
                $validator->errors()->add('email', 'User ini sudah menjadi moderator.');
            }

            if ($user && !$community->isMember($user)) {
                $validator->errors()->add('email', 'Pengguna harus sudah bergabung sebagai anggota komunitas terlebih dahulu sebelum diangkat menjadi moderator.');
            }
        });
    }
}