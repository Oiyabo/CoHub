@php
    $depth = $depth ?? 0;
@endphp

<div class="mt-3 {{ $depth > 0 ? 'ml-4 sm:ml-6 border-l-2 border-slate-200 pl-3' : '' }}"
     data-comment-id="{{ $comment->id }}">

   <div class="bg-slate-50 p-3 rounded-xl border border-slate-100" data-comment-body>
        @if($comment->deleted_at)
            <p class="text-xs font-semibold text-slate-400">
                [komentar telah dihapus]
            </p>
        @else
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="text-xs font-bold text-slate-700">
                    {{ $comment->user->name }}
                </span>

                @if($comment->user->isAdmin())
                    <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1 py-0.5 rounded">
                        Admin
                    </span>
                @elseif($comment->user->isModeratorOf($comment->post->community_id))
                    <span class="text-[9px] font-bold text-purple-700 bg-purple-50 px-1 py-0.5 rounded">
                        Moderator
                    </span>
                @endif

                <span class="text-[10px] text-slate-400">
                    · {{ $comment->created_at->diffForHumans() }}
                </span>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                {{ $comment->content }}
            </p>

            @auth
                <div class="flex items-center gap-3 mt-2 flex-wrap">

                    @can('update', $comment)
                        <details>
                            <summary class="text-[11px] text-amber-600 font-semibold cursor-pointer">
                                Edit
                            </summary>

                            <form action="{{ route('comments.update', $comment) }}"
                                  method="POST"
                                  class="mt-2 space-y-2"
                                  data-comment-ajax="update">
                                @csrf
                                @method('PATCH')

                                <textarea name="content"
                                          rows="2"
                                          required
                                          maxlength="5000"
                                          class="w-full text-xs rounded-lg border-slate-200 focus:border-blue-400 focus:ring-blue-400">{{ $comment->content }}</textarea>

                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                                    Simpan Perubahan
                                </button>
                            </form>
                        </details>
                    @endcan

                    @can('delete', $comment)
                        <form action="{{ route('comments.destroy', $comment) }}"
                              method="POST"
                              data-comment-ajax="delete"
                              onsubmit="return confirm('Yakin ingin menghapus komentar ini?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="text-[11px] text-red-600 font-semibold hover:text-red-700">
                                Hapus
                            </button>
                        </form>
                    @endcan

                </div>

                <details class="mt-2">
                    <summary class="text-[11px] text-blue-600 font-semibold cursor-pointer">
                        Balas
                    </summary>

                    <form action="{{ route('comments.store', $comment->post_id) }}"
                          method="POST"
                          class="mt-2 space-y-2"
                          data-comment-ajax="store">
                        @csrf

                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">

                        <textarea name="content"
                                  rows="2"
                                  required
                                  maxlength="5000"
                                  placeholder="Tulis balasanmu..."
                                  class="w-full text-xs rounded-lg border-slate-200 focus:border-blue-400 focus:ring-blue-400"></textarea>

                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                            Kirim Balasan
                        </button>
                    </form>
                </details>
            @endauth
        @endif

    </div>

    <div data-replies-container>
        @if($comment->replies->isNotEmpty())
            <details class="mt-2" open>
                <summary class="text-[11px] text-slate-500 font-semibold cursor-pointer">
                    {{ $comment->replies->count() }} Balasan
                </summary>

                <div class="mt-2" data-replies-list>
                    @foreach($comment->replies as $reply)
                        @include('comments._comment', [
                            'comment' => $reply,
                            'depth' => $depth + 1
                        ])
                    @endforeach
                </div>
            </details>
        @endif
    </div>

</div>
