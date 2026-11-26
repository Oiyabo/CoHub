<div class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
        <h3 class="text-sm font-bold text-slate-900">Kewenangan Admin</h3>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Blokir akun, tutup atau hapus komunitas, dan kelola hak akses lanjutan.
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
        <h3 class="text-sm font-bold text-slate-900 mb-3">Ringkasan</h3>
        <ul class="space-y-2 text-xs text-slate-600">
            <li>{{ number_format($sidebarStats['total_users']) }} pengguna</li>
            <li>{{ number_format($sidebarStats['active_communities']) }} komunitas aktif</li>
            <li>{{ number_format($sidebarStats['total_posts']) }} postingan</li>
            <li>{{ number_format($sidebarStats['pending_reports']) }} laporan menunggu</li>
        </ul>
    </div>
</div>
