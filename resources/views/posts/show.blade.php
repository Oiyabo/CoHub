<x-app-layout>
    <div class="space-y-4">
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
                <p class="text-xs text-slate-400">c/{{ $post->community->name }}</p>
            </div>
        </div>

        <!-- Isi postingan lengkap -->
        <x-post-card :post="$post" :detail="true" />

        <!-- Komentar: pakai comments/thread.blade.php kalau sudah ada, kalau belum pakai tampilan sementara -->
        @includeFirst(['comments.thread', 'posts.partials.comments-fallback'], ['post' => $post])
    </div>
</x-app-layout>
