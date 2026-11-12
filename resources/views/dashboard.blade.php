<x-app-layout>
    <!-- MAIN FEED DASHBOARD (Menampilkan Data Nyata dari Seeder) -->
    <div class="space-y-4">
        
        <!-- 1. KOTAK INPUT CEPAT BUAT POST -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                </div>
                <button type="button" 
                        @click="$dispatch('open-create-post')"
                        class="flex-1 bg-slate-50 hover:bg-slate-100 text-left text-xs text-slate-400 px-4 py-2.5 rounded-xl border border-slate-200 transition-colors cursor-pointer">
                    Apa yang ingin kamu diskusikan hari ini?
                </button>
            </div>
        </div>

        <!-- 2. DAFTAR POSTINGAN NYATA DARI DATABASE SEEDER -->
        <x-feed-sorter />
        @forelse($posts as $post)
            <article class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs cursor-pointer hover:border-blue-300 transition-colors"
                     onclick="if (!event.target.closest('a, button, form, [data-no-nav]') && !window.getSelection().toString()) window.location.href = '{{ route('posts.show', $post) }}'">
                
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
                        <span class="text-xs font-bold text-slate-800">{{ $post->user->name }}</span>
                        
                        @if($isAuthorAdmin)
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">Admin</span>
                        @elseif($isAuthorMod)
                            <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded border border-purple-200">Moderator</span>
                        @endif

                        <span class="text-[11px] text-slate-400">· {{ $post->created_at->diffForHumans() }}</span>
                    </div>

                    <!-- Kanan: badge komunitas + menu titik tiga -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-lg">
                            c/{{ $post->community->name }}
                        </span>

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

                <!-- Judul & Konten Postingan -->
                <h2 class="text-base font-bold text-slate-900 mb-1.5">
                    <a href="{{ route('posts.show', $post) }}" class="hover:text-blue-600 transition-colors">
                        {{ $post->title }}
                    </a>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-3">
                    {{ $post->content }}
                </p>

                <x-post-image :post="$post" />

                <!-- Actions: Skor Vote & Jumlah Komentar -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                    <div class="flex items-center gap-3">
                        <!-- Widget Upvote / Downvote -->
                        <div class="flex items-center bg-slate-100 text-slate-700 rounded-lg px-2.5 py-1 font-bold">
                            <span class="text-blue-600">▲</span>
                            <span class="mx-1.5 font-mono">{{ $post->score }}</span>
                            <span class="text-slate-400">▼</span>
                        </div>

                        <!-- Jumlah Komentar -->
                        <a href="{{ route('posts.show', $post) }}" class="text-slate-500 hover:text-blue-600 font-medium">
                            💬 {{ $post->comments->count() }} Komentar
                        </a>
                    </div>
                </div>

                <!-- DAFTAR KOMENTAR NYATA DARI SEEDER -->
                @if($post->comments->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-slate-100 space-y-2 pl-1">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Komentar:</span>
                        @foreach($post->comments as $comment)
                            <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100 {{ $comment->parent_id ? 'ml-4 sm:ml-6 border-l-2 border-l-blue-400' : '' }}">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="font-bold text-slate-700">{{ $comment->user->name }}</span>
                                    @if($comment->user->isAdmin())
                                        <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1 py-0.2 rounded">Admin</span>
                                    @elseif($comment->user->isModeratorOf($post->community_id))
                                        <span class="text-[9px] font-bold text-purple-700 bg-purple-50 px-1 py-0.2 rounded">Moderator</span>
                                    @endif
                                    <span class="text-[10px] text-slate-400">· {{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-slate-600 leading-normal">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

            </article>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 text-sm">
                Belum ada postingan dalam komunitas.
            </div>
        @endforelse

    </div>
</x-app-layout>