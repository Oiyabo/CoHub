<x-app-layout>
    <x-slot name="title">Dashboard Admin</x-slot>

    <x-slot name="sidebar">
        @include('admin.partials.sidebar')
    </x-slot>

    @include('admin.partials.header')

    <div class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
            <a href="{{ route('admin.users.index') }}"
               class="bg-blue-50/90 hover:bg-blue-100/70 border border-blue-100 rounded-2xl p-4 transition-colors block">
                <p class="text-xs font-medium text-blue-700">Pengguna</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['total_users']) }}</p>
                <p class="text-[11px] text-blue-600 mt-1">+{{ number_format($stats['users_this_week']) }} minggu ini</p>
            </a>

            <a href="{{ route('admin.communities.index') }}"
               class="bg-emerald-50/90 hover:bg-emerald-100/70 border border-emerald-100 rounded-2xl p-4 transition-colors block">
                <p class="text-xs font-medium text-emerald-700">Komunitas</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['total_communities']) }}</p>
                <p class="text-[11px] text-emerald-600 mt-1">+{{ number_format($stats['communities_new']) }} baru</p>
            </a>

            <div class="bg-purple-50/90 border border-purple-100 rounded-2xl p-4">
                <p class="text-xs font-medium text-purple-700">Postingan</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['total_posts']) }}</p>
                <p class="text-[11px] text-purple-600 mt-1">+{{ number_format($stats['posts_today']) }} hari ini</p>
            </div>

            <a href="{{ route('admin.reports.index') }}"
               class="bg-rose-50/90 hover:bg-rose-100/70 border border-rose-100 rounded-2xl p-4 transition-colors block">
                <p class="text-xs font-medium text-rose-700">Laporan</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['pending_reports']) }}</p>
                <p class="text-[11px] text-rose-600 mt-1">perlu ditinjau</p>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
            <h2 class="text-sm font-bold text-slate-900 mb-4">Aktivitas 7 hari terakhir</h2>

            @php
                $maxTotal = max(collect($activityData)->max('total'), 1);
                $defaultHeights = [38, 60, 34, 72, 52, 86, 48];
                $hasAnyActivity = collect($activityData)->sum('total') > 0;
            @endphp

            <div class="grid grid-cols-7 gap-3 items-end h-32 pt-2">
                @foreach($activityData as $idx => $day)
                    @php
                        $pct = $hasAnyActivity
                            ? max(22, (int) round(($day['total'] / $maxTotal) * 100))
                            : $defaultHeights[$idx % 7];
                        $isPeak = $idx === $peakIndex;
                    @endphp
                    <div class="h-full flex flex-col justify-end group" title="{{ $day['date'] }}: {{ $day['posts'] }} postingan, {{ $day['comments'] }} komentar">
                        <div class="w-full rounded-xl transition-all {{ $isPeak ? 'bg-blue-600' : 'bg-blue-200/75 group-hover:bg-blue-300' }}"
                             style="height: {{ $pct }}%;"></div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-3 mt-2 text-center">
                @foreach($activityData as $day)
                    <span class="text-[10px] text-slate-400">{{ $day['date'] }}</span>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-100 shadow-2xs overflow-hidden">
            @forelse($recentActivities as $item)
                <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($item['type'] === 'user')
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        @elseif($item['type'] === 'community')
                            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 4.5-3 8.4-7 9-4-.6-7-4.5-7-9V7l7-4z"></path>
                                </svg>
                            </div>
                        @else
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-6 4h10M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                                </svg>
                            </div>
                        @endif

                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ $item['actor'] }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $item['action'] }}</p>
                        </div>
                    </div>

                    <span class="text-[11px] text-slate-400 shrink-0">{{ $item['time_short'] }}</span>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-xs text-slate-400">
                    Belum ada aktivitas terbaru.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
