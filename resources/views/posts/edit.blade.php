<x-app-layout>
    <div class="space-y-4">
        <x-flash-status />

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs"
             x-data="{ content: @js(old('content', $post->content)) }">

            <h1 class="text-base font-bold text-slate-800">Edit postingan</h1>
            <p class="text-xs text-slate-400 mt-0.5">c/{{ $post->community->name }}</p>

            <form method="POST" action="{{ route('posts.update', $post) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="title" class="block text-xs font-semibold text-slate-700 mb-1">Judul</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $post->title) }}" maxlength="255"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
                    @error('title')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="content" class="block text-xs font-semibold text-slate-700 mb-1">Isi diskusi</label>
                    <textarea id="content" name="content" rows="6" maxlength="500" x-model="content"
                              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors resize-none"></textarea>
                    @error('content')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <a href="{{ route('posts.show', $post) }}"
                       class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                        Batal
                    </a>
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] text-slate-400 font-mono" x-text="content.length + ' / 500'"></span>
                        <button type="submit"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer">
                            Simpan perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
