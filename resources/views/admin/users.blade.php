<x-app-layout>
    <x-slot name="title">Manajemen Pengguna &amp; Hak Akses</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-amber-900 rounded-2xl p-5 text-white shadow-xs mb-5">
        <div class="flex items-center gap-2 text-[11px] text-slate-300 mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-amber-400 transition-colors">Dashboard Admin</a>
            <span>›</span>
            <span class="text-amber-400 font-semibold">Pengguna &amp; Hak Akses</span>
        </div>
        <h1 class="text-xl font-extrabold tracking-tight">Manajemen Pengguna &amp; Hak Akses</h1>
        <p class="text-xs text-slate-300 mt-0.5">Cari pengguna, atur peran hak akses sesuai kewenangan, blokir, atau pulihkan akun.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-3 flex-wrap">
            <div class="relative flex-1 min-w-48">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama, username, atau email..."
                       class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
            </div>

            <div class="flex gap-1.5 flex-wrap">
                @foreach(['all' => 'Semua', 'user' => 'User', 'admin' => 'Admin', 'blocked' => 'Diblokir'] as $val => $label)
                    <button type="submit" name="filter" value="{{ $val }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-xl border transition-colors cursor-pointer
                                {{ $filter === $val
                                    ? 'bg-slate-900 text-white border-slate-900'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-amber-400 hover:text-slate-900' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden" x-data="userManager()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-100 text-rose-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700'"
             class="px-4 py-2.5 border-b flex items-center gap-2 text-xs font-medium"
             style="display:none">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span> <span x-text="flashMsg"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pengguna</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Peran / Hak Akses</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Post / Komentar</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Status</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Aksi Akun</th>
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
                            <td class="px-4 py-3">
                                @if($user->id === Auth::id())
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                        Admin (Akun Anda)
                                    </span>
                                @else
                                    <select id="role-select-{{ $user->id }}"
                                            data-current-role="{{ $user->role }}"
                                            @change="changeRole({{ $user->id }}, $event.target)"
                                            class="text-[11px] font-semibold rounded-lg border border-slate-200 bg-white pl-2.5 pr-7 py-1 text-slate-700 focus:border-amber-500 focus:ring-amber-500 cursor-pointer">
                                        <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-mono font-semibold text-slate-700">{{ $user->posts_count }}</span>
                                <span class="text-slate-400">/</span>
                                <span class="font-mono font-semibold text-slate-500">{{ $user->comments_count }}</span>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell" id="status-badge-{{ $user->id }}">
                                @if($user->is_blocked)
                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200">Diblokir</span>
                                @else
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($user->id === Auth::id())
                                    <span class="text-[10px] text-slate-400 italic">Akun kamu</span>
                                @elseif($user->isAdmin())
                                    <span id="admin-protected-{{ $user->id }}" class="text-[10px] text-slate-400 italic">Admin terlindungi</span>
                                @else
                                    <button type="button"
                                            @click="toggleBlock({{ $user->id }})"
                                            id="block-btn-{{ $user->id }}"
                                            class="{{ $user->is_blocked
                                                ? 'text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100'
                                                : 'text-rose-600 bg-rose-50 border-rose-200 hover:bg-rose-100' }}
                                                   px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors cursor-pointer">
                                        {{ $user->is_blocked ? '✓ Pulihkan' : '✕ Blokir' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">
                                Tidak ada pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif

    </div>

    <script>
        function userManager() {
            return {
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,

                async toggleBlock(userId) {
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
                            const badge = document.getElementById(`status-badge-${userId}`);
                            if (badge) {
                                badge.innerHTML = data.is_blocked
                                    ? '<span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200">Diblokir</span>'
                                    : '<span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">Aktif</span>';
                            }

                            if (data.is_blocked) {
                                btn.innerText = '✓ Pulihkan';
                                btn.className = 'text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100 px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors cursor-pointer';
                            } else {
                                btn.innerText = '✕ Blokir';
                                btn.className = 'text-rose-600 bg-rose-50 border-rose-200 hover:bg-rose-100 px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors cursor-pointer';
                            }

                            this.showFlash(data.message, 'success');
                        } else {
                            btn.innerText = originalText;
                            this.showFlash(data.message || 'Gagal memproses permintaan.', 'error');
                        }
                    } catch (err) {
                        btn.innerText = originalText;
                        this.showFlash('Terjadi kesalahan jaringan.', 'error');
                    } finally {
                        btn.disabled = false;
                    }
                },

                async changeRole(userId, selectEl) {
                    const prevRole = selectEl.dataset.currentRole;
                    const nextRole = selectEl.value;
                    selectEl.disabled = true;

                    try {
                        const response = await fetch(`/admin/users/${userId}/role`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ role: nextRole }),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            selectEl.dataset.currentRole = data.role;
                            this.showFlash(data.message, 'success');
                            setTimeout(() => window.location.reload(), 600);
                        } else {
                            selectEl.value = prevRole;
                            this.showFlash(data.message || 'Perubahan peran ditolak.', 'error');
                        }
                    } catch (err) {
                        selectEl.value = prevRole;
                        this.showFlash('Terjadi kesalahan saat mengubah peran.', 'error');
                    } finally {
                        selectEl.disabled = false;
                    }
                },

                showFlash(msg, type = 'success') {
                    this.flashMsg = msg;
                    this.flashType = type;
                    if (this.flashTimeout) clearTimeout(this.flashTimeout);
                    this.flashTimeout = setTimeout(() => { this.flashMsg = ''; }, 3500);
                }
            };
        }
    </script>

</x-app-layout>
