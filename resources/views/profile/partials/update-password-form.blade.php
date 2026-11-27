<section>
    <header class="mb-6 border-b border-slate-100 pb-4">
        <h2 class="text-base font-bold text-slate-900">
            Perbarui Password
        </h2>

        <p class="text-xs text-slate-400 mt-1">
            Pastikan akun Anda menggunakan password yang panjang dan aman.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-xs font-bold text-slate-700 mb-1">
                Password Saat Ini
            </label>
            <input id="update_password_current_password"
                   name="current_password"
                   type="password"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400"
                   autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="update_password_password" class="block text-xs font-bold text-slate-700 mb-1">
                    Password Baru
                </label>
                <input id="update_password_password"
                       name="password"
                       type="password"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400"
                       autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
            </div>

            <div>
                <label for="update_password_password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">
                    Konfirmasi Password Baru
                </label>
                <input id="update_password_password_confirmation"
                       name="password_confirmation"
                       type="password"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-400 focus:ring-blue-400"
                       autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-5 py-2.5 rounded-xl shadow-xs transition-colors">
                Simpan Password
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-xs font-medium text-emerald-600"
                >Password berhasil diperbarui.</p>
            @endif
        </div>
    </form>
</section>
