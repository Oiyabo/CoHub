<x-guest-layout>
    <!-- Logo Co -->
    <div class="flex justify-center mb-4">
        <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
            Co
        </div>
    </div>

    <!-- Title -->
    <h2 class="text-2xl font-black text-slate-900 text-center tracking-tight mb-2">
        Konfirmasi kata sandi
    </h2>
    <p class="text-xs text-slate-500 text-center mb-6 leading-relaxed">
        Ini adalah area aman aplikasi. Silakan konfirmasi kata sandi kamu sebelum melanjutkan.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                Kata sandi
            </label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                placeholder="••••••••"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('password') border-rose-500 @enderror">
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Tombol Konfirmasi -->
        <div class="pt-2">
            <button type="submit"
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition">
                Konfirmasi
            </button>
        </div>
    </form>
</x-guest-layout>
