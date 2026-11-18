<x-app-layout>
    {{-- data-post-id dipakai script komentar AJAX (resources/js/app.js) untuk menemukan kartu post --}}
    <div class="space-y-4" data-post-id="{{ $post->id }}">
        <x-flash-status />

        <!-- Header halaman -->
        <div class="flex items-center gap-3">
            <a href="{{ Auth::check() ? route('dashboard') : url('/') }}" aria-label="Kembali"
               class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Postingan</h1>
                <p class="text-xs text-slate-400">
                    @if ($post->community)
                        c/{{ $post->community->name }}
                    @else
                        Postingan umum
                    @endif
                </p>
            </div>
        </div>

        <!-- Isi postingan lengkap -->
        <x-post-card :post="$post" :detail="true" />

        <!-- Komentar bertingkat -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">
                Komentar:
            </span>

            @auth
                <form action="{{ route('comments.store', $post) }}"
                      method="POST"
                      class="mb-3 space-y-2"
                      data-comment-ajax="store">
                    @csrf

                    <textarea name="content"
                              rows="2"
                              required
                              maxlength="5000"
                              placeholder="Tulis komentarmu..."
                              class="w-full text-xs rounded-lg border-slate-200 focus:border-blue-400 focus:ring-blue-400"></textarea>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                        Kirim Komentar
                    </button>
                </form>
            @endauth

            <div data-comments-list class="space-y-2">
                @if($post->commentTree->isNotEmpty())
                    @include('components.comment-tree', [
                        'comments' => $post->commentTree,
                        'depth' => 0
                    ])
                @else
                    <p class="text-xs text-slate-400" data-empty-comments>
                        Belum ada komentar.
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
