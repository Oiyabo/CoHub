<x-app-layout>
    <div class="space-y-4">
        <x-flash-status />

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs"
             x-data="{ content: @js(old('content', $post->content)) }">

            <h1 class="text-base font-bold text-slate-800">Edit postingan</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $post->community ? 'c/'.$post->community->name : 'Postingan umum' }}</p>

            <form method="POST" action="{{ route('posts.update', $post) }}" enctype="multipart/form-data"
                  class="mt-4 space-y-4">
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

                <!-- Gambar -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gambar</label>

                    @if ($post->image_url)
                        <div class="mb-2 inline-block">
                            <img src="{{ $post->image_url }}" alt="Gambar saat ini"
                                 class="max-h-40 rounded-xl border border-slate-200 object-cover">
                        </div>
                        <label class="flex items-center gap-2 text-[11px] text-slate-500 mb-2 cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">
                            Hapus gambar saat ini
                        </label>
                    @endif

                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                           class="block w-full text-xs text-slate-500 file:mr-3 file:px-3.5 file:py-2 file:rounded-xl file:border file:border-slate-200 file:bg-slate-100 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    <p class="text-[11px] text-slate-400 mt-1">
                        {{ $post->image_url ? 'Pilih file baru untuk mengganti gambar. ' : '' }}PNG, JPG, WEBP, maks 2 MB
                    </p>
                    @error('image')
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
