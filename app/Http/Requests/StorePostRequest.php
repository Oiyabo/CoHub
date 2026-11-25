<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Boleh kosong (postingan umum). Kalau diisi, user harus sudah menjadi anggota
            // komunitas itu (tercatat di tabel community_members lewat tombol "Gabung").
            'community_id' => [
                'nullable',
                'integer',
                'exists:communities,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $isMember = $this->user()
                        ->joinedCommunities()
                        ->where('communities.id', $value)
                        ->exists();

                    if (! $isMember) {
                        $fail('Kamu harus bergabung ke komunitas ini terlebih dahulu sebelum memposting di sana.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'community_id' => 'komunitas',
            'title' => 'judul',
            'content' => 'isi diskusi',
            'image' => 'gambar',
        ];
    }

    public function messages(): array
    {
        return [
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus PNG, JPG, atau WEBP.',
            'image.max'   => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}
