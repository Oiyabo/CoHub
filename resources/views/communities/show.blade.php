<x-app-layout>
    <x-slot name="sidebar">
        <!-- SIDEBAR DETAIL KOMUNITAS (Sesuai Gambar) -->
        <div class="space-y-4">
            <!-- 1. Tentang Komunitas -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <h4 class="text-sm font-bold text-slate-800 mb-2">Tentang komunitas</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    {{ $community->description ?? 'Diskusi Laravel, Eloquent, dan arsitektur aplikasi web modern.' }}
                    Dibuat oleh {{ $community->creator ? $community->creator->name : 'Administrator' }}.
                </p>
            </div>

            <!-- 2. Aturan Komunitas -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <h4 class="text-sm font-bold text-slate-800 mb-3">Aturan</h4>
                <div class="space-y-3 text-xs">
                    <div class="flex items-start gap-2.5">
                        <span
                            class="w-5 h-5 rounded-full bg-blue-50 text-blue-600 font-bold text-[11px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                        <div>
                            <p class="font-bold text-slate-800">Tetap sesuai topik</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span
                            class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[11px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                        <div>
                            <p class="font-bold text-slate-800">Hormati sesama anggota</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span
                            class="w-5 h-5 rounded-full bg-amber-50 text-amber-600 font-bold text-[11px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                        <div>
                            <p class="font-bold text-slate-800">Dilarang spam & iklan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Daftar Moderator -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-slate-800">Moderator</h4>
                    @can('assignModerator', $community)
                        <a href="{{ route('communities.edit', $community->name) }}?tab=moderator"
                            class="text-[11px] font-semibold text-blue-600 hover:underline">
                            Kelola
                        </a>
                    @endcan
                </div>

                <div class="space-y-3">
                    @forelse($moderators as $mod)
                        @php
                            $initial = strtoupper(substr($mod->name, 0, 2));
                        @endphp
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-[11px]">
                                {{ $initial }}
                            </div>
                            <div class="text-xs">
                                <p class="font-bold text-slate-800">{{ $mod->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ '@' . Str::slug($mod->name, '') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada moderator.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">

        <!-- BANNER & HEADER KOMUNITAS -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden">
            <!-- Area Banner Pastel dengan Lingkaran Dekoratif -->
            <div
                class="h-44 bg-gradient-to-r from-blue-50 via-indigo-50/50 to-blue-100/60 relative overflow-hidden flex items-center justify-end px-12">
                @if($community->banner_path)
                    <img src="{{ $community->banner_url }}" alt="Banner"
                        class="absolute inset-0 w-full h-full object-cover">
                @else
                    <div class="w-40 h-40 rounded-full bg-purple-200/40 blur-2xl absolute -right-6 -bottom-6"></div>
                    <div class="w-32 h-32 rounded-full bg-blue-200/40 blur-xl absolute right-16 top-2"></div>
                @endif
            </div>

            <div class="px-6 pb-6 pt-0">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <!-- Floating Avatar Logo & Info -->
                    <div>
                        <div
                            class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-700 font-black text-lg flex items-center justify-center -mt-8 border-4 border-white shadow-xs">
                            {{ strtoupper(substr($community->name, 0, 2)) }}
                        </div>

                        <h1 class="text-2xl font-black text-slate-900 mt-3 tracking-tight">
                            c/{{ $community->name }}
                        </h1>

                        <!-- Badges Statistik -->
                        <div class="flex items-center gap-2 mt-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600">
                                920 anggota
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600">
                                {{ $posts->total() }} diskusi
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                Publik
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Aksi Kanan -->
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                            class="px-4 py-2 rounded-full border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold text-xs transition">
                            Bagikan
                        </button>

                        @auth
                            @php
                                $isMod = $community->isModerator(Auth::user());
                                $isOwnerOrAdmin = Auth::user()->isAdmin() || Auth::user()->id === $community->created_by;
                            @endphp

                            @if($isOwnerOrAdmin || $isMod)
                                <a href="{{ route('communities.edit', $community->name) }}"
                                    class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs flex items-center gap-1.5 shadow-xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                    <span>Ruang Moderasi</span>
                                </a>
                            @else
                                <button type="button"
                                    class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition">
                                    Gabung
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                                class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition">
                                Gabung
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Sub-nav Tabs -->
                <div class="flex items-center gap-2 mt-5 pt-4 border-t border-slate-100 text-xs font-semibold">
                    <span class="px-4 py-1.5 rounded-full bg-slate-100 text-slate-800 cursor-pointer">Diskusi</span>
                    <span
                        class="px-4 py-1.5 rounded-full text-slate-400 hover:text-slate-600 cursor-pointer">Tentang</span>
                    <span
                        class="px-4 py-1.5 rounded-full text-slate-400 hover:text-slate-600 cursor-pointer">Anggota</span>
                </div>
            </div>
        </div>

        <!-- BANNER KHUSUS MODERATOR (Sesuai Gambar "Komunitas (Tampilan Moderator)") -->
        @auth
            @if($community->isModerator(Auth::user()) || Auth::user()->isAdmin())
                <div
                    class="bg-purple-50/70 border border-purple-200/80 rounded-2xl p-3.5 flex items-center gap-2.5 text-xs text-purple-800 font-medium shadow-2xs">
                    <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    <span>Kamu moderator di komunitas ini. Tombol moderasi muncul di setiap postingan.</span>
                </div>
            @endif
        @endauth

        <!-- DAFTAR POSTINGAN DISKUSI -->
        <div class="space-y-4">
            @forelse($posts as $post)
                <article class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800">{{ $post->user->name }}</span>
                            @if($post->user->isAdmin())
                                <span
                                    class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">Admin</span>
                            @elseif($community->isModerator($post->user))
                                <span
                                    class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">Moderator</span>
                            @endif
                            <span class="text-[11px] text-slate-400">· {{ $post->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <h2 class="text-base font-bold text-slate-900 mb-1.5">{{ $post->title }}</h2>
                    <p class="text-xs text-slate-600 leading-relaxed mb-3">{{ $post->content }}</p>

                    <div class="flex items-center gap-4 pt-3 border-t border-slate-100 text-xs text-slate-500 font-medium">
                        <span>▲ {{ $post->score }} vote</span>
                        <span>💬 {{ $post->comments->count() }} Balasan</span>
                    </div>
                </article>
            @empty
                <div class="bg-white rounded-3xl p-10 text-center text-slate-400 text-xs">
                    Belum ada diskusi dalam komunitas ini.
                </div>
            @endforelse

            <div>
                {{ $posts->links() }}
            </div>
        </div>

    </div>
</x-app-layout>