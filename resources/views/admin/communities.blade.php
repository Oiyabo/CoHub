<x-app-layout>
    <x-slot name="title">Komunitas — Dashboard Admin</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    @include('admin.partials.header')

    <div class="space-y-4" x-data="communityManager()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
             class="px-4 py-2.5 rounded-2xl border flex items-center gap-2 text-xs font-medium shadow-2xs"
             style="display: none;">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span>
            <span x-text="flashMsg"></span>
        </div>

        <form method="GET" action="{{ route('admin.communities.index') }}"
              class="bg-white rounded-2xl border border-slate-200/80 px-4 py-2.5 shadow-2xs flex items-center gap-2.5">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
            </svg>
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Cari komunitas..."
                   class="w-full border-0 bg-transparent p-0 text-xs text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none">
            @if($search)
                <a href="{{ route('admin.communities.index') }}" class="text-[11px] font-semibold text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
            @endif
        </form>

        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs overflow-hidden">
            @php
                $badgeThemes = [
                    'bg-blue-100 text-blue-700',
                    'bg-emerald-100 text-emerald-700',
                    'bg-amber-100 text-amber-800',
                    'bg-purple-100 text-purple-700',
                    'bg-rose-100 text-rose-700',
                ];
            @endphp

            @forelse($communities as $community)
                @php
                    $theme = $badgeThemes[$loop->index % count($badgeThemes)];
                    $openReportCount = (int) ($openReportsByCommunity[$community->id] ?? 0);
                @endphp

                <div class="px-5 py-4 flex items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors" id="community-row-{{ $community->id }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl {{ $theme }} flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden">
                            @if($community->avatar_path)
                                <img src="{{ $community->avatar_url }}" alt="{{ $community->name }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($community->name, 0, 2)) }}
                            @endif
                        </div>

                        <div class="min-w-0">
                            <a href="{{ route('communities.show', $community->name) }}"
                               class="text-sm font-bold text-slate-900 hover:text-blue-600 transition-colors truncate block">
                                c/{{ $community->name }}
                            </a>
                            <p class="text-xs text-slate-400 truncate mt-0.5">
                                oleh {{ $community->creator?->name ?? 'Admin' }} · {{ number_format($community->members_count) }} anggota
                                @if($openReportCount > 0)
                                    · {{ $openReportCount }} laporan
                                @else
                                    · {{ number_format($community->posts_count) }} post
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                        <span id="community-status-{{ $community->id }}">
                            @if($community->isClosed())
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">Ditutup</span>
                            @elseif($openReportCount > 0)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">Bermasalah</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>
                            @endif
                        </span>

                        <button type="button"
                                id="close-btn-{{ $community->id }}"
                                @click="toggleClose({{ $community->id }}, {{ $openReportCount }})"
                                class="{{ $community->isClosed()
                                    ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-50 hover:bg-amber-100 text-amber-700' }} px-3.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer">
                            {{ $community->isClosed() ? 'Buka' : 'Tutup' }}
                        </button>

                        <button type="button"
                                @click="confirmDelete({{ $community->id }}, '{{ addslashes($community->name) }}')"
                                class="px-3.5 py-1 rounded-full text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 transition-colors cursor-pointer">
                            Hapus
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-400">
                    Tidak ada komunitas yang ditemukan.
                </div>
            @endforelse
        </div>

        @if($communities->hasPages())
            <div class="pt-1">
                {{ $communities->links() }}
            </div>
        @endif

        <div x-show="confirmOpen"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="confirmOpen = false"
                 class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900" x-text="'Hapus c/' + targetName + '?'"></h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Seluruh postingan dan komentar di komunitas ini ikut terhapus permanen. Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex items-center justify-end gap-2.5 mt-6">
                    <button type="button"
                            @click="confirmOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="executeDelete()"
                            class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors cursor-pointer">
                        Ya, hapus
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function communityManager() {
            return {
                confirmOpen: false,
                targetId: null,
                targetName: '',
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,

                confirmDelete(id, name) {
                    this.targetId = id;
                    this.targetName = name;
                    this.confirmOpen = true;
                },

                async toggleClose(id, openReportCount = 0) {
                    const btn = document.getElementById(`close-btn-${id}`);
                    if (!btn) return;
                    const originalText = btn.innerText;
                    btn.disabled = true;
                    btn.innerText = '...';

                    try {
                        const response = await fetch(`/admin/communities/${id}/toggle-close`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            }
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            const statusEl = document.getElementById(`community-status-${id}`);
                            if (statusEl) {
                                if (data.is_closed) {
                                    statusEl.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">Ditutup</span>';
                                } else if (openReportCount > 0) {
                                    statusEl.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">Bermasalah</span>';
                                } else {
                                    statusEl.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>';
                                }
                            }

                            if (data.is_closed) {
                                btn.innerText = 'Buka';
                                btn.className = 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-3.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer';
                            } else {
                                btn.innerText = 'Tutup';
                                btn.className = 'bg-amber-50 hover:bg-amber-100 text-amber-700 px-3.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer';
                            }

                            this.showFlash(data.message, 'success');
                        } else {
                            btn.innerText = originalText;
                            this.showFlash(data.message || 'Gagal mengubah status komunitas.', 'error');
                        }
                    } catch (err) {
                        btn.innerText = originalText;
                        this.showFlash('Terjadi kesalahan jaringan.', 'error');
                    } finally {
                        btn.disabled = false;
                    }
                },

                async executeDelete() {
                    this.confirmOpen = false;
                    const id = this.targetId;

                    try {
                        const response = await fetch(`/admin/communities/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            }
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            const row = document.getElementById(`community-row-${id}`);
                            if (row) {
                                row.style.transition = 'opacity 0.25s';
                                row.style.opacity = '0';
                                setTimeout(() => row.remove(), 250);
                            }
                            this.showFlash(data.message, 'success');
                        }
                    } catch (err) {
                        this.showFlash('Terjadi kesalahan jaringan.', 'error');
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
