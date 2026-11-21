@props(['post'])
@auth
    @php
        $isSaved = auth()->user()
            ->savedPosts
            ->contains('id', $post->id);
    @endphp

    <div data-no-nav>
        @if($isSaved)
            <form action="{{ route('saved.destroy', $post) }}"
                  method="POST"
                  class="inline-flex">
                @csrf
                @method('DELETE')

                <button type="submit"
                    title="Hapus dari Tersimpan"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">

                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 3a2 2 0 0 0-2 2v16l8-4 8 4V5a2 2 0 0 0-2-2H6z"/>
                    </svg>

                    Tersimpan
                </button>
            </form>
        @else
            <form action="{{ route('saved.store', $post) }}"
                  method="POST"
                  class="inline-flex">
                @csrf

                <button type="submit"
                    title="Simpan postingan"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-500 bg-slate-100 hover:bg-blue-50 hover:text-blue-600 transition-colors">

                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M6 4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17l-6-4-6 4V4z"/>
                    </svg>

                    Simpan
                </button>
            </form>
        @endif
    </div>
@endauth