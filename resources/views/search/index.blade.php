
<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-5">

        {{-- Header --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <h1 class="text-lg font-bold text-slate-900">
                Pencarian Global
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Temukan postingan, komunitas, dan pengguna di CoHub.
            </p>

            <form action="{{ route('search.index') }}" method="GET"
                class="mt-4 flex gap-2">
                <input type="text"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="Cari sesuatu di CoHub..."
                    maxlength="100"
                    required
                    class="flex-1 min-w-0 px-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                <input type="hidden" name="tab" value="{{ $tab }}">

                <button type="submit"
                    class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors">
                    Cari
                </button>
            </form>

            @error('q')
                <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
            @enderror
        </div>

        @if($keyword !== '')           
          {{-- Tab pencarian --}}
          <div class="bg-white rounded-2xl border border-slate-200 p-2 shadow-2xs">
          <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px;">

               <a href="{{ route('search.index', ['q' => $keyword, 'tab' => 'posts']) }}"
                    class="flex items-center justify-center gap-2 rounded-xl px-2 py-3 text-xs sm:text-sm font-semibold transition
                    {{ $tab === 'posts' ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    Postingan
                    <span>{{ $posts->total() }}</span>
               </a>

               <a href="{{ route('search.index', ['q' => $keyword, 'tab' => 'communities']) }}"
                    class="flex items-center justify-center gap-2 rounded-xl px-2 py-3 text-xs sm:text-sm font-semibold transition
                    {{ $tab === 'communities' ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    Komunitas
                    <span>{{ $communities->total() }}</span>
               </a>

               <a href="{{ route('search.index', ['q' => $keyword, 'tab' => 'users']) }}"
                    class="flex items-center justify-center gap-2 rounded-xl px-2 py-3 text-xs sm:text-sm font-semibold transition
                    {{ $tab === 'users' ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    Pengguna
                    <span>{{ $users->total() }}</span>
               </a>

          </div>
          </div>


            {{-- Hasil postingan --}}
            @if($tab === 'posts')
                <div class="space-y-3">
                    @forelse($posts as $post)
                        <article class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">

                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('profile.show', ['user' => $post->user->username]) }}"
                                        class="text-xs font-bold text-slate-700 hover:text-blue-600">
                                        {{ $post->user->name }}
                                    </a>

                                    @if($post->community)
                                        <span class="text-xs text-slate-400">di</span>
                                        <a href="{{ route('communities.show', $post->community->name) }}"
                                            class="text-xs font-semibold text-blue-600 hover:underline">
                                            c/{{ $post->community->name }}
                                        </a>
                                    @endif
                                </div>

                                <span class="text-[10px] text-slate-400 shrink-0">
                                    {{ $post->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <a href="{{ route('posts.show', $post) }}" class="block mt-3 group">
                                <h2 class="text-sm font-bold text-slate-900 group-hover:text-blue-600">
                                    {{ $post->title }}
                                </h2>

                                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($post->content), 220) }}
                                </p>
                            </a>

                            <div class="mt-4 pt-3 border-t border-slate-100">
                                <span class="text-[11px] text-slate-400">
                                    {{ $post->comments_count }} komentar
                                </span>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                            <p class="text-sm font-bold text-slate-700">
                                Postingan tidak ditemukan
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Coba gunakan kata kunci lain.
                            </p>
                        </div>
                    @endforelse

                    <div>{{ $posts->links() }}</div>
                </div>
            @endif

            {{-- Hasil komunitas --}}
            @if($tab === 'communities')
                <div class="space-y-3">
                    @forelse($communities as $community)
                        <article class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
                            <div class="flex items-start gap-3">

                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden">
                                    @if($community->avatar_path)
                                        <img src="{{ $community->avatar_url }}"
                                            alt="{{ $community->name }}"
                                            class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($community->name, 0, 2)) }}
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('communities.show', $community->name) }}"
                                        class="text-sm font-bold text-slate-900 hover:text-blue-600">
                                        c/{{ $community->name }}
                                    </a>

                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @if($community->category)
                                            <span class="text-[10px] text-blue-600 font-semibold">
                                                {{ $community->category }}
                                            </span>
                                        @endif

                                        @if($community->is_private)
                                            <span class="text-[10px] text-amber-600 font-semibold">
                                                Privat
                                            </span>
                                        @else
                                            <span class="text-[10px] text-emerald-600 font-semibold">
                                                Publik
                                            </span>
                                        @endif

                                        @if($community->is_closed)
                                            <span class="text-[10px] text-red-500 font-semibold">
                                                Diskusi ditutup
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                        {{ $community->description ?: 'Belum ada deskripsi komunitas.' }}
                                    </p>

                                    <a href="{{ route('communities.show', $community->name) }}"
                                        class="inline-block mt-3 text-[11px] font-bold text-blue-600 hover:underline">
                                        Lihat komunitas →
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                            <p class="text-sm font-bold text-slate-700">
                                Komunitas tidak ditemukan
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Coba gunakan kata kunci lain.
                            </p>
                        </div>
                    @endforelse

                    <div>{{ $communities->links() }}</div>
                </div>
            @endif

            {{-- Hasil pengguna --}}
            @if($tab === 'users')
                <div class="space-y-3">
                    @forelse($users as $resultUser)
                        <article class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
                            <div class="flex items-center gap-3">

                                <div class="w-11 h-11 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($resultUser->name, 0, 2)) }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('profile.show', ['user' => $resultUser->username]) }}"
                                        class="text-sm font-bold text-slate-900 hover:text-blue-600">
                                        {{ $resultUser->name }}
                                    </a>

                                    <p class="text-xs text-slate-400">
                                        &#64;{{ $resultUser->username }}
                                   </p>

                                    @if($resultUser->bio)
                                        <p class="text-xs text-slate-500 mt-1">
                                            {{ \Illuminate\Support\Str::limit($resultUser->bio, 120) }}
                                        </p>
                                    @endif
                                </div>

                                <a href="{{ route('profile.show', ['user' => $resultUser->username]) }}"
                                    class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-bold hover:bg-blue-100 shrink-0">
                                    Lihat profil
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                            <p class="text-sm font-bold text-slate-700">
                                Pengguna tidak ditemukan
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Coba gunakan nama atau username lain.
                            </p>
                        </div>
                    @endforelse

                    <div>{{ $users->links() }}</div>
                </div>
            @endif

        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                <p class="text-sm font-bold text-slate-700">
                    Mulai pencarian di CoHub
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    Masukkan kata kunci untuk menemukan postingan, komunitas, atau pengguna.
                </p>
            </div>
        @endif

    </div>
</x-app-layout>
