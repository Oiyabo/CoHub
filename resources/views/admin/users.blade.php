<x-app-layout>
    <x-slot name="title">Pengguna — Dashboard Admin</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    @include('admin.partials.header')

    <div class="space-y-4" x-data="userManager()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
             class="px-4 py-2.5 rounded-2xl border flex items-center gap-2 text-xs font-medium shadow-2xs"
             style="display: none;">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span>
            <span x-text="flashMsg"></span>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}"
              class="bg-white rounded-2xl border border-slate-200/80 px-4 py-2.5 shadow-2xs flex items-center gap-2.5">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
            </svg>
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Cari pengguna..."
                   class="w-full border-0 bg-transparent p-0 text-xs text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none">
            @if($search)
                <a href="{{ route('admin.users.index') }}" class="text-[11px] font-semibold text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
            @endif
        </form>

        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs overflow-hidden">
            @php
                $avatarThemes = [
                    'bg-blue-100 text-blue-700',
                    'bg-rose-100 text-rose-700',
                    'bg-emerald-100 text-emerald-700',
                    'bg-purple-100 text-purple-700',
                    'bg-amber-100 text-amber-800',
                ];
            @endphp

            @forelse($users as $user)
                @php
                    $theme = $avatarThemes[$loop->index % count($avatarThemes)];
                    $handle = $user->username ?: Str::slug($user->name, '');
                    $initials = strtoupper(substr($user->name, 0, 2));
                @endphp

                <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors" id="user-row-{{ $user->id }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full {{ $theme }} flex items-center justify-center font-bold text-xs shrink-0">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ '@' . $handle }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                        @if($user->isAdmin())
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100/80 text-amber-800">Admin</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">User</span>
                        @endif

                        <span id="status-badge-{{ $user->id }}">
                            @if($user->isBlocked())
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">Diblokir</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>
                            @endif
                        </span>

                        @if($user->id === Auth::id() || $user->isAdmin())
                            <button type="button"
                                    @click="showFlash('{{ $user->id === Auth::id() ? 'Anda tidak dapat memblokir akun sendiri.' : 'Akun Admin tidak dapat diblokir.' }}', 'error')"
                                    class="px-3.5 py-1 rounded-full text-xs font-semibold bg-rose-50/60 text-rose-400 cursor-not-allowed">
                                Blokir
                            </button>
                        @else
                            <button type="button"
                                    id="block-btn-{{ $user->id }}"
                                    data-blocked="{{ $user->isBlocked() ? '1' : '0' }}"
                                    @click="handleAction({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($handle) }}', $el)"
                                    class="{{ $user->isBlocked()
                                        ? 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-800'
                                        : 'bg-rose-50 hover:bg-rose-100 text-rose-700' }} px-3.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer">
                                {{ $user->isBlocked() ? 'Pulihkan' : 'Blokir' }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-400">
                    Tidak ada pengguna yang ditemukan.
                </div>
            @endforelse
        </div>

        @if($users->hasPages())
            <div class="pt-1">
                {{ $users->links() }}
            </div>
        @endif

        <div x-show="blockModalOpen"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="blockModalOpen = false"
                 class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900" x-text="'Blokir @' + targetHandle + '?'"></h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    <span x-text="targetName"></span> tidak akan bisa login dan memposting. Admin dapat memulihkannya kapan saja.
                </p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Alasan</label>
                        <input type="text"
                               x-model="blockReason"
                               placeholder="Menyebarkan spam di beberapa komunitas"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-colors">
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox"
                               x-model="hidePosts"
                               class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-medium text-slate-700">Sembunyikan semua postingannya</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5 mt-6">
                    <button type="button"
                            @click="blockModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="confirmBlock()"
                            :disabled="submitting"
                            class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 rounded-xl transition-colors cursor-pointer">
                        Blokir
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function userManager() {
            return {
                blockModalOpen: false,
                targetId: null,
                targetName: '',
                targetHandle: '',
                blockReason: '',
                hidePosts: true,
                submitting: false,
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,

                handleAction(userId, userName, userHandle, btnEl) {
                    const isBlocked = btnEl.dataset.blocked === '1';
                    if (isBlocked) {
                        this.submitToggle(userId, {}, btnEl);
                        return;
                    }
                    this.targetId = userId;
                    this.targetName = userName;
                    this.targetHandle = userHandle;
                    this.blockReason = '';
                    this.hidePosts = true;
                    this.blockModalOpen = true;
                },

                async confirmBlock() {
                    const btn = document.getElementById(`block-btn-${this.targetId}`);
                    await this.submitToggle(this.targetId, {
                        reason: this.blockReason,
                        hide_posts: this.hidePosts,
                    }, btn);
                    this.blockModalOpen = false;
                },

                async submitToggle(userId, payload, btn) {
                    if (this.submitting) return;
                    this.submitting = true;
                    const originalText = btn ? btn.innerText : '';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerText = '...';
                    }

                    try {
                        const response = await fetch(`/admin/users/${userId}/toggle-block`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            const badge = document.getElementById(`status-badge-${userId}`);
                            if (badge) {
                                badge.innerHTML = data.is_blocked
                                    ? '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">Diblokir</span>'
                                    : '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>';
                            }

                            if (btn) {
                                btn.dataset.blocked = data.is_blocked ? '1' : '0';
                                if (data.is_blocked) {
                                    btn.innerText = 'Pulihkan';
                                    btn.className = 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 px-3.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer';
                                } else {
                                    btn.innerText = 'Blokir';
                                    btn.className = 'bg-rose-50 hover:bg-rose-100 text-rose-700 px-3.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer';
                                }
                            }

                            this.showFlash(data.message, 'success');
                        } else {
                            if (btn) btn.innerText = originalText;
                            this.showFlash(data.message || 'Gagal memproses permintaan.', 'error');
                        }
                    } catch (err) {
                        if (btn) btn.innerText = originalText;
                        this.showFlash('Terjadi kesalahan jaringan.', 'error');
                    } finally {
                        if (btn) btn.disabled = false;
                        this.submitting = false;
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
