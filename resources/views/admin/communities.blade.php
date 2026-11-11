<x-app-layout>
    <x-slot name="title">Manajemen Komunitas</x-slot>

    {{-- ═══ HEADER ══════════════════════════════════════════════════════════════ --}}
    <div class="mb-5">
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 transition-colors">Dashboard</a>
            <span>›</span>
            <span class="text-slate-600 font-medium">Komunitas</span>
        </div>
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Manajemen Komunitas</h1>
                <p class="text-xs text-slate-500 mt-0.5">Pantau dan moderasi semua ruang diskusi di CoHub.</p>
            </div>
        </div>
    </div>

    {{-- ═══ SEARCH ═════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 mb-4">
        <form method="GET" action="{{ route('admin.communities.index') }}" class="flex items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama komunitas..."
                       class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors">
                Cari
            </button>
        </form>
    </div>

    {{-- ═══ TABEL KOMUNITAS ═════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden" x-data="communityManager()">

        {{-- Flash message --}}
        <div x-show="flashMsg" x-transition
             class="px-4 py-2.5 bg-rose-50 border-b border-rose-100 flex items-center gap-2 text-xs text-rose-700 font-medium"
             style="display:none">
            <span>✓</span> <span x-text="flashMsg"></span>
        </div>

        {{-- Confirm Delete Modal --}}
        <div x-show="confirmOpen"
             class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
             style="display:none">
            <div @click.away="confirmOpen = false"
                 class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 flex items-center justify-center text-2xl mx-auto mb-3">🗑️</div>
                    <h3 class="text-sm font-bold text-slate-800 mb-1">Hapus Komunitas?</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Komunitas <span class="font-bold text-rose-600" x-text="'c/' + targetName"></span> dan semua postingannya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center gap-3 justify-center">
                        <button @click="confirmOpen = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <button @click="executeDelete()"
                                class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors">
                            Ya, Hapus Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Komunitas</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Pembuat</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Postingan</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Dibuat</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($communities as $community)
                        <tr class="hover:bg-slate-50/50 transition-colors" id="community-row-{{ $community->id }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    @php
                                        $colors = ['bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-purple-100 text-purple-700', 'bg-amber-100 text-amber-700'];
                                        $color = $colors[$community->id % count($colors)];
                                    @endphp
                                    <div class="w-9 h-9 rounded-xl {{ $color }} flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($community->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">c/{{ $community->name }}</p>
                                        @if($community->description)
                                            <p class="text-[10px] text-slate-400 truncate max-w-48">{{ Str::limit($community->description, 60) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="text-slate-600">{{ $community->creator?->name ?? 'Tidak diketahui' }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-mono font-semibold text-slate-700">{{ $community->posts_count }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-slate-500">{{ $community->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button"
                                        @click="confirmDelete({{ $community->id }}, '{{ addslashes($community->name) }}')"
                                        class="px-2.5 py-1 text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors">
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">
                                Tidak ada komunitas yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($communities->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $communities->links() }}
            </div>
        @endif

    </div>

    <script>
        function communityManager() {
            return {
                confirmOpen: false,
                targetId: null,
                targetName: '',
                flashMsg: '',
                flashTimeout: null,

                confirmDelete(id, name) {
                    this.targetId = id;
                    this.targetName = name;
                    this.confirmOpen = true;
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

                        if (data.success) {
                            // Hapus baris dari tabel dengan animasi
                            const row = document.getElementById(`community-row-${id}`);
                            if (row) {
                                row.style.transition = 'opacity 0.3s, transform 0.3s';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-8px)';
                                setTimeout(() => row.remove(), 300);
                            }
                            this.showFlash(data.message);
                        }
                    } catch (err) {
                        console.error(err);
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
