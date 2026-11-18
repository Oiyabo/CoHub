<!-- DEFAULT RIGHT SIDEBAR (Sesuai Data Seeder Tanpa Hardcoded Data) -->
<aside class="space-y-4">

    <!-- 1. Tombol Buat Komunitas -->
    <div class="bg-blue-600 rounded-2xl p-5 text-white shadow-xs">
        <h3 class="text-sm font-bold">Ruang Komunitas Baru</h3>
        <p class="text-xs text-blue-100 mt-1">
            Buat ruang diskusi untuk topik atau mata kuliahmu.
        </p>
        <button type="button" @click="$dispatch('open-create-community')"
            class="mt-3 px-4 py-2 bg-white text-blue-600 hover:bg-blue-50 font-semibold text-xs rounded-xl transition-all cursor-pointer">
            + Buat Komunitas
        </button>
    </div>

    <!-- 2. Daftar Komunitas yang Tersedia (Terhubung ke Detail Komunitas) -->
    <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
        <div class="flex items-center justify-between mb-3.5">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                Komunitas Pilihan
            </h4>
            <a href="{{ route('communities.index') }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                Lihat semua &rarr;
            </a>
        </div>

        <div class="space-y-3">
            @forelse($globalCommunities as $comm)
                @php
                    $initial = strtoupper(substr($comm->name, 0, 2));
                @endphp
                <div class="flex items-center justify-between gap-2.5 p-2 rounded-2xl hover:bg-slate-50 transition">
                    <a href="{{ route('communities.show', $comm->name) }}" class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div
                            class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 hover:text-blue-600 transition truncate">
                                c/{{ $comm->name }}
                            </p>
                            <p class="text-[10px] text-slate-400">
                                {{ $comm->posts_count }} diskusi
                            </p>
                        </div>
                    </a>

                    <a href="{{ route('communities.show', $comm->name) }}"
                        class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition shrink-0">
                        Gabung
                    </a>
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada komunitas terdaftar.</p>
            @endforelse
        </div>
    </div>

    <!-- 3. Info Singkat Project -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs text-xs text-slate-500 space-y-1.5">
        <p class="font-bold text-slate-700">CoHub (Kelompok 6)</p>
        <p class="text-[11px] text-slate-400">Pemrograman Web Lanjut — Jurusan Ilmu Komputer Universitas Lampung 2026
        </p>
    </div>

</aside>