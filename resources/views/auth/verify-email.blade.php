<x-guest-layout>
    <!-- Logo Co -->
    <div class="flex justify-center mb-4">
        <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
            Co
        </div>
    </div>

    <!-- Title -->
    <h2 class="text-2xl font-black text-slate-900 text-center tracking-tight mb-2">
        Verifikasi email
    </h2>
    <p class="text-xs text-slate-500 text-center mb-6 leading-relaxed">
        Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email kamu dengan mengeklik tautan yang baru saja kami kirimkan.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700 text-center">
            Tautan verifikasi baru telah dikirimkan ke alamat email yang kamu daftarkan.
        </div>
    @endif

    <div class="space-y-3 pt-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-500 hover:text-slate-700 transition">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
