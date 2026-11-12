{{--
    Tampilan komentar sementara (hanya baca, datar).
    Otomatis dipakai kalau view `comments.thread` belum ada.
    Begitu modul komentar bersarang jadi dan membuat resources/views/comments/thread.blade.php,
    view itu yang akan dipakai oleh halaman detail post.
--}}
@php($comments = $post->comments()->with('user')->oldest()->get())

<div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
    <h3 class="text-sm font-bold text-slate-800 mb-3">Komentar ({{ $comments->count() }})</h3>

    @forelse ($comments as $comment)
        <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100 mb-2 {{ $comment->parent_id ? 'ml-4 sm:ml-6 border-l-2 border-l-blue-400' : '' }}">
            <div class="flex items-center gap-1.5 mb-1">
                <span class="font-bold text-slate-700">{{ $comment->user->name }}</span>
                <span class="text-[10px] text-slate-400">· {{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-slate-600 leading-normal">{{ $comment->content }}</p>
        </div>
    @empty
        <p class="text-xs text-slate-400">Belum ada komentar.</p>
    @endforelse
</div>
