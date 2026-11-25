@props(['post', 'detail' => false])

@php
    $author = $post->user;
    $isAuthorAdmin = $author->isAdmin();
    $isAuthorMod = $author->relationLoaded('moderatedCommunities')
        ? $author->moderatedCommunities->contains('id', $post->community_id)
        : $author->isModeratorOf($post->community_id);

    $score = isset($post->upvotes_count)
        ? $post->upvotes_count - ($post->downvotes_count ?? 0)
        : $post->score;
    $commentsCount = $post->comments_count ?? $post->comments->count();
    $userVoteOnPost = auth()->check()
        ? ($post->relationLoaded('votes') ? $post->votes->where('user_id', auth()->id())->first()?->vote_type : null)
        : null;
@endphp

<article id="post-{{ $post->id }}" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">

    <div class="flex items-center justify-between gap-3 mb-2.5">
        <div class="flex items-center gap-2 flex-wrap">
            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                {{ strtoupper(substr($author->name, 0, 2)) }}
            </div>
            @if($author->username)
                <a href="{{ route('profile.show', ['user' => $author->username]) }}"
                   class="text-xs font-bold text-slate-800 hover:text-blue-600 transition-colors">
                    {{ $author->name }}
                </a>
            @else
                <span class="text-xs font-bold text-slate-800">{{ $author->name }}</span>
            @endif

            @if ($isAuthorAdmin)
                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Admin</span>
            @elseif ($isAuthorMod)
                <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">Moderator</span>
            @endif

            <span class="text-[11px] text-slate-400">· {{ $post->created_at->diffForHumans() }}</span>
        </div>

        <div class="flex items-center gap-2">
            @if ($post->community)
                <a href="{{ route('communities.show', $post->community->name) }}"
                class="text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 px-2.5 py-0.5 rounded-lg transition-colors inline-flex items-center gap-1">
                    @if($post->community->is_private)
                        <span title="Komunitas Privat">🔒</span>
                    @endif
                    <span>c/{{ $post->community->name }}</span>
                </a>
            @else
                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-lg">
                    Postingan umum
                </span>
            @endif

            <div x-data="{ open: false }" data-no-nav class="relative" @click.outside="open = false">
                <button type="button" @click="open = !open" aria-label="Menu postingan"
                        class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM11.5 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM19 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                    </svg>
                </button>

                <div x-show="open" x-transition.opacity style="display: none;"
                     class="absolute right-0 mt-1 w-44 z-30 bg-white rounded-xl border border-slate-200 shadow-lg py-1 text-xs">
                    @can('update', $post)
                        <a href="{{ route('posts.edit', $post) }}"
                           class="block px-3.5 py-2 text-slate-700 hover:bg-slate-50">Edit postingan</a>
                    @endcan

                    @can('delete', $post)
                        <form method="POST" action="{{ route('posts.destroy', $post) }}"
                              onsubmit="return confirm('Hapus postingan ini? Semua komentarnya ikut terhapus.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full text-left px-3.5 py-2 text-red-600 hover:bg-red-50 cursor-pointer">
                                Hapus postingan
                            </button>
                        </form>
                    @endcan

                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ route('posts.show', $post) }}'); open = false"
                            class="w-full text-left px-3.5 py-2 text-slate-700 hover:bg-slate-50 cursor-pointer">
                        Salin tautan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($detail)
        <h2 class="text-lg font-bold text-slate-900 mb-1.5">{{ $post->title }}</h2>
        <p class="text-sm text-slate-600 leading-relaxed mb-3 whitespace-pre-line">{{ $post->content }}</p>
    @else
        <h2 class="text-base font-bold text-slate-900 mb-1.5">
            <a href="{{ route('posts.show', $post) }}" class="hover:text-blue-600 transition-colors">{{ $post->title }}</a>
        </h2>
        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-3">
            {{ \Illuminate\Support\Str::limit($post->content, 280) }}
        </p>
    @endif

    <x-post-image :post="$post" />

    <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
        <div class="flex items-center gap-3">
            <x-vote-button
                :post-id="$post->id"
                :score="$score"
                :user-vote="$userVoteOnPost"
            />

            <a href="{{ route('posts.show', $post) }}"
               class="text-slate-500 hover:text-blue-600 font-medium transition-colors">
                <span data-comment-count data-count="{{ $commentsCount }}">💬 {{ $commentsCount }} Komentar</span>
            </a>
        </div>
        <x-save-post-button :post="$post" />
    </div>

</article>
