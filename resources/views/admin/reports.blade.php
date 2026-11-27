<x-app-layout>
    <x-slot name="title">Laporan — Dashboard Admin</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    @include('admin.partials.header')

    <div class="space-y-4" x-data="reportManager()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
             class="px-4 py-2.5 rounded-2xl border flex items-center gap-2 text-xs font-medium shadow-2xs"
             style="display: none;">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span>
            <span x-text="flashMsg"></span>
        </div>

        @php
            $statusFilter = $statusFilter ?? 'all';
            $typeFilter = $typeFilter ?? 'all';
            $statusTabs = [
                'all' => 'Semua Status',
                'new' => 'Baru',
                'reviewed' => 'Ditinjau',
                'resolved' => 'Selesai',
                'dismissed' => 'Diabaikan',
            ];
        @endphp

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="bg-slate-100/80 p-1 rounded-xl flex flex-wrap items-center gap-1">
                @foreach($statusTabs as $statusKey => $statusLabel)
                    <a href="{{ route('admin.reports.index', array_filter(['status' => $statusKey === 'all' ? null : $statusKey, 'type' => $typeFilter === 'all' ? null : $typeFilter])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $statusFilter === $statusKey ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $statusLabel }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
                @if($statusFilter !== 'all')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <select name="type"
                        onchange="this.form.submit()"
                        aria-label="Filter jenis target laporan"
                        class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>Semua Jenis</option>
                    <option value="post" {{ $typeFilter === 'post' ? 'selected' : '' }}>Postingan</option>
                    <option value="comment" {{ $typeFilter === 'comment' ? 'selected' : '' }}>Komentar</option>
                    <option value="user" {{ $typeFilter === 'user' ? 'selected' : '' }}>Pengguna</option>
                    <option value="community" {{ $typeFilter === 'community' ? 'selected' : '' }}>Komunitas</option>
                </select>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs overflow-hidden">
            @forelse($reports as $report)
                @php
                    $statusClasses = match ($report->status) {
                        'new' => 'bg-rose-50 text-rose-600',
                        'reviewed' => 'bg-amber-50 text-amber-700',
                        'resolved' => 'bg-emerald-50 text-emerald-700',
                        'dismissed' => 'bg-slate-100 text-slate-600',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp

                <div class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors" id="report-row-{{ $report->id }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                {{ $report->type_label }}
                            </span>
                            <span id="report-status-{{ $report->id }}" class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClasses }}">
                                {{ $report->status_label }}
                            </span>
                            <span class="text-[11px] text-slate-400" title="{{ $report->reported_at_formatted ?? '' }}">
                                {{ $report->reported_at_human ?? $report->created_at?->diffForHumans() }} · {{ $report->reported_at_formatted ?? $report->created_at?->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <p class="text-sm font-bold text-slate-900 truncate">{{ $report->display_title }}</p>
                        <p class="text-xs text-slate-600 mt-0.5 line-clamp-2">
                            <span class="font-semibold text-slate-700">Alasan:</span> {{ $report->reason }}
                        </p>
                        <p class="text-[11px] text-slate-400 truncate mt-1">
                            Pelapor: <span class="font-medium text-slate-600">{{ $report->reporter_display ?? ($report->reporter?->name ?? 'Sistem') }}</span> · {{ $report->display_meta }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                @click="openReview({
                                    id: {{ $report->id }},
                                    typeLabel: @js($report->type_label),
                                    title: @js($report->display_title),
                                    meta: @js($report->display_meta),
                                    reason: @js($report->reason),
                                    reporter: @js($report->reporter_display ?? ($report->reporter?->name ?? 'Sistem')),
                                    reportedAt: @js(($report->reported_at_formatted ?? '-') . ' (' . ($report->reported_at_human ?? '-') . ')'),
                                    targetUrl: @js($report->target_url ?? ''),
                                    status: @js($report->status)
                                })"
                                class="px-3.5 py-1 rounded-full text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 transition-colors cursor-pointer">
                            Tinjau
                        </button>

                        <button type="button"
                                @click="openDelete({
                                    id: {{ $report->id }},
                                    title: @js($report->display_title),
                                    typeLabel: @js($report->type_label),
                                    canDeleteTarget: {{ $report->can_delete_target ? 'true' : 'false' }}
                                })"
                                class="px-3.5 py-1 rounded-full text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 transition-colors cursor-pointer">
                            Hapus
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-400">
                    Tidak ada laporan yang sesuai dengan filter saat ini.
                </div>
            @endforelse
        </div>

        @if($reports->hasPages())
            <div class="pt-1">
                {{ $reports->links() }}
            </div>
        @endif

        <div x-show="reviewModalOpen"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="reviewModalOpen = false"
                 class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600" x-text="selectedReport.typeLabel"></span>
                    <span class="text-xs text-slate-500" x-text="'Pelapor: ' + selectedReport.reporter"></span>
                </div>
                <p class="text-[11px] text-slate-400 mb-3" x-text="'Dilaporkan: ' + selectedReport.reportedAt"></p>

                <h3 class="text-base font-extrabold text-slate-900" x-text="selectedReport.title"></h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed" x-text="selectedReport.meta"></p>

                <div class="mt-3 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Alasan Laporan</p>
                    <p class="text-xs text-slate-700 mt-1 leading-relaxed" x-text="selectedReport.reason"></p>
                </div>

                <template x-if="selectedReport.targetUrl">
                    <a :href="selectedReport.targetUrl" target="_blank"
                       class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-blue-600 hover:underline">
                        Buka halaman target →
                    </a>
                </template>

                <div class="flex items-center justify-end gap-2 mt-6 flex-wrap">
                    <button type="button"
                            @click="reviewModalOpen = false"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Tutup
                    </button>
                    <button type="button"
                            @click="submitReview('dismissed')"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Abaikan
                    </button>
                    <button type="button"
                            @click="submitReview('reviewed')"
                            class="px-3.5 py-2 text-xs font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-xl transition-colors cursor-pointer">
                        Tandai Ditinjau
                    </button>
                    <button type="button"
                            @click="submitReview('resolved')"
                            class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors cursor-pointer">
                        Tandai Selesai
                    </button>
                </div>
            </div>
        </div>

        <div x-show="deleteModalOpen"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="deleteModalOpen = false"
                 class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900" x-text="'Hapus laporan ' + deleteTarget.title + '?'"></h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Laporan ini akan dihapus dari daftar tinjauan. Anda juga dapat langsung menindak konten atau akun terkait.
                </p>

                <template x-if="deleteTarget.canDeleteTarget">
                    <label class="flex items-center gap-2.5 mt-4 cursor-pointer select-none">
                        <input type="checkbox"
                               x-model="deleteAlsoTarget"
                               class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-medium text-slate-700">Hapus / blokir juga target yang dilaporkan</span>
                    </label>
                </template>

                <div class="flex items-center justify-end gap-2.5 mt-6">
                    <button type="button"
                            @click="deleteModalOpen = false"
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
        function reportManager() {
            return {
                reviewModalOpen: false,
                deleteModalOpen: false,
                selectedReport: {},
                deleteTarget: {},
                deleteAlsoTarget: false,
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,

                openReview(report) {
                    this.selectedReport = report;
                    this.reviewModalOpen = true;
                },

                openDelete(target) {
                    this.deleteTarget = target;
                    this.deleteAlsoTarget = false;
                    this.deleteModalOpen = true;
                },

                async submitReview(status) {
                    const id = this.selectedReport.id;
                    this.reviewModalOpen = false;

                    try {
                        const res = await fetch(`/admin/reports/${id}/review`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ status }),
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            const badge = document.getElementById(`report-status-${id}`);
                            if (badge) {
                                const badgeClasses = {
                                    resolved: 'bg-emerald-50 text-emerald-700',
                                    reviewed: 'bg-amber-50 text-amber-700',
                                    dismissed: 'bg-slate-100 text-slate-600',
                                    new: 'bg-rose-50 text-rose-600',
                                };
                                badge.innerText = data.status_label;
                                badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold ' + (badgeClasses[status] || 'bg-slate-100 text-slate-600');
                            }
                            this.showFlash(data.message, 'success');
                        } else {
                            this.showFlash(data.message || 'Gagal memperbarui laporan.', 'error');
                        }
                    } catch (err) {
                        this.showFlash('Terjadi kesalahan jaringan.', 'error');
                    }
                },

                async executeDelete() {
                    const id = this.deleteTarget.id;
                    this.deleteModalOpen = false;

                    try {
                        const res = await fetch(`/admin/reports/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ delete_target: this.deleteAlsoTarget }),
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            const row = document.getElementById(`report-row-${id}`);
                            if (row) {
                                row.style.transition = 'opacity 0.25s';
                                row.style.opacity = '0';
                                setTimeout(() => row.remove(), 250);
                            }
                            this.showFlash(data.message, 'success');
                        } else {
                            this.showFlash(data.message || 'Gagal menghapus laporan.', 'error');
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
