<x-app-layout>
    @php
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;
        $isAdmin = $user->isAdmin();
        $isModerator = !$isAdmin && $user->moderatedCommunities()->exists();

        $initials = strtoupper(
            collect(explode(' ', $user->name))
                ->filter()
                ->take(2)
                ->map(fn ($word) => substr($word, 0, 1))
                ->implode('')
        );

        $tabs = [
            'posts' => 'Postingan',
            'replies' => 'Balasan',
            'votes' => 'Vote',
            'communities' => 'Komunitas',
        ];
    @endphp

    <div class="space-y-4">

        <!-- HEADER PROFILE -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">

            <!-- Banner -->
            <div class="h-28 sm:h-32 bg-gradient-to-r from-pink-100 via-rose-100 to-purple-100">
            </div>

            <div class="px-5 pb-5">

                <!-- Avatar dan Edit Profil -->
                <div class="flex items-end justify-between -mt-12 mb-3">

                    <div class="w-24 h-24 rounded-full bg-pink-100 border-4 border-white flex items-center justify-center text-pink-700 text-3xl font-extrabold shadow-sm">
                        {{ $initials }}
                    </div>

                    @if($isOwnProfile)
                        <a href="{{ route('profile.edit') }}"
                           class="mb-1 px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                            Edit profil
                        </a>
                    @endif

                </div>

                <!-- Nama dan Role -->
                <div class="flex items-center gap-2 flex-wrap">

                    <h1 class="text-xl font-extrabold text-slate-900">
                        {{ $user->name }}
                    </h1>

                    @if($isAdmin)
                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-1 rounded-lg">
                            Admin
                        </span>
                    @elseif($isModerator)
                        <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-1 rounded-lg">
                            Moderator
                        </span>
                    @endif

                </div>

                <!-- Username dan Tanggal Bergabung -->
                <p class="text-xs text-slate-400 mt-1">
                    <span>@</span>{{ $user->username }}
                    · Bergabung {{ $user->created_at->translatedFormat('F Y') }}
                </p>

                <!-- Bio -->
                <p class="text-sm text-slate-700 mt-3 leading-relaxed whitespace-pre-line">
                    {{ $user->bio ?: 'Pengguna CoHub' }}
                </p>

                <!-- Statistik -->
                <div class="flex items-center flex-wrap gap-2 mt-4">

                    <div class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full text-xs font-bold">
                        {{ $postCount }} Postingan
                    </div>

                    <div class="bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full text-xs font-bold">
                        {{ $commentCount }} Komentar
                    </div>

                    <div class="bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full text-xs font-bold">
                        {{ $communityCount }} Komunitas
                    </div>

                </div>

            </div>
        </section>

        <!-- TAB NAVIGATION -->
        <div class="bg-slate-200/70 rounded-xl p-1 flex items-center gap-1 overflow-x-auto">

            @foreach($tabs as $key => $label)

                <a href="{{ route('profile.show', [
                    'user' => $user->username,
                    'tab' => $key
                ]) }}"
                   class="px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors
                   {{ $tab === $key
                        ? 'bg-white text-slate-900 shadow-sm'
                        : 'text-slate-500 hover:text-slate-800 hover:bg-white/50' }}">

                    {{ $label }}

                </a>

            @endforeach

        </div>

        <!-- KONTEN TAB -->

        @if($tab === 'posts')

            <div class="space-y-4">

                @forelse($posts as $post)

                    <x-post-card :post="$post" />

                @empty

                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                        <p class="text-sm font-semibold text-slate-500">
                            Belum ada postingan.
                        </p>
                    </div>

                @endforelse

                @if($posts->hasPages())
                    <div class="pt-3">
                        {{ $posts->links() }}
                    </div>
                @endif

            </div>

        @elseif($tab === 'replies')

            <div class="space-y-3">

                @forelse($replies as $comment)

                    <article class="bg-white rounded-2xl border border-slate-200 p-4">

                        <div class="flex items-center gap-2 text-xs mb-3">

                            <span class="font-bold text-slate-800">
                                {{ $user->name }}
                            </span>

                            <span class="text-slate-400">
                                · {{ $comment->created_at->diffForHumans() }}
                            </span>

                        </div>

                        <p class="text-sm text-slate-700 leading-relaxed">
                            @if($comment->deleted_at)
                                <span class="italic text-slate-400">
                                    [komentar telah dihapus]
                                </span>
                            @else
                                {{ $comment->content }}
                            @endif
                        </p>

                        @if($comment->parent)
                            <p class="text-xs text-slate-400 mt-2">
                                Membalas komentar {{ $comment->parent->user->name }}
                            </p>
                        @endif

                        <a href="{{ route('posts.show', $comment->post) }}"
                           class="inline-block text-xs font-semibold text-blue-600 hover:text-blue-700 mt-3">
                            Lihat postingan
                        </a>

                    </article>

                @empty

                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                        <p class="text-sm font-semibold text-slate-500">
                            Belum ada balasan.
                        </p>
                    </div>

                @endforelse

                @if($replies->hasPages())
                    {{ $replies->links() }}
                @endif

            </div>

        @elseif($tab === 'votes')

            <div class="space-y-4">

                <!-- Vote Postingan -->
                <section class="bg-white rounded-2xl border border-slate-200 p-4">

                    <h2 class="font-bold text-sm text-slate-800 mb-3">
                        Vote Postingan
                    </h2>

                    @forelse($postVotes as $vote)

                        @if($vote->post)

                            <a href="{{ route('posts.show', $vote->post) }}"
                               class="block border-t border-slate-100 py-3">

                                <p class="text-xs font-semibold text-slate-800">
                                    {{ $vote->post->title }}
                                </p>

                                <span class="text-[11px] text-slate-400">
                                    {{ $vote->vote_type === 'upvote' ? '▲ Upvote' : '▼ Downvote' }}
                                    · {{ $vote->created_at->diffForHumans() }}
                                </span>

                            </a>

                        @endif

                    @empty

                        <p class="text-xs text-slate-400">
                            Belum ada vote postingan.
                        </p>

                    @endforelse

                </section>

                <!-- Vote Komentar -->
                <section class="bg-white rounded-2xl border border-slate-200 p-4">

                    <h2 class="font-bold text-sm text-slate-800 mb-3">
                        Vote Komentar
                    </h2>

                    @forelse($commentVotes as $vote)

                        @if($vote->comment)

                            <div class="border-t border-slate-100 py-3">

                                <p class="text-xs text-slate-700">
                                    {{ $vote->comment->deleted_at
                                        ? '[komentar telah dihapus]'
                                        : $vote->comment->content }}
                                </p>

                                <span class="text-[11px] text-slate-400">
                                    {{ $vote->vote_type === 'upvote' ? '▲ Upvote' : '▼ Downvote' }}
                                    · {{ $vote->created_at->diffForHumans() }}
                                </span>

                            </div>

                        @endif

                    @empty

                        <p class="text-xs text-slate-400">
                            Belum ada vote komentar.
                        </p>

                    @endforelse

                </section>

            </div>

        @elseif($tab === 'communities')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                @forelse($communities as $community)

                    <a href="{{ route('communities.show', $community->name) }}"
                       class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-blue-300 transition-colors">

                        <div class="flex items-center gap-3">

                            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($community->name, 0, 2)) }}
                            </div>

                            <div class="min-w-0 flex-1">

                                <p class="text-xs font-bold text-slate-800 truncate">
                                    c/{{ $community->name }}
                                </p>

                                <p class="text-[11px] text-slate-400">
                                    {{ $community->posts_count }} postingan
                                </p>

                            </div>

                        </div>

                        <p class="text-xs text-slate-500 mt-3 line-clamp-2">
                            {{ $community->description ?: 'Komunitas CoHub' }}
                        </p>

                        <span class="inline-block text-[10px] font-semibold text-blue-600 mt-3">
                            @if($community->created_by === $user->id)
                                Pembuat komunitas
                            @else
                                Moderator
                            @endif
                        </span>

                    </a>

                @empty

                    <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-8 text-center">
                        <p class="text-sm text-slate-500">
                            Belum ada komunitas.
                        </p>
                    </div>

                @endforelse

            </div>

        @endif

    </div>
</x-app-layout>
