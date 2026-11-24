
<x-app-layout>

    <div class="space-y-4">

        <!-- HEADER NOTIFIKASI -->
        <section class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">

            <div class="flex items-center justify-between gap-3">

                <div class="flex items-center gap-3">

                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4">
                            </path>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-lg font-extrabold text-slate-900">
                            Notifikasi
                        </h1>

                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $unreadCount }} belum dibaca
                        </p>
                    </div>

                </div>

                <!-- TANDAI SEMUA DIBACA -->
                @if($unreadCount > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf

                        <button type="submit"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:bg-blue-50 px-3 py-2 rounded-lg transition-colors">
                            Tandai semua dibaca
                        </button>
                    </form>
                @endif

            </div>
        </section>

        <!-- PESAN SUKSES -->
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <!-- FILTER NOTIFIKASI -->
        <div class="bg-white rounded-2xl border border-slate-200 p-2">
            <div class="flex items-center gap-1 overflow-x-auto">

               @php
                    $filters = [
                         'all' => 'Semua',
                         'comment' => 'Komentar',
                         'reply' => 'Balasan',
                         'vote' => 'Vote',
                         ];

                    if ($canSeeModeration) {
                         $filters['moderation'] = 'Moderasi';
                    }
               @endphp

                @foreach($filters as $key => $label)

                    <a href="{{ route('notifications.index', ['filter' => $key]) }}"
                        class="shrink-0 px-4 py-2 rounded-xl text-xs font-semibold transition-colors
                        {{ $filter === $key
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">

                        {{ $label }}

                    </a>

                @endforeach

            </div>
        </div>

        <!-- DAFTAR NOTIFIKASI -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

            @if($notifications->isNotEmpty())

                @php
                    $groupedNotifications = $notifications->getCollection()->groupBy(
                        function ($notification) {
                            if ($notification->created_at->isToday()) {
                                return 'Hari ini';
                            }

                            if ($notification->created_at->isYesterday()) {
                                return 'Kemarin';
                            }

                            return $notification->created_at->translatedFormat('d F Y');
                        }
                    );
                @endphp

                @foreach($groupedNotifications as $date => $items)

                    <!-- LABEL TANGGAL -->
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                        <h2 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            {{ $date }}
                        </h2>
                    </div>

                    @foreach($items as $notification)

                        @php
                            $data = $notification->data ?? [];

                            $category = $data['category'] ?? 'other';

                            $actorName = $data['actor_name'] ?? 'Pengguna CoHub';

                            $initials = strtoupper(substr($actorName, 0, 2));

                            $message = $data['message'] ?? 'Ada aktivitas baru untukmu.';

                            $content = $data['comment_content'] ?? null;

                            $categoryLabels = [
                              'comment' => 'Komentar',
                              'reply' => 'Balasan',
                              'vote' => 'Vote',
                              'moderation' => 'Moderasi',
                              ];

                            $categoryColors = [
                              'comment' => 'bg-violet-50 text-violet-600',
                              'reply' => 'bg-blue-50 text-blue-600',
                              'vote' => 'bg-emerald-50 text-emerald-600',
                              'moderation' => 'bg-amber-50 text-amber-700',
                              ];
                        @endphp

                        <!-- SATU NOTIFIKASI -->
                        <form action="{{ route('notifications.read', $notification->id) }}"
                            method="POST">

                            @csrf

                            <button type="submit"
                              class="w-full text-left flex items-start gap-3 px-5 py-4 border-b border-slate-100 last:border-b-0 transition-colors
                              {{ $notification->read_at === null
                                   ? 'bg-blue-50 hover:bg-blue-100'
                                   : 'bg-white hover:bg-slate-50' }}">

                                <!-- AVATAR PENGIRIM -->
                                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ $initials }}
                                </div>

                                <!-- ISI NOTIFIKASI -->
                                <div class="flex-1 min-w-0">

                                    <div class="flex items-center justify-between gap-2 mb-1">

                                        <span class="text-xs font-bold text-slate-800">
                                            {{ $actorName }}
                                        </span>

                                        <span class="text-[10px] text-slate-400 whitespace-nowrap">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>

                                    </div>

                                    <p class="text-xs text-slate-600 leading-relaxed">
                                        {{ $message }}
                                    </p>

                                    @if(!empty($data['post_title']))
                                        <p class="text-xs font-semibold text-slate-800 mt-1">
                                            {{ $data['post_title'] }}
                                        </p>
                                    @endif

                                    @if($content)
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                            "{{ $content }}"
                                        </p>
                                    @endif

                                    <div class="flex items-center gap-2 mt-2">

                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold
                                            {{ $categoryColors[$category] ?? 'bg-slate-100 text-slate-500' }}">

                                            {{ $categoryLabels[$category] ?? 'Notifikasi' }}

                                        </span>

                                        @if($notification->read_at === null)
                                             <span class="text-[10px] font-bold text-blue-700">
                                                  Belum dibaca
                                             </span>
                                             @else
                                             <span class="text-[10px] font-medium text-slate-500">
                                                  Sudah dibaca
                                             </span>
                                             @endif

                                    </div>

                                </div>

                                <!-- PENANDA BELUM DIBACA -->
                                @if($notification->read_at === null)
                                    <div class="w-2 h-2 rounded-full bg-blue-600 shrink-0 mt-2"></div>
                                @endif

                            </button>

                        </form>

                    @endforeach

                @endforeach

            @else

                <!-- EMPTY STATE -->
                <div class="p-10 text-center">

                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center">

                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4">
                            </path>
                        </svg>

                    </div>

                    <h2 class="text-sm font-bold text-slate-700 mb-1">
                        Belum Ada Notifikasi
                    </h2>

                    <p class="text-xs text-slate-400">
                        @if($filter === 'all')
                            Aktivitas terbaru akan muncul di sini.
                        @else
                            Belum ada notifikasi dalam kategori ini.
                        @endif
                    </p>

                </div>

            @endif

        </div>

        <!-- PAGINATION -->
        @if($notifications->hasPages())
            <div class="pt-2">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>

</x-app-layout>
