
<x-app-layout>
    <!-- GUEST / PUBLIC FEED -->
    <div class="space-y-4">

        <!-- Header Banner Sederhana -->
        <div class="bg-blue-600 rounded-2xl p-6 text-white shadow-xs">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight">
                CoHub (Community Hub)
            </h1>
            <p class="text-xs sm:text-sm text-blue-100 mt-1 leading-relaxed">
                Platform forum diskusi berbasis komunitas dengan hak akses peran (Admin, Moderator, User, Guest) dan balasan bertingkat.
            </p>
        </div>

        <!-- DAFTAR POSTINGAN -->
        @forelse($posts as $post)
            <article class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">

                <!-- Author Header -->
                <div class="flex items-center justify-between gap-3 mb-2.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        @php
                            $userInitial = strtoupper(substr($post->user->name, 0, 2));
                            $isAuthorAdmin = $post->user->isAdmin();
                            $isAuthorMod = $post->user->isModeratorOf($post->community_id);
                        @endphp

                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                            {{ $userInitial }}
                        </div>

                        <span class="text-xs font-bold text-slate-800">
                            {{ $post->user->name }}
                        </span>

                        @if($isAuthorAdmin)
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                Admin
                            </span>
                        @elseif($isAuthorMod)
                            <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">
                                Moderator
                            </span>
                        @endif

                        <span class="text-[11px] text-slate-400">
                            · {{ $post->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-lg">
                        c/{{ $post->community->name }}
                    </span>
                </div>

                <!-- Judul & Konten Postingan -->
                <h2 class="text-base font-bold text-slate-900 mb-1.5">
                    {{ $post->title }}
                </h2>

                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-3">
                    {{ $post->content }}
                </p>

                <!-- Actions: Skor Vote & Jumlah Komentar -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center bg-slate-100 text-slate-700 rounded-lg px-2.5 py-1 font-bold">
                            <span class="text-blue-600">▲</span>
                            <span class="mx-1.5 font-mono">{{ $post->score }}</span>
                            <span class="text-slate-400">▼</span>
                        </div>

                        <span class="text-slate-500 font-medium">
                            💬 {{ $post->comments->count() }} Komentar
                        </span>
                    </div>
                </div>

                <!-- DAFTAR KOMENTAR BERTINGKAT -->
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">
                        Komentar:
                    </span>

                    @auth
                        <form action="{{ route('comments.store', $post) }}"
                              method="POST"
                              class="mb-3 space-y-2">
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

                    @if($post->comments->whereNull('parent_id')->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($post->comments->whereNull('parent_id') as $comment)
                                @include('comments._comment', [
                                    'comment' => $comment,
                                    'depth' => 0
                                ])
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400">
                            Belum ada komentar.
                        </p>
                    @endif
                </div>

            </article>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 text-sm">
                Belum ada postingan dalam komunitas.
            </div>
        @endforelse

    </div>
</x-app-layout>
