<x-app-layout>
    <x-slot name="sidebar">
        <!-- SIDEBAR DETAIL KOMUNITAS -->
        <div class="space-y-4">
            <!-- 1. Tentang Komunitas -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <h4 class="text-sm font-bold text-slate-800 mb-2">Tentang komunitas</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    @if(is_array($about))
                        {{ $about['content'] ?? $about['description'] ?? $community->description ?? 'Belum ada deskripsi untuk komunitas ini.' }}
                    @else
                        {{ $about ?? $community->description ?? 'Belum ada deskripsi untuk komunitas ini.' }}
                    @endif
                </p>

                @if(is_array($about) && (!empty($about['website']) || !empty($about['discord']) || !empty($about['github'])))
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 flex-wrap">
                        @if(!empty($about['website']))
                            <a href="{{ $about['website'] }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-[11px] font-semibold text-slate-700 transition">
                                🌐 Website
                            </a>
                        @endif
                        @if(!empty($about['discord']))
                            <a href="{{ $about['discord'] }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-[11px] font-semibold text-indigo-700 transition">
                                💬 Discord
                            </a>
                        @endif
                        @if(!empty($about['github']))
                            <a href="{{ $about['github'] }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-semibold text-slate-800 transition">
                                🔗 Medsos
                            </a>
                        @endif
                    </div>
                @endif

                <div class="mt-3.5 pt-3 border-t border-slate-100 text-[11px] text-slate-500 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Pembuat:</span>
                        <span
                            class="font-semibold text-slate-700">{{ $community->creator ? $community->creator->name : 'Administrator' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Dibuat:</span>
                        <span
                            class="font-semibold text-slate-700">{{ $community->created_at ? $community->created_at->format('d M Y') : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Aturan Komunitas -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-slate-800">Aturan</h4>
                    @can('update', $community)
                        <a href="{{ route('communities.edit', $community->name) }}?tab=aturan"
                            class="text-[11px] font-semibold text-blue-600 hover:underline">
                            Kelola
                        </a>
                    @endcan
                </div>

                <div class="space-y-3 text-xs">
                    @forelse($rules as $index => $rule)
                        @php
                            $colorStyles = [
                                ['bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
                                ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
                                ['bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
                                ['bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
                            ];
                            $color = $colorStyles[$index % count($colorStyles)];
                            $title = is_array($rule) ? ($rule['title'] ?? '') : $rule;
                            $desc = is_array($rule) ? ($rule['description'] ?? null) : null;
                        @endphp
                        <div class="flex items-start gap-2.5">
                            <span
                                class="w-5 h-5 rounded-full {{ $color['bg'] }} {{ $color['text'] }} font-bold text-[11px] flex items-center justify-center shrink-0 mt-0.5">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="font-bold text-slate-800">{{ $title }}</p>
                                @if($desc)
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $desc }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada aturan khusus.</p>
                    @endforelse
                </div>
            </div>

            <!-- 3. Daftar Moderator -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-slate-800">Moderator ({{ $moderators->count() }})</h4>
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
                                class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-[11px] shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="text-xs min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $mod->name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">
                                    {{ '@' . ($mod->username ?? Str::slug($mod->name, '')) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada moderator.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-slot>

    <div x-data="{ currentTab: new URLSearchParams(window.location.search).get('tab') || 'diskusi' }" class="space-y-4">

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

            <div class="px-6 pb-6 pt-0 relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <!-- Floating Avatar Logo & Info -->
                    <div>
                        <div
                            class="relative z-10 w-16 h-16 rounded-2xl bg-blue-100 text-blue-700 font-black text-lg flex items-center justify-center -mt-8 border-4 border-white shadow-md overflow-hidden">
                            @if($community->avatar_path)
                                <img src="{{ $community->avatar_url }}" alt="{{ $community->name }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($community->name, 0, 2)) }}
                            @endif
                        </div>

                        <h1 class="text-2xl font-black text-slate-900 mt-3 tracking-tight">
                            c/{{ $community->name }}
                        </h1>

                        <!-- Badges Statistik -->
                        <div class="flex items-center gap-2 mt-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600">
                                {{ number_format($community->members_count ?? $members->count()) }} anggota
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
                        @auth
                            <button type="button"
                                @click="$dispatch('open-create-post', { communityId: '{{ $community->id }}' })"
                                class="px-4 py-2 rounded-full border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs flex items-center gap-1.5 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Buat Post</span>
                            </button>
                        @endauth

                        <button type="button"
                            @click="navigator.clipboard.writeText(window.location.href); alert('Tautan komunitas berhasil disalin!')"
                            class="px-4 py-2 rounded-full border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold text-xs transition cursor-pointer">
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
                            @elseif($isMember)
                                <form action="{{ route('communities.leave', $community->name) }}" method="POST"
                                    onsubmit="return confirm('Yakin ingin keluar dari komunitas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-5 py-2 rounded-full border border-slate-200 hover:border-rose-200 hover:bg-rose-50 text-slate-700 hover:text-rose-600 font-semibold text-xs shadow-xs transition cursor-pointer">
                                        Tergabung ✓
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('communities.join', $community->name) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                                        Gabung
                                    </button>
                                </form>
                            @endif
                        @else
                            <button type="button" @click="authModal = 'login'"
                                class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                                Gabung
                            </button>
                        @endauth
                    </div>
                </div>

                <!-- Sub-nav Tabs Interaktif -->
                <div
                    class="flex items-center gap-2 mt-5 pt-4 border-t border-slate-100 text-xs font-semibold select-none">
                    <button type="button" @click="currentTab = 'diskusi'"
                        :class="currentTab === 'diskusi' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-1.5 rounded-full transition-all cursor-pointer">
                        Diskusi ({{ $posts->total() }})
                    </button>
                    <button type="button" @click="currentTab = 'tentang'"
                        :class="currentTab === 'tentang' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-1.5 rounded-full transition-all cursor-pointer">
                        Tentang
                    </button>
                    <button type="button" @click="currentTab = 'anggota'"
                        :class="currentTab === 'anggota' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-1.5 rounded-full transition-all cursor-pointer">
                        Anggota ({{ $community->members_count ?? $members->count() }})
                    </button>
                </div>
            </div>
        </div>

        <!-- BANNER KHUSUS MODERATOR -->
        @auth
            @if($community->isModerator(Auth::user()) || Auth::user()->isAdmin())
                <div
                    class="bg-purple-50/70 border border-purple-200/80 rounded-2xl p-3.5 flex items-center gap-2.5 text-xs text-purple-800 font-medium shadow-2xs">
                    <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    <span>Kamu moderator di komunitas ini. Pengaturan & moderasi dapat diakses melalui tombol Ruang
                        Moderasi.</span>
                </div>
            @endif
        @endauth

        <!-- ================= TAB 1: DISKUSI ================= -->
        <div x-show="currentTab === 'diskusi'" class="space-y-4">

            <!-- KOTAK INPUT CEPAT BUAT POST DI KOMUNITAS INI -->
            @auth
                <div class="bg-white rounded-3xl p-4 border border-slate-100 shadow-xs flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                    </div>
                    <button type="button"
                        @click="$dispatch('open-create-post', { communityId: '{{ $community->id }}' })"
                        class="flex-1 bg-slate-50 hover:bg-slate-100 text-left text-xs text-slate-400 px-4 py-2.5 rounded-xl border border-slate-200 transition-colors cursor-pointer">
                        Mulai obrolan di c/{{ $community->name }}...
                    </button>
                    <button type="button"
                        @click="$dispatch('open-create-post', { communityId: '{{ $community->id }}' })"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Buat Post</span>
                    </button>
                </div>
            @endauth

            @forelse($posts as $post)
                <article
                    class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs hover:border-slate-200 transition-all">
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

                    <h2 class="text-base font-bold text-slate-900 mb-1.5">
                        <a href="{{ route('posts.show', $post) }}" class="hover:text-blue-600 transition-colors">
                            {{ $post->title }}
                        </a>
                    </h2>
                    <p class="text-xs text-slate-600 leading-relaxed mb-3 line-clamp-3">{{ $post->content }}</p>

                    @if($post->image_path)
                        <div class="mb-3 rounded-2xl overflow-hidden max-h-96 bg-slate-100 border border-slate-100">
                            <img src="{{ Storage::url($post->image_path) }}" alt="{{ $post->title }}"
                                class="w-full object-cover">
                        </div>
                    @endif

                    <div class="flex items-center gap-4 pt-3 border-t border-slate-100 text-xs text-slate-500 font-medium">
                        <span class="flex items-center gap-1">
                            <span>▲</span>
                            <span>{{ $post->score }} vote</span>
                        </span>
                        <a href="{{ route('posts.show', $post) }}"
                            class="hover:text-blue-600 transition-colors flex items-center gap-1">
                            <span>💬</span>
                            <span>{{ $post->comments->count() }} Balasan</span>
                        </a>
                    </div>
                </article>
            @empty
                <div class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-xs">
                    <div
                        class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-lg font-bold">
                        💬
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Belum ada diskusi</h3>
                    <p class="text-xs text-slate-500 mt-1">Jadilah yang pertama memulai obrolan di c/{{ $community->name }}!
                    </p>

                    <div class="mt-4">
                        @auth
                            <button type="button"
                                @click="$dispatch('open-create-post', { communityId: '{{ $community->id }}' })"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Buat Post Sekarang</span>
                            </button>
                        @else
                            <button type="button" @click="authModal = 'login'"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer">
                                <span>Masuk untuk Membuat Post</span>
                            </button>
                        @endauth
                    </div>
                </div>
            @endforelse

            <div>
                {{ $posts->links() }}
            </div>
        </div>

        <!-- ================= TAB 2: TENTANG ================= -->
        <div x-show="currentTab === 'tentang'" style="display: none;" class="space-y-4">
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-2">Tentang c/{{ $community->name }}</h3>
                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                        @if(is_array($about))
                            {{ $about['content'] ?? $about['description'] ?? $community->description ?? 'Komunitas ini belum memiliki penjelasan detail.' }}
                        @else
                            {{ $about ?? $community->description ?? 'Komunitas ini belum memiliki penjelasan detail.' }}
                        @endif
                    </p>
                </div>

                @if(is_array($about) && !empty($about['guidelines']))
                    <div class="pt-5 border-t border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 mb-2">Pedoman & Nilai Komunitas</h3>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                            {{ $about['guidelines'] }}
                        </div>
                    </div>
                @endif

                @if(is_array($about) && (!empty($about['website']) || !empty($about['discord']) || !empty($about['github'])))
                    <div class="pt-5 border-t border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 mb-3">Tautan Resmi & Komunitas</h3>
                        <div class="flex items-center gap-3 flex-wrap">
                            @if(!empty($about['website']))
                                <a href="{{ $about['website'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs transition">
                                    <span>🌐</span>
                                    <span>Website Resmi</span>
                                </a>
                            @endif
                            @if(!empty($about['discord']))
                                <a href="{{ $about['discord'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition">
                                    <span>💬</span>
                                    <span>Server Discord</span>
                                </a>
                            @endif
                            @if(!empty($about['github']))
                                <a href="{{ $about['github'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs transition">
                                    <span>🔗</span>
                                    <span>GitHub / Medsos</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="pt-5 border-t border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Aturan Komunitas ({{ count($rules) }})</h3>
                    @if(count($rules) > 0)
                        <div class="space-y-3">
                            @foreach($rules as $idx => $r)
                                @php
                                    $title = is_array($r) ? ($r['title'] ?? '') : $r;
                                    $desc = is_array($r) ? ($r['description'] ?? null) : null;
                                @endphp
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100/80">
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-800">{{ $title }}</h4>
                                            @if($desc)
                                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $desc }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400">Komunitas ini belum menambahkan aturan khusus.</p>
                    @endif
                </div>

                <div class="pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100">
                        <p class="text-[11px] text-blue-600 font-semibold">Total Anggota</p>
                        <p class="text-lg font-black text-blue-900 mt-1">
                            {{ number_format($community->members_count ?? $members->count()) }}
                        </p>
                    </div>
                    <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100">
                        <p class="text-[11px] text-emerald-600 font-semibold">Diskusi Diterbitkan</p>
                        <p class="text-lg font-black text-emerald-900 mt-1">{{ number_format($posts->total()) }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100">
                        <p class="text-[11px] text-purple-600 font-semibold">Moderator Aktif</p>
                        <p class="text-lg font-black text-purple-900 mt-1">{{ number_format($moderators->count()) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= TAB 3: ANGGOTA ================= -->
        <div x-show="currentTab === 'anggota'" style="display: none;" class="space-y-4">
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Daftar Anggota</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Semua pengguna yang telah bergabung di komunitas
                            c/{{ $community->name }}.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600">
                        {{ number_format($community->members_count ?? $members->count()) }} Anggota
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($members as $member)
                        @php
                            $initial = strtoupper(substr($member->name, 0, 2));
                            $isMemberMod = $community->isModerator($member) || ($member->pivot && $member->pivot->role === 'moderator');
                            $isMemberOwner = $member->id === $community->created_by || ($member->pivot && $member->pivot->role === 'admin');

                            if ($isMemberOwner) {
                                $avatarBg = 'bg-amber-100 text-amber-800';
                                $roleBadge = 'bg-amber-50 text-amber-700 border border-amber-200';
                                $roleName = 'Pembuat Komunitas';
                            } elseif ($isMemberMod) {
                                $avatarBg = 'bg-purple-100 text-purple-700';
                                $roleBadge = 'bg-purple-50 text-purple-700 border border-purple-200';
                                $roleName = 'Moderator';
                            } else {
                                $avatarBg = 'bg-blue-100 text-blue-700';
                                $roleBadge = 'bg-slate-100 text-slate-600';
                                $roleName = 'Anggota';
                            }
                        @endphp
                        <div class="py-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-full {{ $avatarBg }} font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $initial }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ $member->name }}</p>
                                        @if($isMemberOwner)
                                            <span
                                                class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">Owner</span>
                                        @elseif($isMemberMod)
                                            <span
                                                class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200 shrink-0">Mod</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-400 truncate">
                                        {{ '@' . ($member->username ?? Str::slug($member->name, '')) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-[10px] text-slate-400 hidden sm:inline">
                                    {{ $member->pivot && $member->pivot->created_at ? 'Bergabung ' . $member->pivot->created_at->diffForHumans() : '' }}
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $roleBadge }}">
                                    {{ $roleName }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-slate-400 text-xs">
                            Belum ada anggota yang bergabung.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>