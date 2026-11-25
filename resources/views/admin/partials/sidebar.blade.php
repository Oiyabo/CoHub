<div class="space-y-4">
    <div class="bg-slate-900 text-white rounded-3xl p-5 shadow-xs">
        <div class="flex items-center gap-2.5 mb-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm shrink-0">
                AD
            </div>
            <div>
                <p class="text-xs font-extrabold tracking-tight">Panel Kontrol Admin</p>
                <p class="text-[11px] text-slate-400">Mode Administrator Aktif</p>
            </div>
        </div>

        <nav class="space-y-1.5 text-xs font-semibold">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Ringkasan Statistik</span>
                <span>→</span>
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Pengguna &amp; Hak Akses</span>
                <span>→</span>
            </a>

            <a href="{{ route('admin.communities.index') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.communities.*') ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Kelola Komunitas</span>
                <span>→</span>
            </a>
        </nav>

        <div class="mt-4 pt-4 border-t border-slate-800">
            <a href="{{ route('dashboard') }}"
               class="flex items-center justify-center gap-2 w-full px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors">
                <span>← Kembali ke Forum User</span>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl p-5 border border-amber-200 shadow-xs">
        <h3 class="text-xs font-extrabold text-amber-800 uppercase tracking-wider mb-2.5">
            Batas Kewenangan Peran
        </h3>
        <ul class="space-y-2 text-[11px] text-slate-600 leading-relaxed">
            <li class="flex items-start gap-2">
                <span class="text-amber-600 font-bold">•</span>
                <span>Admin tidak dapat memblokir atau mengubah peran akunnya sendiri.</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-amber-600 font-bold">•</span>
                <span>Akun yang diblokir tidak dapat dinaikkan menjadi Admin.</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-amber-600 font-bold">•</span>
                <span>Sistem wajib menyisakan minimal 1 Admin aktif.</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-amber-600 font-bold">•</span>
                <span>Komunitas yang ditutup otomatis menonaktifkan postingan &amp; komentar baru.</span>
            </li>
        </ul>
    </div>
</div>
