<x-app-layout>
    <x-slot name="title">Ruang Moderasi c/{{ $community->name }}</x-slot>

    @php
        $tab = $tab ?? 'queue';
        $statusFilter = $statusFilter ?? 'queue';
        $typeFilter = $typeFilter ?? 'all';
        $actionFilter = $actionFilter ?? 'all';
        $sort = $sort ?? 'latest';
        $search = $search ?? '';
        $statusCounts = $statusCounts ?? ['queue' => 0, 'all' => 0, 'new' => 0, 'reviewed' => 0, 'resolved' => 0, 'dismissed' => 0];
        $typeCounts = $typeCounts ?? ['all' => 0, 'post' => 0, 'comment' => 0];
        $actionCounts = $actionCounts ?? ['all' => 0, 'approve' => 0, 'hide' => 0, 'delete' => 0, 'dismiss' => 0];

        $statusTabs = [
            'queue' => 'Antrean',
            'all' => 'Semua',
            'new' => 'Baru',
            'reviewed' => 'Ditinjau',
            'resolved' => 'Selesai',
            'dismissed' => 'Diabaikan',
        ];

        $actionTabs = [
            'all' => 'Semua Aksi',
            'approve' => 'Disetujui',
            'hide' => 'Disembunyikan',
            'delete' => 'Dihapus',
            'dismiss' => 'Diabaikan',
        ];

        $typeOptions = [
            'all' => 'Semua Jenis',
            'post' => 'Postingan',
            'comment' => 'Komentar',
        ];

        $sortOptions = [
            'latest' => 'Terbaru',
            'most_reported' => 'Laporan Terbanyak',
            'priority' => 'Prioritas Baru',
        ];

        $buildModerationUrl = function (array $overrides = []) use ($community, $tab, $statusFilter, $typeFilter, $actionFilter, $sort, $search) {
            $activeTab = $overrides['tab'] ?? $tab;
            $params = [
                'tab' => $activeTab,
                'status' => $overrides['status'] ?? $statusFilter,
                'type' => $overrides['type'] ?? $typeFilter,
                'action' => $overrides['action'] ?? $actionFilter,
                'sort' => $overrides['sort'] ?? $sort,
                'search' => $overrides['search'] ?? $search,
            ];

            $filtered = ['community' => $community->name];
            if ($activeTab !== 'queue') {
                $filtered['tab'] = $activeTab;
            }
            if ($activeTab === 'queue' && ($params['status'] ?? 'queue') !== 'queue') {
                $filtered['status'] = $params['status'];
            }
            if ($activeTab === 'logs' && ($params['action'] ?? 'all') !== 'all') {
                $filtered['action'] = $params['action'];
            }
            if (($params['type'] ?? 'all') !== 'all') {
                $filtered['type'] = $params['type'];
            }
            if ($activeTab === 'queue' && ($params['sort'] ?? 'latest') !== 'latest') {
                $filtered['sort'] = $params['sort'];
            }
            if (trim((string) ($params['search'] ?? '')) !== '') {
                $filtered['search'] = trim((string) $params['search']);
            }

            return route('communities.moderation.index', $filtered);
        };
    @endphp

    <x-slot name="sidebar">
        <div class="space-y-4">
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-slate-900">Ringkasan Moderasi</h4>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700">
                        c/{{ $community->name }}
                    </span>
                </div>

                <div class="space-y-2 text-xs text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Antrean aktif</span>
                        <span class="font-bold text-rose-600">{{ $statusCounts['queue'] ?? 0 }} laporan</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total semua laporan</span>
                        <span class="font-semibold text-slate-800">{{ $statusCounts['all'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Riwayat tindakan</span>
                        <span class="font-semibold text-slate-800">{{ $actionCounts['all'] ?? 0 }} log</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total anggota</span>
                        <span class="font-semibold text-slate-800">{{ number_format($community->members_count ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total postingan</span>
                        <span class="font-semibold text-slate-800">{{ number_format($community->posts_count ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-slate-900">Aturan Komunitas</h4>
                    <a href="{{ route('communities.edit', $community->name) }}"
                       class="text-[11px] font-semibold text-blue-600 hover:underline">
                        Atur
                    </a>
                </div>

                @if(count($rules) > 0)
                    <ol class="space-y-2 text-xs text-slate-600 list-decimal pl-4">
                        @foreach($rules as $rule)
                            @php
                                $ruleText = is_array($rule) ? ($rule['text'] ?? $rule['title'] ?? '') : (string) $rule;
                            @endphp
                            @if($ruleText !== '')
                                <li class="leading-relaxed">{{ $ruleText }}</li>
                            @endif
                        @endforeach
                    </ol>
                @else
                    <p class="text-xs text-slate-400">Belum ada aturan khusus di komunitas ini.</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-4" x-data="communityModerationQueue()">

        <div x-show="flashMsg" x-transition
             :class="flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
             class="px-4 py-2.5 rounded-2xl border flex items-center gap-2 text-xs font-medium shadow-2xs"
             style="display: none;">
            <span x-text="flashType === 'error' ? '!' : '✓'"></span>
            <span x-text="flashMsg"></span>
        </div>

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('communities.show', $community->name) }}"
                   class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition shadow-2xs shrink-0">
                    &larr;
                </a>
                <div class="min-w-0">
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight truncate">Ruang Moderasi</h1>
                    <p class="text-xs text-slate-500 truncate">c/{{ $community->name }} &bull; Antrean konten dilaporkan di komunitas ini</p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('communities.edit', $community->name) }}"
                   class="px-3.5 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition shadow-2xs">
                    Pengaturan
                </a>
                <a href="{{ route('communities.show', $community->name) }}"
                   class="px-3.5 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-600 transition shadow-2xs">
                    Lihat Komunitas &rarr;
                </a>
            </div>
        </div>

        <div class="inline-flex items-center gap-1 bg-slate-200/70 p-1 rounded-2xl">
            <a href="{{ route('communities.moderation.index', ['community' => $community->name]) }}"
               class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 {{ $tab === 'queue' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span>Antrean Laporan</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none {{ ($statusCounts['queue'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-white/60 text-slate-500' }}">
                    {{ $statusCounts['queue'] ?? 0 }}
                </span>
            </a>
            <a href="{{ route('communities.moderation.index', ['community' => $community->name, 'tab' => 'logs']) }}"
               class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 {{ $tab === 'logs' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span>Riwayat Moderasi</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none {{ $tab === 'logs' ? 'bg-slate-100 text-slate-700' : 'bg-white/70 text-slate-600' }}">
                    {{ $actionCounts['all'] ?? 0 }}
                </span>
            </a>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
            <form method="GET" action="{{ route('communities.moderation.index', $community->name) }}"
                  class="flex-1 min-w-0 bg-white rounded-2xl border border-slate-200/80 px-4 py-2.5 shadow-2xs flex items-center gap-2.5">
                @if($tab !== 'queue')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                @endif
                @if($tab === 'queue' && $statusFilter !== 'queue')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                @if($tab === 'logs' && $actionFilter !== 'all')
                    <input type="hidden" name="action" value="{{ $actionFilter }}">
                @endif
                @if($typeFilter !== 'all')
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                @if($tab === 'queue' && $sort !== 'latest')
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                    <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
                </svg>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="{{ $tab === 'logs' ? 'Cari target, moderator, penulis, atau catatan...' : 'Cari konten, penulis, pelapor, atau alasan...' }}"
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
                         class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white rounded-2xl border border-slate-200/90 shadow-lg py-1.5 z-30">
                        @foreach($typeOptions as $typeKey => $typeLabel)
                            <a href="{{ $buildModerationUrl(['type' => $typeKey]) }}"
                               class="px-3.5 py-2 text-xs flex items-center justify-between gap-2 transition-colors {{ $typeFilter === $typeKey ? 'bg-blue-50/70 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium' }}">
                                <span>{{ $typeLabel }}</span>
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                    {{ $typeCounts[$typeKey] ?? 0 }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($tab === 'queue')
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
                                <a href="{{ $buildModerationUrl(['sort' => $sortKey]) }}"
                                   class="block px-3.5 py-2 text-xs transition-colors {{ $sort === $sortKey ? 'bg-blue-50/70 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium' }}">
                                    {{ $sortLabel }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($tab === 'logs')
            <div class="flex items-center justify-between gap-2.5 flex-wrap">
                <div class="inline-flex items-center gap-1 bg-slate-200/70 p-1 rounded-2xl flex-wrap">
                    @foreach($actionTabs as $actionKey => $actionLabel)
                        @php
                            $countVal = $actionCounts[$actionKey] ?? 0;
                            $isActive = $actionFilter === $actionKey;
                        @endphp
                        <a href="{{ $buildModerationUrl(['tab' => 'logs', 'action' => $actionKey]) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 whitespace-nowrap {{ $isActive ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>{{ $actionLabel }}</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none {{ $countVal === 0 ? 'bg-white/50 text-slate-400' : ($isActive ? 'bg-slate-100 text-slate-700' : 'bg-white/70 text-slate-600') }}">
                                {{ $countVal }}
                            </span>
                        </a>
                    @endforeach
                </div>

                @if($search !== '' || $actionFilter !== 'all' || $typeFilter !== 'all')
                    <a href="{{ route('communities.moderation.index', ['community' => $community->name, 'tab' => 'logs']) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 transition-colors shrink-0">
                        Reset filter
                    </a>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs overflow-hidden">
                @forelse($logs as $log)
                    @php
                        $logActionClasses = match ($log->action) {
                            'approve' => 'bg-emerald-50 text-emerald-700',
                            'hide' => 'bg-amber-50 text-amber-800',
                            'delete' => 'bg-rose-50 text-rose-700',
                            default => 'bg-slate-100 text-slate-600',
                        };
                    @endphp
                    <div class="px-5 py-4 hover:bg-slate-50/50 transition-colors">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $logActionClasses }}">
                                {{ $log->action_label }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                {{ $log->target_type_label }}
                            </span>
                            @if(($log->reports_affected ?? 1) > 1)
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">
                                    {{ $log->reports_affected }} laporan
                                </span>
                            @endif
                            <span class="text-[11px] text-slate-400">
                                {{ $log->created_at?->locale('id')->diffForHumans() }} · {{ $log->created_at?->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <p class="text-sm font-bold text-slate-900">{{ $log->target_snapshot }}</p>
                        @if($log->note)
                            <p class="text-xs text-slate-600 mt-1">
                                <span class="font-semibold text-slate-700">Catatan:</span> {{ $log->note }}
                            </p>
                        @endif
                        <p class="text-[11px] text-slate-400 mt-1">
                            Moderator: <span class="font-medium text-slate-600">{{ $log->moderator?->name ?? 'Sistem' }}@if($log->moderator?->username) ({{ '@' . $log->moderator->username }})@endif</span>
                            · Penulis konten: <span class="font-medium text-slate-600">{{ $log->targetAuthor?->name ?? 'Pengguna' }}</span>
                        </p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-xs text-slate-400">
                        Belum ada riwayat tindakan moderasi di c/{{ $community->name }}.
                    </div>
                @endforelse
            </div>

            @if($logs->hasPages())
                <div class="pt-1">
                    {{ $logs->links() }}
                </div>
            @endif
        @else
            <div class="flex items-center justify-between gap-2.5 flex-wrap">
                <div class="inline-flex items-center gap-1 bg-slate-200/70 p-1 rounded-2xl flex-wrap">
                    @foreach($statusTabs as $statusKey => $statusLabel)
                        @php
                            $countVal = $statusCounts[$statusKey] ?? 0;
                            $isActive = $statusFilter === $statusKey;
                        @endphp
                        <a href="{{ $buildModerationUrl(['status' => $statusKey]) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 whitespace-nowrap {{ $isActive ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>{{ $statusLabel }}</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none {{ ($statusKey === 'queue' || $statusKey === 'new') && $countVal > 0 ? 'bg-rose-100 text-rose-700' : ($countVal === 0 ? 'bg-white/50 text-slate-400' : ($isActive ? 'bg-slate-100 text-slate-700' : 'bg-white/70 text-slate-600')) }}">
                                {{ $countVal }}
                            </span>
                        </a>
                    @endforeach
                </div>

                @if($search !== '' || $statusFilter !== 'queue' || $typeFilter !== 'all' || $sort !== 'latest')
                    <a href="{{ route('communities.moderation.index', $community->name) }}"
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

                    $statusClasses = match ($effectiveStatus) {
                        'new' => 'bg-rose-50 text-rose-600',
                        'reviewed' => 'bg-amber-50 text-amber-700',
                        'resolved' => 'bg-emerald-50 text-emerald-700',
                        default => 'bg-slate-100 text-slate-600',
                    };

                    $stateClasses = match ($report->target_state ?? 'published') {
                        'hidden' => 'bg-amber-50 text-amber-700',
                        'deleted' => 'bg-slate-100 text-slate-500',
                        default => 'bg-emerald-50 text-emerald-700',
                    };
                @endphp

                <div class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors" id="mod-row-{{ $report->id }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                {{ $report->type_label }}
                            </span>
                            <span id="mod-status-{{ $report->id }}" class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClasses }}">
                                {{ $effectiveStatusLabel }}
                            </span>
                            <span id="mod-state-{{ $report->id }}" class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $stateClasses }}">
                                {{ $report->target_state_label ?? 'Publik' }}
                            </span>
                            @if($reportCount > 1)
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">
                                    {{ $reportCount }} laporan
                                </span>
                            @endif
                            <span class="text-[11px] text-slate-400">
                                {{ $report->reported_at_human }} · {{ $report->reported_at_formatted }}
                            </span>
                        </div>

                        <p class="text-sm font-bold text-slate-900 truncate">{{ $report->display_title }}</p>
                        @if($report->display_excerpt)
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $report->display_excerpt }}</p>
                        @endif
                        <p class="text-xs text-slate-600 mt-1">
                            <span class="font-semibold text-slate-700">Alasan:</span> {{ $report->reason }}
                            @if($distinctReasons > 1)
                                <span class="text-[11px] font-semibold text-blue-600">(+{{ $distinctReasons - 1 }} alasan lainnya)</span>
                            @endif
                        </p>
                        <p class="text-[11px] text-slate-400 truncate mt-1">
                            Pelapor: <span class="font-medium text-slate-600">{{ $report->reporter_display }}</span>@if($reportCount > 1 && $uniqueReporters > 1) &amp; {{ $uniqueReporters - 1 }} lainnya @endif · {{ $report->display_meta }}
                        </p>

                        @if(($report->target_state ?? 'published') !== 'deleted')
                            <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
                                <button type="button"
                                        :disabled="submitting"
                                        @click="submitAction({{ $report->id }}, 'approve')"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors cursor-pointer disabled:opacity-50">
                                    {{ ($report->target_state ?? 'published') === 'hidden' ? 'Setujui & Tampilkan' : 'Setujui' }}
                                </button>
                                @if(($report->target_state ?? 'published') !== 'hidden')
                                    <button type="button"
                                            :disabled="submitting"
                                            @click="submitAction({{ $report->id }}, 'hide')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-amber-50 hover:bg-amber-100 text-amber-800 transition-colors cursor-pointer disabled:opacity-50">
                                        Sembunyikan
                                    </button>
                                @endif
                                <button type="button"
                                        x-show="confirmRowDeleteId !== {{ $report->id }}"
                                        :disabled="submitting"
                                        @click="confirmRowDeleteId = {{ $report->id }}"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 transition-colors cursor-pointer disabled:opacity-50">
                                    Hapus
                                </button>
                                <span x-show="confirmRowDeleteId === {{ $report->id }}" style="display: none;" class="inline-flex items-center gap-1">
                                    <button type="button"
                                            :disabled="submitting"
                                            @click="submitAction({{ $report->id }}, 'delete')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-600 hover:bg-rose-700 text-white transition-colors cursor-pointer disabled:opacity-50">
                                        Ya, hapus
                                    </button>
                                    <button type="button"
                                            @click="confirmRowDeleteId = null"
                                            class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors cursor-pointer">
                                        Batal
                                    </button>
                                </span>
                                <button type="button"
                                        :disabled="submitting"
                                        @click="submitAction({{ $report->id }}, 'dismiss')"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors cursor-pointer disabled:opacity-50">
                                    Abaikan
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                @click="openDetail({
                                    id: {{ $report->id }},
                                    typeLabel: @js($report->type_label),
                                    statusLabel: @js($effectiveStatusLabel),
                                    targetState: @js($report->target_state ?? 'published'),
                                    targetStateLabel: @js($report->target_state_label ?? 'Publik'),
                                    title: @js($report->display_title),
                                    excerpt: @js($report->display_excerpt ?? ''),
                                    meta: @js($report->display_meta),
                                    targetUrl: @js($report->target_url ?? ''),
                                    reportCount: {{ $reportCount }},
                                    items: @js($report->group_items ?? [])
                                })"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 transition-colors cursor-pointer">
                            Tinjau
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-400">
                    Tidak ada konten yang perlu dimoderasi di c/{{ $community->name }} saat ini.
                </div>
            @endforelse
        </div>

            @if($reports->hasPages())
                <div class="pt-1">
                    {{ $reports->links() }}
                </div>
            @endif
        @endif

        <div x-show="detailOpen"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="detailOpen = false"
                 class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600" x-text="selected.typeLabel"></span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700" x-text="selected.targetStateLabel"></span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700"
                              x-show="(selected.reportCount || 1) > 1"
                              x-text="(selected.reportCount || 1) + ' laporan'"></span>
                    </div>
                    <template x-if="selected.targetUrl && selected.targetState !== 'deleted'">
                        <a :href="selected.targetUrl" target="_blank"
                           class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
                            Lihat konten &rarr;
                        </a>
                    </template>
                </div>

                <h3 class="text-base font-extrabold text-slate-900 break-words" x-text="selected.title"></h3>
                <p class="text-xs text-slate-500 mt-1" x-text="selected.meta"></p>

                <template x-if="selected.excerpt">
                    <div class="mt-3 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Cuplikan Konten</p>
                        <p class="text-xs text-slate-700 leading-relaxed" x-text="selected.excerpt"></p>
                    </div>
                </template>

                <div class="mt-3 space-y-2 max-h-48 overflow-y-auto pr-1">
                    <template x-for="item in (selected.items || [])" :key="item.id">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
                                <span class="text-xs font-bold text-slate-800" x-text="item.reporter"></span>
                                <span class="text-[11px] text-slate-400" x-text="item.reported_at_formatted"></span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed" x-text="item.reason"></p>
                        </div>
                    </template>
                </div>

                <template x-if="selected.targetState !== 'deleted'">
                    <div class="mt-4">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Catatan Moderasi (Opsional)</label>
                        <input type="text"
                               x-model="actionNote"
                               maxlength="255"
                               placeholder="Tulis alasan tindakan untuk log moderasi..."
                               class="w-full rounded-xl border-slate-200 text-xs text-slate-800 focus:border-blue-400 focus:ring-blue-400">
                    </div>
                </template>

                <div class="flex items-center justify-between gap-2 mt-5 flex-wrap">
                    <button type="button"
                            @click="detailOpen = false"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Tutup
                    </button>

                    <template x-if="selected.targetState !== 'deleted'">
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button"
                                    :disabled="submitting"
                                    @click="submitAction(selected.id, 'dismiss', true)"
                                    class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer disabled:opacity-50">
                                Abaikan
                            </button>
                            <button type="button"
                                    x-show="selected.targetState !== 'hidden'"
                                    :disabled="submitting"
                                    @click="submitAction(selected.id, 'hide', true)"
                                    class="px-3.5 py-2 text-xs font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-xl transition-colors cursor-pointer disabled:opacity-50">
                                Sembunyikan
                            </button>
                            <button type="button"
                                    x-show="!modalDeleteConfirm"
                                    :disabled="submitting"
                                    @click="modalDeleteConfirm = true"
                                    class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-xl transition-colors cursor-pointer disabled:opacity-50">
                                Hapus
                            </button>
                            <button type="button"
                                    x-show="modalDeleteConfirm"
                                    style="display: none;"
                                    :disabled="submitting"
                                    @click="submitAction(selected.id, 'delete', true)"
                                    class="px-3.5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors cursor-pointer disabled:opacity-50">
                                Konfirmasi Hapus
                            </button>
                            <button type="button"
                                    :disabled="submitting"
                                    @click="submitAction(selected.id, 'approve', true)"
                                    class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors cursor-pointer disabled:opacity-50"
                                    x-text="selected.targetState === 'hidden' ? 'Setujui & Tampilkan' : 'Setujui'">
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <script>
        function communityModerationQueue() {
            return {
                detailOpen: false,
                selected: {},
                actionNote: '',
                confirmRowDeleteId: null,
                modalDeleteConfirm: false,
                submitting: false,
                flashMsg: '',
                flashType: 'success',
                flashTimeout: null,
                statusFilter: @js($statusFilter),

                openDetail(item) {
                    this.selected = item;
                    this.actionNote = '';
                    this.modalDeleteConfirm = false;
                    this.detailOpen = true;
                },

                badgeClassForReportStatus(status) {
                    const map = {
                        resolved: 'bg-emerald-50 text-emerald-700',
                        reviewed: 'bg-amber-50 text-amber-700',
                        dismissed: 'bg-slate-100 text-slate-600',
                        new: 'bg-rose-50 text-rose-600',
                    };
                    return map[status] || 'bg-slate-100 text-slate-600';
                },

                badgeClassForTargetState(state) {
                    const map = {
                        hidden: 'bg-amber-50 text-amber-700',
                        deleted: 'bg-slate-100 text-slate-500',
                        published: 'bg-emerald-50 text-emerald-700',
                    };
                    return map[state] || 'bg-slate-100 text-slate-600';
                },

                async submitAction(reportId, action, fromModal = false) {
                    if (this.submitting) return;
                    this.submitting = true;
                    this.confirmRowDeleteId = null;

                    try {
                        const res = await fetch(`/c/{{ $community->name }}/moderation/${reportId}/action`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action,
                                note: fromModal && this.actionNote ? this.actionNote.trim() : null,
                            }),
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            if (fromModal) {
                                this.detailOpen = false;
                            }

                            const row = document.getElementById(`mod-row-${reportId}`);
                            if (this.statusFilter === 'queue' || this.statusFilter === 'new' || this.statusFilter === 'reviewed') {
                                if (row) {
                                    row.style.transition = 'opacity 0.25s';
                                    row.style.opacity = '0';
                                    setTimeout(() => row.remove(), 250);
                                }
                            } else {
                                const statusBadge = document.getElementById(`mod-status-${reportId}`);
                                if (statusBadge) {
                                    statusBadge.innerText = data.report_status_label;
                                    statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold ' + this.badgeClassForReportStatus(data.report_status);
                                }
                                const stateBadge = document.getElementById(`mod-state-${reportId}`);
                                if (stateBadge) {
                                    stateBadge.innerText = data.target_state_label;
                                    stateBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold ' + this.badgeClassForTargetState(data.target_state);
                                }
                            }

                            this.showFlash(data.message, 'success');
                        } else {
                            this.showFlash(data.message || 'Gagal menjalankan tindakan moderasi.', 'error');
                        }
                    } catch (err) {
                        this.showFlash('Terjadi kesalahan jaringan saat memproses tindakan.', 'error');
                    } finally {
                        this.submitting = false;
                        this.modalDeleteConfirm = false;
                    }
                },

                showFlash(msg, type = 'success') {
                    this.flashMsg = msg;
                    this.flashType = type;
                    if (this.flashTimeout) clearTimeout(this.flashTimeout);
                    this.flashTimeout = setTimeout(() => { this.flashMsg = ''; }, 3800);
                }
            };
        }
    </script>
</x-app-layout>
