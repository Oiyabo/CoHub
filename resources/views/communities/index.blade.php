<x-app-layout>
    <x-slot name="sidebar">
        <!-- SIDEBAR KHUSUS JELAJAHI KOMUNITAS (Sesuai Gambar) -->
        <div class="space-y-5">
            <!-- 1. Blue CTA Card -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-3xl p-6 text-white shadow-sm">
                <h3 class="text-base font-bold">Punya topik sendiri?</h3>
                <p class="text-xs text-blue-100 mt-1.5 leading-relaxed">
                    Buat komunitasmu dan angkat moderator pilihanmu.
                </p>
                <button type="button" @click="$dispatch('open-create-community')"
                    class="mt-4 px-5 py-2.5 bg-white text-blue-600 hover:bg-blue-50 font-bold text-xs rounded-full shadow-xs transition-all cursor-pointer">
                    Buat Komunitas
                </button>
            </div>

            <!-- 2. Widget "Lagi ramai" -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <h4 class="text-sm font-bold text-slate-800 mb-4">Lagi ramai</h4>
                <div class="space-y-3.5">
                    <div class="flex items-center gap-3">
                        <span
                            class="w-6 h-6 rounded-full bg-blue-50 text-blue-600 font-bold text-xs flex items-center justify-center shrink-0">1</span>
                        <div>
                            <p class="text-xs font-bold text-slate-800 hover:text-blue-600 cursor-pointer">
                                #SupabasePooler</p>
                            <p class="text-[10px] text-slate-400">1,2 rb post</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 font-bold text-xs flex items-center justify-center shrink-0">2</span>
                        <div>
                            <p class="text-xs font-bold text-slate-800 hover:text-blue-600 cursor-pointer">
                                #PemrogramanWebLanjut</p>
                            <p class="text-[10px] text-slate-400">840 post</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="w-6 h-6 rounded-full bg-amber-50 text-amber-600 font-bold text-xs flex items-center justify-center shrink-0">3</span>
                        <div>
                            <p class="text-xs font-bold text-slate-800 hover:text-blue-600 cursor-pointer">
                                #Laravel12Breeze</p>
                            <p class="text-[10px] text-slate-400">620 post</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- HEADER & PENCARIAN -->
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Jelajahi</h1>
            <p class="text-xs text-slate-500 mt-1">Temukan ruang diskusi yang cocok untukmu</p>

            <!-- Search Bar Bulat -->
            <form action="{{ route('communities.index') }}" method="GET" class="mt-4">
                <div class="relative flex items-center">
                    <span class="absolute left-4 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Cari komunitas, topik, atau kata kunci"
                        class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200/80 rounded-2xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 shadow-2xs transition-colors">
                </div>
            </form>

            <!-- Kategori Filter Pills -->
            <div class="flex items-center gap-2 mt-4 overflow-x-auto pb-1 text-xs font-semibold select-none">
                <span class="px-4 py-1.5 rounded-full bg-blue-600 text-white cursor-pointer shadow-xs">Semua</span>
                <span
                    class="px-4 py-1.5 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 cursor-pointer transition">Teknologi</span>
                <span
                    class="px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100 cursor-pointer transition">Akademik</span>
                <span
                    class="px-4 py-1.5 rounded-full bg-amber-50 text-amber-600 hover:bg-amber-100 cursor-pointer transition">Kampus</span>
                <span
                    class="px-4 py-1.5 rounded-full bg-purple-50 text-purple-600 hover:bg-purple-100 cursor-pointer transition">Data</span>
                <span
                    class="px-4 py-1.5 rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 cursor-pointer transition">Karier</span>
            </div>
        </div>

        <!-- GRID DAFTAR KOMUNITAS (2 KOLOM SESUAI GAMBAR) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @forelse($communities as $comm)
                @php
                    $colors = [
                        ['banner' => 'bg-blue-50', 'avatar' => 'bg-blue-100 text-blue-700'],
                        ['banner' => 'bg-emerald-50', 'avatar' => 'bg-emerald-100 text-emerald-700'],
                        ['banner' => 'bg-purple-50', 'avatar' => 'bg-purple-100 text-purple-700'],
                        ['banner' => 'bg-amber-50', 'avatar' => 'bg-amber-100 text-amber-700'],
                    ];
                    $theme = $colors[$loop->index % count($colors)];
                    $initials = strtoupper(substr($comm->name, 0, 2));
                @endphp
                <div
                    class="bg-white rounded-3xl border border-slate-100 shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Header Banner -->
                        <div class="h-20 {{ $theme['banner'] }} relative overflow-hidden">
                            @if($comm->banner_path)
                                <img src="{{ $comm->banner_url }}" alt="Banner" class="w-full h-full object-cover">
                            @endif
                        </div>

                        <!-- Floating Avatar & Body -->
                        <div class="px-5 pb-5">
                            <div
                                class="w-12 h-12 rounded-2xl {{ $theme['avatar'] }} flex items-center justify-center font-bold text-xs -mt-6 border-4 border-white shadow-2xs">
                                {{ $initials }}
                            </div>

                            <a href="{{ route('communities.show', $comm->name) }}"
                                class="block font-bold text-slate-900 text-sm mt-3 hover:text-blue-600 transition">
                                c/{{ $comm->name }}
                            </a>

                            <p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
                                {{ $comm->description ?? 'Komunitas diskusi mahasiswa.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Kartu -->
                    <div class="px-5 pb-5 pt-0 flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-medium">
                            {{ $comm->posts_count ?? 0 }} post · {{ $comm->moderators_count ?? 1 }} mod
                        </span>

                        <a href="{{ route('communities.show', $comm->name) }}"
                            class="px-4 py-1.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-2xs">
                            Gabung
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-3xl p-12 text-center text-slate-400">
                    <p class="text-sm font-medium">Belum ada komunitas yang cocok dengan pencarian.</p>
                </div>
            @endforelse
        </div>

        <!-- PAGINASI -->
        <div class="mt-4">
            {{ $communities->links() }}
        </div>

    </div>
</x-app-layout>