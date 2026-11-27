<x-app-layout>
    <div class="space-y-4 max-w-4xl mx-auto">
        <!-- HEADER -->
        <div class="flex items-center gap-3">
            <a href="{{ $user->username ? route('profile.show', $user->username) : route('dashboard') }}"
               aria-label="Kembali"
               class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Pengaturan Profil</h1>
                <p class="text-xs text-slate-400">Kelola foto, banner, informasi akun, dan keamanan Anda</p>
            </div>
        </div>

        @if (session('status') === 'profile-updated')
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">
                Profil berhasil diperbarui.
            </div>
        @endif

        <!-- KARTU INFORMASI PROFIL & MEDIA -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            @include('profile.partials.update-profile-information-form')
        </div>

        <!-- KARTU PERBARUI PASSWORD -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            @include('profile.partials.update-password-form')
        </div>

        <!-- KARTU HAPUS AKUN -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
