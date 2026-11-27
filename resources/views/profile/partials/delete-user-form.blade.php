<section class="space-y-4">
    <header class="border-b border-slate-100 pb-4">
        <h2 class="text-base font-bold text-red-600">
            Hapus Akun
        </h2>

        <p class="text-xs text-slate-400 mt-1">
            Setelah akun dihapus, seluruh data dan aktivitas Anda akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
        </p>
    </header>

    <div>
        <button type="button"
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 font-semibold text-xs px-4 py-2.5 rounded-xl transition-colors cursor-pointer">
            Hapus Akun Saya
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 space-y-4">
            @csrf
            @method('delete')

            <h2 class="text-base font-bold text-slate-900">
                Apakah Anda yakin ingin menghapus akun?
            </h2>

            <p class="text-xs text-slate-500 leading-relaxed">
                Setelah akun Anda dihapus, seluruh sumber daya dan data akan dihapus secara permanen. Masukkan password Anda untuk mengonfirmasi penghapusan akun secara permanen.
            </p>

            <div>
                <label for="password" class="sr-only">Password</label>
                <input id="password"
                       name="password"
                       type="password"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-red-400 focus:ring-red-400"
                       placeholder="Masukkan password Anda untuk konfirmasi">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button"
                        x-on:click="$dispatch('close')"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>

                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold text-xs px-4 py-2 rounded-xl shadow-xs transition-colors">
                    Ya, Hapus Akun
                </button>
            </div>
        </form>
    </x-modal>
</section>
