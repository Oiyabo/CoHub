<x-app-layout>
    <x-slot name="title">Laporan — Dashboard Admin</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    @include('admin.partials.header')

    @php
        $statusFilter = $statusFilter ?? 'all';
        $typeFilter = $typeFilter ?? 'all';
        $sort = $sort ?? 'latest';
        $search = $search ?? '';
        $statusCounts = $statusCounts ?? ['all' => 0, 'new' => 0, 'reviewed' => 0, 'resolved' => 0, 'dismissed' => 0];
        $typeCounts = $typeCounts ?? ['all' => 0, 'post' => 0, 'comment' => 0, 'user' => 0, 'community' => 0];

        $statusTabs = [
            'all' => 'Semua',
            'new' => 'Baru',
            'reviewed' => 'Ditinjau',
            'resolved' => 'Selesai',
            'dismissed' => 'Diabaikan',
        ];

        $typeOptions = [
            'all' => 'Semua Jenis',
            'post' => 'Postingan',
            'comment' => 'Komentar',
            'user' => 'Pengguna',
            'community' => 'Komunitas',
        ];

        $sortOptions = [
            'latest' => 'Terbaru',
            'most_reported' => 'Laporan Terbanyak',
            'priority' => 'Prioritas Baru',
        ];

        $buildFilterUrl = function (array $overrides = []) use ($statusFilter, $typeFilter, $sort, $search) {
            $params = [
                'status' => $overrides['status'] ?? $statusFilter,
                'type' => $overrides['type'] ?? $typeFilter,
                'sort' => $overrides['sort'] ?? $sort,
                'search' => $overrides['search'] ?? $search,
            ];

            $filtered = [];
            if (($params['status'] ?? 'all') !== 'all') {
                $filtered['status'] = $params['status'];
            }
            if (($params['type'] ?? 'all') !== 'all') {
                $filtered['type'] = $params['type'];
            }
            if (($params['sort'] ?? 'latest') !== 'latest') {
                $filtered['sort'] = $params['sort'];
            }
            if (trim((string) ($params['search'] ?? '')) !== '') {
                $filtered['search'] = trim((string) $params['search']);
            }

            return route('admin.reports.index', $filtered);
        };
    @endphp

    <div class="space-y-4" x-data="reportManager()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
             class="px-4 py-2.5 rounded-2xl border flex items-center gap-2 text-xs font-medium shadow-2xs"
             style="display: none;">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span>
            <span x-text="flashMsg"></span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
            <form method="GET" action="{{ route('admin.reports.index') }}"
                  class="flex-1 min-w-0 bg-white rounded-2xl border border-slate-200/80 px-4 py-2.5 shadow-2xs flex items-center gap-2.5">
                @if($statusFilter !== 'all')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                @if($typeFilter !== 'all')
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                @if($sort !== 'latest')
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                    <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
                </svg>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari target, @username, pelapor, atau alasan..."
                       class="w-full min-w-0 border-0 bg-transparent p-0 text-xs text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none">
            </form>

            <div class="flex items-center gap-2 shrink-0">
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button"
                            @click="open = !open"
                            class="px-3.5 py-2.5 rounded-2xl border border-slate-200/80 bg-white hover:border-slate-300 text-xs font-semibold text-slate-700 shadow-2xs inline-flex items-center gap-2 whitespace-nowrap cursor-pointer transition-colors">
                        <span>{{ $typeOptions[$typeFilter] ?? 'Semua Jenis' }}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none bg-slate-100 text-slate-600">
                            {{ $typeCounts[$typeFilter] ?? 0 }}
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         style="display: none;"
                         class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-48 bg-white rounded-2xl border border-slate-200/90 shadow-lg py-1.5 z-30">
                        @foreach($typeOptions as $typeKey => $typeLabel)
                            <a href="{{ $buildFilterUrl(['type' => $typeKey]) }}"
                               class="px-3.5 py-2 text-xs flex items-center justify-between gap-2 transition-colors {{ $typeFilter === $typeKey ? 'bg-blue-50/70 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium' }}">
                                <span>{{ $typeLabel }}</span>
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                    {{ $typeCounts[$typeKey] ?? 0 }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button"
                            @click="open = !open"
                            class="px-3.5 py-2.5 rounded-2xl border border-slate-200/80 bg-white hover:border-slate-300 text-xs font-semibold text-slate-700 shadow-2xs inline-flex items-center gap-2 whitespace-nowrap cursor-pointer transition-colors">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h12M3 12h9m-9 5h6m7-9v10m0 0-3-3m3 3 3-3"></path>
                        </svg>
                        <span>{{ $sortOptions[$sort] ?? 'Terbaru' }}</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-1.5 w-44 bg-white rounded-2xl border border-slate-200/90 shadow-lg py-1.5 z-30">
                        @foreach($sortOptions as $sortKey => $sortLabel)
                            <a href="{{ $buildFilterUrl(['sort' => $sortKey]) }}"
                               class="block px-3.5 py-2 text-xs transition-colors {{ $sort === $sortKey ? 'bg-blue-50/70 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium' }}">
                                {{ $sortLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2.5 flex-wrap">
            <div class="inline-flex items-center gap-1 bg-slate-200/70 p-1 rounded-2xl flex-wrap">
                @foreach($statusTabs as $statusKey => $statusLabel)
                    @php
                        $countVal = $statusCounts[$statusKey] ?? 0;
                        $isActive = $statusFilter === $statusKey;
                    @endphp
                    <a href="{{ $buildFilterUrl(['status' => $statusKey]) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 whitespace-nowrap {{ $isActive ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <span>{{ $statusLabel }}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none {{ $statusKey === 'new' && $countVal > 0 ? 'bg-rose-100 text-rose-700' : ($countVal === 0 ? 'bg-white/50 text-slate-400' : ($isActive ? 'bg-slate-100 text-slate-700' : 'bg-white/70 text-slate-600')) }}">
                            {{ $countVal }}
                        </span>
                    </a>
                @endforeach
            </div>

            @if($search !== '' || $statusFilter !== 'all' || $typeFilter !== 'all' || $sort !== 'latest')
                <a href="{{ route('admin.reports.index') }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 transition-colors shrink-0">
                    Reset filter
                </a>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs overflow-hidden">
            @forelse($reports as $report)
                @php
                    $effectiveStatus = $report->group_status ?? $report->status;
                    $effectiveStatusLabel = $report->group_status_label ?? $report->status_label;
                    $reportCount = (int) ($report->report_count ?? 1);
                    $uniqueReporters = (int) ($report->unique_reporters_count ?? 1);
                    $distinctReasons = (int) ($report->distinct_reasons_count ?? 1);
                    $groupItems = $report->group_items ?? [[
                        'id' => $report->id,
                        'reporter' => $report->reporter_display ?? ($report->reporter?->name ?? 'Sistem'),
                        'reason' => $report->reason,
                        'status' => $report->status,
                        'status_label' => $report->status_label,
                        'reported_at_human' => $report->reported_at_human ?? ($report->created_at?->locale('id')->diffForHumans() ?? '-'),
                        'reported_at_formatted' => $report->reported_at_formatted ?? ($report->created_at?->format('d M Y, H:i') ?? '-'),
                    ]];

                    $statusClasses = match ($effectiveStatus) {
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
                                {{ $effectiveStatusLabel }}
                            </span>
                            @if($reportCount > 1)
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">
                                    {{ $reportCount }} laporan
                                </span>
                            @endif
                            <span class="text-[11px] text-slate-400" title="{{ $report->reported_at_formatted ?? '' }}">
                                {{ $report->reported_at_human ?? $report->created_at?->locale('id')->diffForHumans() }} · {{ $report->reported_at_formatted ?? $report->created_at?->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <p class="text-sm font-bold text-slate-900 truncate">{{ $report->display_title }}</p>
                        <p class="text-xs text-slate-600 mt-0.5 line-clamp-2">
                            <span class="font-semibold text-slate-700">Alasan:</span> {{ $report->reason }}
                            @if($distinctReasons > 1)
                                <span class="text-[11px] font-semibold text-blue-600">(+{{ $distinctReasons - 1 }} alasan lainnya)</span>
                            @endif
                        </p>
                        <p class="text-[11px] text-slate-400 truncate mt-1">
                            Pelapor: <span class="font-medium text-slate-600">{{ $report->reporter_display ?? ($report->reporter?->name ?? 'Sistem') }}</span>@if($reportCount > 1 && $uniqueReporters > 1) &amp; {{ $uniqueReporters - 1 }} lainnya @endif · {{ $report->display_meta }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                @click="openReview({
                                    id: {{ $report->id }},
                                    typeLabel: @js($report->type_label),
                                    statusLabel: @js($effectiveStatusLabel),
                                    status: @js($effectiveStatus),
                                    title: @js($report->display_title),
                                    meta: @js($report->display_meta),
                                    reason: @js($report->reason),
                                    reporter: @js($report->reporter_display ?? ($report->reporter?->name ?? 'Sistem')),
                                    reportedAt: @js(($report->reported_at_formatted ?? '-') . ' (' . ($report->reported_at_human ?? '-') . ')'),
                                    targetUrl: @js($report->target_url ?? ''),
                                    reportCount: {{ $reportCount }},
                                    items: @js($groupItems)
                                })"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 transition-colors cursor-pointer">
                            Tinjau
                        </button>

                        <button type="button"
                                @click="openDelete({
                                    id: {{ $report->id }},
                                    title: @js($report->display_title),
                                    typeLabel: @js($report->type_label),
                                    canDeleteTarget: {{ $report->can_delete_target ? 'true' : 'false' }},
                                    reportCount: {{ $reportCount }}
                                })"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 transition-colors cursor-pointer">
                            Hapus
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-400">
                    Tidak ada laporan yang sesuai dengan filter atau pencarian saat ini.
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
                 class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600" x-text="selectedReport.typeLabel"></span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700"
                              x-show="(selectedReport.reportCount || 1) > 1"
                              x-text="(selectedReport.reportCount || 1) + ' laporan terkelompok'"></span>
                    </div>
                    <template x-if="selectedReport.targetUrl">
                        <a :href="selectedReport.targetUrl" target="_blank"
                           class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
                            Buka target →
                        </a>
                    </template>
                </div>

                <h3 class="text-base font-extrabold text-slate-900 break-words" x-text="selectedReport.title"></h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed" x-text="selectedReport.meta"></p>

                <div class="mt-4 space-y-2 max-h-60 overflow-y-auto pr-1">
                    <template x-for="item in (selectedReport.items || [])" :key="item.id">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
                                <span class="text-xs font-bold text-slate-800" x-text="item.reporter"></span>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                          :class="badgeClassFor(item.status)"
                                          x-text="item.status_label"></span>
                                    <span class="text-[11px] text-slate-400" x-text="item.reported_at_formatted"></span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed" x-text="item.reason"></p>
                        </div>
                    </template>
                </div>

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

                <template x-if="(deleteTarget.reportCount || 1) > 1">
                    <label class="flex items-center gap-2.5 mt-4 cursor-pointer select-none">
                        <input type="checkbox"
                               x-model="deleteAlsoGroup"
                               class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-medium text-slate-700"
                              x-text="'Hapus semua ' + deleteTarget.reportCount + ' laporan untuk target ini sekaligus'"></span>
                    </label>
                </template>

                <template x-if="deleteTarget.canDeleteTarget">
                    <label class="flex items-center gap-2.5 mt-3 cursor-pointer select-none">
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
                deleteAlsoGroup: true,
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,

                badgeClassFor(status) {
                    const map = {
                        resolved: 'bg-emerald-50 text-emerald-700',
                        reviewed: 'bg-amber-50 text-amber-700',
                        dismissed: 'bg-slate-100 text-slate-600',
                        new: 'bg-rose-50 text-rose-600',
                    };
                    return map[status] || 'bg-slate-100 text-slate-600';
                },

                openReview(report) {
                    this.selectedReport = report;
                    this.reviewModalOpen = true;
                },

                openDelete(target) {
                    this.deleteTarget = target;
                    this.deleteAlsoTarget = false;
                    this.deleteAlsoGroup = (target.reportCount || 1) > 1;
                    this.deleteModalOpen = true;
                },

                async submitReview(status) {
                    const id = this.selectedReport.id;
                    const applyToGroup = (this.selectedReport.reportCount || 1) > 1;
                    this.reviewModalOpen = false;

                    try {
                        const res = await fetch(`/admin/reports/${id}/review`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ status, apply_to_group: applyToGroup }),
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            const badge = document.getElementById(`report-status-${id}`);
                            if (badge) {
                                badge.innerText = data.status_label;
                                badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold ' + this.badgeClassFor(status);
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
                    const applyToGroup = (this.deleteTarget.reportCount || 1) > 1 && this.deleteAlsoGroup;
                    this.deleteModalOpen = false;

                    try {
                        const res = await fetch(`/admin/reports/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                delete_target: this.deleteAlsoTarget,
                                apply_to_group: applyToGroup,
                            }),
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            this.showFlash(data.message, 'success');
                            if (!applyToGroup && (this.deleteTarget.reportCount || 1) > 1 && !this.deleteAlsoTarget) {
                                setTimeout(() => window.location.reload(), 650);
                            } else {
                                const row = document.getElementById(`report-row-${id}`);
                                if (row) {
                                    row.style.transition = 'opacity 0.25s';
                                    row.style.opacity = '0';
                                    setTimeout(() => row.remove(), 250);
                                }
                            }
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
