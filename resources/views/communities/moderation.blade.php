<x-app-layout>
    <x-slot name="title">Ruang Moderasi c/{{ $community->name }}</x-slot>

    @php
        $statusFilter = $statusFilter ?? 'queue';
        $typeFilter = $typeFilter ?? 'all';
        $sort = $sort ?? 'latest';
        $search = $search ?? '';
        $statusCounts = $statusCounts ?? ['queue' => 0, 'all' => 0, 'new' => 0, 'reviewed' => 0, 'resolved' => 0, 'dismissed' => 0];
        $typeCounts = $typeCounts ?? ['all' => 0, 'post' => 0, 'comment' => 0];

        $statusTabs = [
            'queue' => 'Antrean',
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
        ];

        $sortOptions = [
            'latest' => 'Terbaru',
            'most_reported' => 'Laporan Terbanyak',
            'priority' => 'Prioritas Baru',
        ];

        $buildModerationUrl = function (array $overrides = []) use ($community, $statusFilter, $typeFilter, $sort, $search) {
            $params = [
                'status' => $overrides['status'] ?? $statusFilter,
                'type' => $overrides['type'] ?? $typeFilter,
                'sort' => $overrides['sort'] ?? $sort,
                'search' => $overrides['search'] ?? $search,
            ];

            $filtered = ['community' => $community->name];
            if (($params['status'] ?? 'queue') !== 'queue') {
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

        <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
            <form method="GET" action="{{ route('communities.moderation.index', $community->name) }}"
                  class="flex-1 min-w-0 bg-white rounded-2xl border border-slate-200/80 px-4 py-2.5 shadow-2xs flex items-center gap-2.5">
                @if($statusFilter !== 'queue')
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
                       placeholder="Cari konten, penulis, pelapor, atau alasan..."
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
            </div>
        </div>

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
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClasses }}">
                                {{ $effectiveStatusLabel }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $stateClasses }}">
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
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                @click="openDetail({
                                    id: {{ $report->id }},
                                    typeLabel: @js($report->type_label),
                                    statusLabel: @js($effectiveStatusLabel),
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
                    <template x-if="selected.targetUrl">
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

                <div class="mt-3 space-y-2 max-h-52 overflow-y-auto pr-1">
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

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button"
                            @click="detailOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function communityModerationQueue() {
            return {
                detailOpen: false,
                selected: {},

                openDetail(item) {
                    this.selected = item;
                    this.detailOpen = true;
                }
            };
        }
    </script>
</x-app-layout>
