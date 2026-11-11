<x-app-layout>
    <x-slot name="title">Dashboard Admin</x-slot>

    {{-- ═══ HEADER ══════════════════════════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                    Dashboard Admin
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Selamat datang, <span class="font-semibold text-amber-600">{{ Auth::user()->name }}</span>. Pantau aktivitas CoHub secara real-time.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-xs">
                    👥 Kelola Pengguna
                </a>
                <a href="{{ route('admin.communities.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors shadow-xs">
                    🏘️ Kelola Komunitas
                </a>
            </div>
        </div>
    </div>

    {{-- ═══ STAT CARDS ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">

        {{-- Total Pengguna --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Pengguna</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($stats['total_users']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">{{ $stats['admin_count'] }} admin · {{ $stats['blocked_count'] }} diblokir</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-xl shrink-0">👥</div>
            </div>
        </div>

        {{-- Total Post --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Postingan</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($stats['total_posts']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Di semua komunitas</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-xl shrink-0">📝</div>
            </div>
        </div>

        {{-- Total Komentar --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Komentar</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($stats['total_comments']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Termasuk balasan bersarang</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-xl shrink-0">💬</div>
            </div>
        </div>

        {{-- Total Komunitas --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Komunitas</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($stats['total_communities']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Ruang diskusi aktif</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-xl shrink-0">🏘️</div>
            </div>
        </div>

        {{-- Pengguna Diblokir --}}
        <div class="bg-white rounded-2xl p-4 border border-rose-100 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-rose-400 uppercase tracking-wider">Pengguna Diblokir</p>
                    <p class="text-3xl font-black text-rose-600 mt-1 leading-none">{{ number_format($stats['blocked_count']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Akun yang dinonaktifkan</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-xl shrink-0">🚫</div>
            </div>
        </div>

        {{-- Admin --}}
        <div class="bg-white rounded-2xl p-4 border border-amber-100 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">Total Admin</p>
                    <p class="text-3xl font-black text-amber-700 mt-1 leading-none">{{ number_format($stats['admin_count']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Akses penuh platform</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-xl shrink-0">⚡</div>
            </div>
        </div>

    </div>

    {{-- ═══ GRAFIK AKTIVITAS 7 HARI ════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 mb-5">
        <h2 class="text-sm font-bold text-slate-800 mb-4">📊 Aktivitas 7 Hari Terakhir</h2>
        <div class="flex items-end gap-3 h-32" id="activity-chart">
            @foreach($activityData as $day)
                @php
                    $maxActivity = collect($activityData)->max(fn($d) => $d['posts'] + $d['comments']);
                    $total = $day['posts'] + $day['comments'];
                    $heightPercent = $maxActivity > 0 ? ($total / $maxActivity) * 100 : 0;
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1 group">
                    <div class="relative w-full flex flex-col gap-0.5" style="height: 100px;">
                        {{-- Bar komentar --}}
                        <div class="w-full bg-purple-200 rounded-t absolute bottom-0 transition-all"
                             style="height: {{ $heightPercent * 0.4 }}%;"
                             title="{{ $day['comments'] }} komentar">
                        </div>
                        {{-- Bar post --}}
                        <div class="w-full bg-blue-500 rounded-t absolute bottom-0 transition-all"
                             style="height: {{ $heightPercent * 0.6 }}%; margin-bottom: {{ $heightPercent * 0.4 }}%"
                             title="{{ $day['posts'] }} post">
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">{{ $day['date'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex items-center gap-4 mt-3 text-[10px] text-slate-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-blue-500 inline-block"></span> Postingan</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-purple-200 inline-block"></span> Komentar</span>
        </div>
    </div>

    {{-- ═══ DUA KOLOM BAWAH ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Pengguna Terbaru --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-800">👤 Pengguna Terbaru</h2>
                <a href="{{ route('admin.users.index') }}" class="text-[11px] text-blue-600 hover:underline">Lihat semua →</a>
            </div>
            <div class="space-y-2.5">
                @foreach($recentUsers as $user)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl {{ $user->isAdmin() ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center font-bold text-xs shrink-0">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ $user->name }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ $user->email }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($user->isAdmin())
                                <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-lg border border-amber-200">Admin</span>
                            @elseif($user->is_blocked)
                                <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-lg border border-rose-200">Diblokir</span>
                            @else
                                <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-lg border border-emerald-200">Aktif</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Posts --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-800">🏆 Postingan Terpopuler</h2>
            </div>
            <div class="space-y-2.5">
                @forelse($topPosts as $i => $post)
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-lg {{ $i === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center font-black text-[10px] shrink-0 mt-0.5">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-800 line-clamp-2 leading-tight">{{ $post->title }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] text-blue-600 font-medium">c/{{ $post->community?->name }}</span>
                                <span class="text-[10px] text-slate-400">·</span>
                                <span class="text-[10px] text-emerald-600 font-semibold">▲ {{ $post->score }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada postingan.</p>
                @endforelse
            </div>
        </div>

    </div>

</x-app-layout>
