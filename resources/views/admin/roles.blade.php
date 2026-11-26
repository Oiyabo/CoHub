<x-app-layout>
    <x-slot name="title">Hak Akses — Dashboard Admin</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    @include('admin.partials.header')

    <div class="space-y-4" x-data="roleManager()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
             class="px-4 py-2.5 rounded-2xl border flex items-center gap-2 text-xs font-medium shadow-2xs"
             style="display: none;">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span>
            <span x-text="flashMsg"></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-amber-100/75 rounded-2xl p-4">
                <p class="text-xs font-extrabold text-amber-800 uppercase tracking-wide">1. ADMIN</p>
                <p class="text-xs text-slate-600 mt-1">Kontrol global &amp; moderasi</p>
            </div>

            <div class="bg-purple-100/70 rounded-2xl p-4">
                <p class="text-xs font-extrabold text-purple-800 uppercase tracking-wide">2. MODERATOR</p>
                <p class="text-xs text-slate-600 mt-1">Terisolasi per komunitas</p>
            </div>

            <div class="bg-emerald-100/70 rounded-2xl p-4">
                <p class="text-xs font-extrabold text-emerald-800 uppercase tracking-wide">3. USER</p>
                <p class="text-xs text-slate-600 mt-1">Posting, vote, &amp; balas</p>
            </div>

            <div class="bg-slate-200/75 rounded-2xl p-4">
                <p class="text-xs font-extrabold text-slate-700 uppercase tracking-wide">4. GUEST</p>
                <p class="text-xs text-slate-600 mt-1">Akses read-only publik</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs">
            @php
                $avatarThemes = [
                    'bg-blue-100 text-blue-700',
                    'bg-rose-100 text-rose-700',
                    'bg-purple-100 text-purple-700',
                    'bg-emerald-100 text-emerald-700',
                    'bg-amber-100 text-amber-800',
                ];
            @endphp

            @forelse($users as $user)
                @php
                    $theme = $avatarThemes[$loop->index % count($avatarThemes)];
                    $initials = strtoupper(substr($user->name, 0, 2));
                    $createdCount = $user->createdCommunities->count();
                    $modCount = $user->moderatedCommunities->count();

                    if ($createdCount > 0) {
                        $subtitle = 'pembuat c/' . $user->createdCommunities->first()->name;
                    } elseif ($modCount === 1) {
                        $subtitle = 'moderator c/' . $user->moderatedCommunities->first()->name;
                    } elseif ($modCount > 1) {
                        $subtitle = "moderator di {$modCount} komunitas";
                    } else {
                        $subtitle = 'bukan moderator';
                    }
                @endphp

                <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors" id="role-row-{{ $user->id }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full {{ $theme }} flex items-center justify-center font-bold text-xs shrink-0">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $subtitle }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0" x-data="{ open: false }" @click.outside="open = false">
                        <span id="role-badge-{{ $user->id }}">
                            @if($user->isAdmin())
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100/80 text-amber-800">Admin</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">User</span>
                            @endif
                        </span>

                        <div class="relative">
                            <button type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center gap-1 px-3.5 py-1 rounded-full text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 transition-colors cursor-pointer">
                                <span>Ubah</span>
                                <span class="text-[10px]">▾</span>
                            </button>

                            <div x-show="open"
                                 x-transition.opacity
                                 style="display: none;"
                                 class="absolute right-0 mt-1.5 w-44 bg-white rounded-xl border border-slate-200 shadow-lg py-1 z-30 text-xs">
                                @if($user->id === Auth::id())
                                    <div class="px-3 py-2 text-[11px] text-slate-400">
                                        Akun sendiri terlindungi
                                    </div>
                                @else
                                    <button type="button"
                                            @click="open = false; changeRole({{ $user->id }}, 'admin')"
                                            class="w-full text-left px-3 py-2 hover:bg-amber-50 text-slate-700 font-medium flex items-center justify-between cursor-pointer">
                                        <span>Jadikan Admin</span>
                                        <span class="text-[10px] text-amber-700 font-bold" x-show="roles[{{ $user->id }}] === 'admin' || ('{{ $user->role }}' === 'admin' && !roles[{{ $user->id }}])">✓</span>
                                    </button>
                                    <button type="button"
                                            @click="open = false; changeRole({{ $user->id }}, 'user')"
                                            class="w-full text-left px-3 py-2 hover:bg-slate-50 text-slate-700 font-medium flex items-center justify-between cursor-pointer">
                                        <span>Jadikan User</span>
                                        <span class="text-[10px] text-blue-600 font-bold" x-show="roles[{{ $user->id }}] === 'user' || ('{{ $user->role }}' === 'user' && !roles[{{ $user->id }}])">✓</span>
                                    </button>
                                @endif
                            </div>
                        </div>
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

    </div>

    <script>
        function roleManager() {
            return {
                roles: {},
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,

                async changeRole(userId, nextRole) {
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
                            this.roles[userId] = data.role;
                            const badge = document.getElementById(`role-badge-${userId}`);
                            if (badge) {
                                badge.innerHTML = data.role === 'admin'
                                    ? '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100/80 text-amber-800">Admin</span>'
                                    : '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">User</span>';
                            }
                            this.showFlash(data.message, 'success');
                        } else {
                            this.showFlash(data.message || 'Perubahan peran ditolak.', 'error');
                        }
                    } catch (err) {
                        this.showFlash('Terjadi kesalahan saat mengubah peran.', 'error');
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
