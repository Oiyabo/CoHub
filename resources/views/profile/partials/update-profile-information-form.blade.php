@php
    $initials = strtoupper(
        collect(explode(' ', $user->name))
            ->filter()
            ->take(2)
            ->map(fn($word) => substr($word, 0, 1))
            ->implode('')
    );
@endphp

<section x-data="{
    avatarPreview: null,
    bannerPreview: null,
    bioCount: {{ strlen(old('bio', $user->bio ?? '')) }},
    handleAvatarPreview(e) {
        const file = e.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    },
    handleBannerPreview(e) {
        const file = e.target.files[0];
        if (file) {
            this.bannerPreview = URL.createObjectURL(file);
        }
    }
}">
    <header class="mb-6 border-b border-slate-100 pb-4">
        <h2 class="text-base font-bold text-slate-900">
            Informasi Profil
        </h2>
        <p class="text-xs text-slate-400 mt-1">
            Perbarui foto profil, banner, nama pengguna, bio, dan alamat email Anda.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post"
          action="{{ route('profile.update') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @method('patch')

        <!-- PREVIEW & UPLOAD BANNER -->
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">
                Banner Profil
            </label>
            <div class="relative w-full h-32 sm:h-44 rounded-xl border border-slate-200 overflow-hidden bg-slate-50 mb-3">
                <template x-if="bannerPreview">
                    <img :src="bannerPreview" alt="Preview Banner" class="w-full h-full object-cover">
                </template>
                <template x-if="!bannerPreview">
                    @if($user->banner_url)
                        <img src="{{ $user->banner_url }}" alt="Banner {{ $user->name }}" class="w-full h-full object-cover" onerror="this.style.display='none';">
                    @else
                        <div class="w-full h-full bg-gradient-to-r from-pink-100 via-rose-100 to-purple-100 flex items-center justify-center text-slate-400 text-xs">
                            Belum ada banner (menggunakan default gradient)
                        </div>
                    @endif
                </template>
            </div>
            <input type="file"
                   id="banner"
                   name="banner"
                   accept="image/jpeg,image/png,image/jpg,image/webp"
                   @change="handleBannerPreview($event)"
                   class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border file:border-slate-200 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer">
            <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WEBP. Maksimal 3MB.</p>
            <x-input-error class="mt-2" :messages="$errors->get('banner')" />
        </div>

        <!-- PREVIEW & UPLOAD AVATAR -->
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">
                Foto Profil (Avatar)
            </label>
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-full border-2 border-slate-200 bg-pink-100 text-pink-700 flex items-center justify-center font-extrabold text-2xl overflow-hidden shadow-xs shrink-0">
                    <template x-if="avatarPreview">
                        <img :src="avatarPreview" alt="Preview Avatar" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!avatarPreview">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <span style="display: none;">{{ $initials }}</span>
                        @else
                            <span>{{ $initials }}</span>
                        @endif
                    </template>
                </div>
                <div class="flex-1 min-w-0">
                    <input type="file"
                           id="avatar"
                           name="avatar"
                           accept="image/jpeg,image/png,image/jpg,image/webp"
                           @change="handleAvatarPreview($event)"
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border file:border-slate-200 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- NAMA -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                    Nama Lengkap
                </label>
                <input id="name"
                       name="name"
                       type="text"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400"
                       value="{{ old('name', $user->name) }}"
                       required
                       autofocus
                       autocomplete="name">
                <x-input-error class="mt-1" :messages="$errors->get('name')" />
            </div>

            <!-- USERNAME -->
            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 mb-1">
                    Username
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">@</span>
                    <input id="username"
                           name="username"
                           type="text"
                           class="w-full pl-7 text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400"
                           value="{{ old('username', $user->username) }}"
                           required
                           autocomplete="username">
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('username')" />
            </div>
        </div>

        <!-- EMAIL -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 mb-1">
                Alamat Email
            </label>
            <input id="email"
                   name="email"
                   type="email"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400"
                   value="{{ old('email', $user->email) }}"
                   required
                   autocomplete="email">
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-xs text-amber-700">
                        Alamat email Anda belum diverifikasi.
                        <button form="send-verification" class="underline hover:text-amber-900 font-semibold">
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 text-xs text-emerald-600 font-medium">
                            Tautan verifikasi baru telah dikirim ke alamat email Anda.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- BIO / ABOUT -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="bio" class="block text-xs font-bold text-slate-700">
                    Tentang Saya (Bio)
                </label>
                <span class="text-[11px] text-slate-400" x-text="bioCount + '/500'"></span>
            </div>
            <textarea id="bio"
                      name="bio"
                      rows="4"
                      maxlength="500"
                      @input="bioCount = $event.target.value.length"
                      placeholder="Ceritakan sedikit tentang dirimu..."
                      class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400 leading-relaxed">{{ old('bio', $user->bio) }}</textarea>
            <p class="text-[11px] text-slate-400 mt-1">Dukung teks multibaris untuk profil publik.</p>
            <x-input-error class="mt-1" :messages="$errors->get('bio')" />
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-5 py-2.5 rounded-xl shadow-xs transition-colors">
                Simpan Perubahan
            </button>
            <a href="{{ $user->username ? route('profile.show', $user->username) : route('dashboard') }}"
               class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Batal
            </a>
        </div>
    </form>
</section>
