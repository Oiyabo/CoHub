<x-app-layout>
    <x-slot name="title">Manajemen Pengguna</x-slot>

    {{-- ═══ HEADER ══════════════════════════════════════════════════════════════ --}}
    <div class="mb-5">
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 transition-colors">Dashboard</a>
            <span>›</span>
            <span class="text-slate-600 font-medium">Pengguna</span>
        </div>
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Manajemen Pengguna</h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola akun, peran, dan status pengguna CoHub.</p>
            </div>
        </div>
    </div>

    {{-- ═══ FILTER & SEARCH ════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-3 flex-wrap">
            {{-- Search --}}
            <div class="relative flex-1 min-w-48">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama atau email..."
                       class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
            </div>

            {{-- Filter Tabs --}}
            <div class="flex gap-1.5">
                @foreach(['all' => 'Semua', 'admin' => 'Admin', 'blocked' => 'Diblokir'] as $val => $label)
                    <button type="submit" name="filter" value="{{ $val }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-xl border transition-colors
                                {{ $filter === $val
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300 hover:text-blue-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </form>
    </div>

    {{-- ═══ TABEL PENGGUNA ══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden" x-data="userManager()">

        {{-- Flash message --}}
        <div x-show="flashMsg" x-transition
             class="px-4 py-2.5 bg-emerald-50 border-b border-emerald-100 flex items-center gap-2 text-xs text-emerald-700 font-medium"
             style="display:none">
            <span>✓</span> <span x-text="flashMsg"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pengguna</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Peran</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Postingan</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Komentar</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Status</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors" id="user-row-{{ $user->id }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl {{ $user->isAdmin() ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $user->name }}</p>
                                        <p class="text-[10px] text-slate-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                @if($user->isAdmin())
                                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">Admin</span>
                                @else
                                    <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-lg">User</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-mono font-semibold text-slate-700">{{ $user->posts_count }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-mono font-semibold text-slate-700">{{ $user->comments_count }}</span>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell" id="status-badge-{{ $user->id }}">
                                @if($user->is_blocked)
                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200">Diblokir</span>
                                @else
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($user->isAdmin())
                                    <span class="text-[10px] text-slate-400 italic">Admin terlindungi</span>
                                @elseif($user->id === Auth::id())
                                    <span class="text-[10px] text-slate-400 italic">Akun kamu</span>
                                @else
                                    <button type="button"
                                            @click="toggleBlock({{ $user->id }}, '{{ $user->name }}', {{ $user->is_blocked ? 'true' : 'false' }})"
                                            id="block-btn-{{ $user->id }}"
                                            class="{{ $user->is_blocked
                                                ? 'text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100'
                                                : 'text-rose-600 bg-rose-50 border-rose-200 hover:bg-rose-100' }}
                                                   px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors">
                                        {{ $user->is_blocked ? '✓ Aktifkan' : '✕ Blokir' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                                Tidak ada pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif

    </div>

    {{-- ═══ ALPINE.JS LOGIC ════════════════════════════════════════════════════ --}}
    <script>
        function userManager() {
            return {
                flashMsg: '',
                flashTimeout: null,

                async toggleBlock(userId, userName, isBlocked) {
                    const btn = document.getElementById(`block-btn-${userId}`);
                    if (!btn) return;

                    const originalText = btn.innerText;
                    btn.disabled = true;
                    btn.innerText = '...';

                    try {
                        const response = await fetch(`/admin/users/${userId}/toggle-block`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Update badge status
                            const badge = document.getElementById(`status-badge-${userId}`);
                            if (badge) {
                                badge.innerHTML = data.is_blocked
                                    ? '<span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200">Diblokir</span>'
                                    : '<span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">Aktif</span>';
                            }

                            // Update tombol
                            if (data.is_blocked) {
                                btn.innerText = '✓ Aktifkan';
                                btn.className = 'text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100 px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors';
                                btn.onclick = () => this.toggleBlock(userId, userName, true);
                            } else {
                                btn.innerText = '✕ Blokir';
                                btn.className = 'text-rose-600 bg-rose-50 border-rose-200 hover:bg-rose-100 px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors';
                                btn.onclick = () => this.toggleBlock(userId, userName, false);
                            }

                            // Flash message
                            this.showFlash(data.message);
                        }
                    } catch (err) {
                        btn.innerText = originalText;
                        console.error(err);
                    } finally {
                        btn.disabled = false;
                    }
                },

                showFlash(msg) {
                    this.flashMsg = msg;
                    if (this.flashTimeout) clearTimeout(this.flashTimeout);
                    this.flashTimeout = setTimeout(() => { this.flashMsg = ''; }, 3500);
                }
            };
        }
    </script>

</x-app-layout>
