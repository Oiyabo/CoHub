<!-- DEFAULT RIGHT SIDEBAR (Sesuai Data Seeder Tanpa Hardcoded Data) -->
<aside class="space-y-4">
    
    <!-- 1. Tombol Buat Komunitas -->
    <div class="bg-blue-600 rounded-2xl p-5 text-white shadow-xs">
        <h3 class="text-sm font-bold">Ruang Komunitas Baru</h3>
        <p class="text-xs text-blue-100 mt-1">
            Buat ruang diskusi untuk topik atau mata kuliahmu.
        </p>
        <button type="button" 
                @click="$dispatch('open-create-community')"
                class="mt-3 px-4 py-2 bg-white text-blue-600 hover:bg-blue-50 font-semibold text-xs rounded-xl transition-all cursor-pointer">
            + Buat Komunitas
        </button>
    </div>

    <!-- 2. Daftar Komunitas yang Tersedia di Database Seeder -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">
            Komunitas Tersedia ({{ $globalCommunities->count() }})
        </h4>

        <div class="space-y-2.5">
            @forelse($globalCommunities as $comm)
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-800">
                            c/{{ $comm->name }}
                        </span>
                        <span class="text-[10px] font-mono px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md">
                            {{ $comm->posts_count }} post
                        </span>
                    </div>
                    @if($comm->description)
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">
                            {{ $comm->description }}
                        </p>
                    @endif
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada komunitas terdaftar.</p>
            @endforelse
        </div>
    </div>

    <!-- 3. Info Singkat Project -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs text-xs text-slate-500 space-y-1.5">
        <p class="font-bold text-slate-700">CoHub (Kelompok 6)</p>
        <p class="text-[11px] text-slate-400">Pemrograman Web Lanjut — Jurusan Ilmu Komputer Universitas Lampung 2026</p>
    </div>

</aside>
