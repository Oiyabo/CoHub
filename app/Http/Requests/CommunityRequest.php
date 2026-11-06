<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Ambil ID komunitas saat ini jika dalam konteks update (edit)
        $communityId = $this->route('community') ? $this->route('community')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('communities', 'name')->ignore($communityId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama komunitas wajib diisi.',
            'name.min' => 'Nama komunitas minimal 3 karakter.',
            'name.max' => 'Nama komunitas maksimal 50 karakter.',
            'name.regex' => 'Nama komunitas hanya boleh berisi huruf, angka, strip (-), dan garis bawah (_).',
            'name.unique' => 'Nama komunitas tersebut sudah digunakan, pilih nama lain.',
            'description.max' => 'Deskripsi tidak boleh melebihi 1000 karakter.',
            'banner.image' => 'File banner harus berupa gambar.',
            'banner.max' => 'Ukuran gambar banner maksimal 2 MB.',
        ];
    }
}
