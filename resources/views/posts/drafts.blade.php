
<x-app-layout>
    <div class="space-y-4">
        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700
                        px-4 py-3 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700
                        px-4 py-3 rounded-xl text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800">
                        Draft Saya
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        Postingan yang kamu simpan dan belum dipublikasikan.
                    </p>
                </div>

                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full">
                    {{ $posts->count() }} Draft
                </span>
            </div>
        </div>

        @forelse($posts as $post)
            <a href="{{ route('posts.edit', $post) }}"
                class="block bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs hover:border-blue-300 hover:shadow-sm transition-all cursor-pointer">

                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-full">
                            DRAFT
                        </span>

                        <h2 class="text-sm font-bold text-slate-800 mt-3">
                            {{ $post->title ?: 'Postingan tanpa judul' }}
                        </h2>

                        <p class="text-xs text-slate-500 mt-1">
                            {{ $post->community
                                ? 'c/'.$post->community->name
                                : 'Postingan umum' }}
                        </p>

                        <p class="text-xs text-slate-600 mt-3 whitespace-pre-line">
                            {{ $post->content }}
                        </p>
                    </div>

                    @if($post->image_path)
                        <img
                            src="{{ $post->image_url }}"
                            alt="Gambar draft"
                            class="w-24 h-24 object-cover rounded-xl border border-slate-100 shrink-0"
                        >
                    @endif
                </div>

                <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100">
                    <span class="text-[10px] text-slate-400">
                        Diperbarui {{ $post->updated_at->diffForHumans() }}
                    </span>
                    <span class="text-[10px] text-blue-600 font-semibold">
                        Klik untuk membuka draft →
                    </span>
                </div>

            </a>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                <div class="text-3xl mb-3">📝</div>

                <h2 class="text-sm font-bold text-slate-700">
                    Belum ada draft
                </h2>

                <p class="text-xs text-slate-400 mt-1">
                    Postingan yang kamu simpan sebagai draft akan muncul di sini.
                </p>
            </div>
        @endforelse

    </div>
</x-app-layout>
