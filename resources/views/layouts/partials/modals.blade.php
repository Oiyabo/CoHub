<!-- MODALS GLOBAL (Sesuai Desain PDF Halaman Buat Post & Buat Komunitas) -->

<!-- 1. MODAL BUAT POSTINGAN (PDF 1 Halaman 6) -->
<div x-show="createPostOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
    style="display: none;">

    <div @click.away="createPostOpen = false"
        class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 transform transition-all relative">

        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">Buat postingan</h3>
            <button @click="createPostOpen = false"
                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Form Buat Postingan -->
        <!-- BARU: enctype="multipart/form-data", x-data digabung dengan postImageUpload(), 'image' ditambah di hasAny -->
        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4"
              x-data="{ content: @js(old('content', '')), ...postImageUpload() }"
              x-init="{{ $errors->hasAny(['community_id', 'title', 'content', 'image']) ? 'createPostOpen = true' : '' }}">
            @csrf
            
            <!-- Pilih Komunitas (otomatis dari database) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Pilih komunitas</label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($globalCommunities as $comm)
                        <label class="cursor-pointer">
                            <input type="radio" name="community_id" value="{{ $comm->id }}" class="peer sr-only"
                                   {{ (string) old('community_id', $loop->first ? $comm->id : '') === (string) $comm->id ? 'checked' : '' }}>
                            <span class="inline-block px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 peer-checked:bg-blue-50 peer-checked:text-blue-600 peer-checked:ring-1 peer-checked:ring-blue-500 transition-all">
                                c/{{ $comm->name }}
                            </span>
                        </label>
                    @endforeach
        <!-- Form Dummy / Action Route -->
        <form action="{{ Route::has('posts.store') ? route('posts.store') : url('/posts') }}" method="POST"
            class="mt-4 space-y-4">
            @csrf

            <!-- Pilih Komunitas -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Pilih komunitas</label>
                <div class="flex flex-wrap gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="community_id" value="1" class="peer sr-only" checked>
                        <span
                            class="inline-block px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 peer-checked:bg-blue-50 peer-checked:text-blue-600 peer-checked:ring-1 peer-checked:ring-blue-500 transition-all">
                            c/laravel-indonesia
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="community_id" value="2" class="peer sr-only">
                        <span
                            class="inline-block px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 peer-checked:bg-emerald-50 peer-checked:text-emerald-600 peer-checked:ring-1 peer-checked:ring-emerald-500 transition-all">
                            c/ilkom-unila
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="community_id" value="3" class="peer sr-only">
                        <span
                            class="inline-block px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 peer-checked:bg-purple-50 peer-checked:text-purple-600 peer-checked:ring-1 peer-checked:ring-purple-500 transition-all">
                            c/data-science-ai
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="community_id" value="4" class="peer sr-only">
                        <span
                            class="inline-block px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 peer-checked:bg-amber-50 peer-checked:text-amber-600 peer-checked:ring-1 peer-checked:ring-amber-500 transition-all">
                            c/seputar-kampus
                        </span>
                    </label>
                </div>
                @error('community_id')
                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Judul -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Judul</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="255" required
                       placeholder="Contoh: Cara membuat relasi self-referencing di Laravel" 
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
                @error('title')
                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <input type="text" name="title" placeholder="Contoh: Cara membuat relasi self-referencing di Laravel"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
            </div>

            <!-- Isi Diskusi -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Isi diskusi</label>
                <textarea name="content" rows="4" maxlength="500" required x-model="content"
                          placeholder="Tuliskan pertanyaan, ide, atau pembahasan lengkapmu di sini..." 
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors resize-none"></textarea>
                @error('content')
                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <textarea name="content" rows="4"
                    placeholder="Tuliskan pertanyaan, ide, atau pembahasan lengkapmu di sini..."
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors resize-none"></textarea>
            </div>

            <!-- Lampiran Gambar -->
            <!-- BARU: seluruh blok ini menggantikan blok Gambar yang lama -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gambar</label>
                <input type="file" name="image" x-ref="file"
                       accept="image/png,image/jpeg,image/webp" class="sr-only"
                       @change="pick($event)">

                <div class="flex items-center gap-3">
                    <button type="button" @click="$refs.file.click()" x-show="!preview"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition-colors flex items-center gap-1.5">
                    <button type="button"
                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition-colors flex items-center gap-1.5">
                        <span>+ Tambah</span>
                    </button>
                    <span class="text-[11px] text-slate-400" x-show="!error">PNG, JPG, WEBP, maks 2 MB</span>
                    <span class="text-[11px] text-red-600 font-medium" x-show="error" x-text="error" style="display: none;"></span>
                </div>

                <div x-show="preview" class="relative mt-2 inline-block" style="display: none;">
                    <img :src="preview" alt="Pratinjau gambar" class="max-h-48 rounded-xl border border-slate-200 object-cover">
                    <button type="button" @click="clear()" aria-label="Hapus gambar"
                            class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-slate-800 text-white text-xs flex items-center justify-center hover:bg-red-600 transition-colors">
                        &times;
                    </button>
                </div>

                @error('image')
                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" @click="createPostOpen = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                    Simpan draft
                </button>
                <div class="flex items-center gap-3">
                    <span class="text-[11px] text-slate-400 font-mono" x-text="content.length + ' / 500'">0 / 500</span>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-all active:scale-95">
                    <span class="text-[11px] text-slate-400 font-mono">0 / 500</span>
                    <button type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-all active:scale-95">
                        Posting
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>


<!-- 2. MODAL BUAT KOMUNITAS BARU (Sesuai Desain Gambar "Modal Buat Komunitas Baru.png") -->
<div x-show="createCommunityOpen" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
    style="display: none;" x-data="modalCommunityForm()">

    <div @click.away="createCommunityOpen = false"
        class="bg-white rounded-3xl max-w-xl w-full p-7 sm:p-8 shadow-2xl border border-slate-100 transform transition-all relative">

        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3">
            <h3 class="text-lg font-bold text-slate-900">Buat komunitas baru</h3>
            <button @click="createCommunityOpen = false"
                class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                &times;
            </button>
        </div>

        <!-- Form Buat Komunitas -->
        <form action="{{ route('communities.store') }}" method="POST" enctype="multipart/form-data"
            class="mt-4 space-y-5">
            @csrf

            <!-- Nama Komunitas (Live AJAX Check) -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Nama komunitas</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs text-slate-400">c/</span>
                    <input type="text" name="name" x-model="name" @input.debounce.350ms="checkName()"
                        placeholder="laravel-indonesia" required
                        class="w-full pl-8 pr-32 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">

                    <span x-show="checking" class="absolute right-3 text-[10px] text-slate-400"
                        style="display: none;">Mengecek...</span>
                    <span x-show="!checking && statusText"
                        :class="isAvailable ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                        class="absolute right-3 px-2 py-0.5 rounded-md text-[10px] font-semibold" x-text="statusText"
                        style="display: none;"></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Dicek otomatis saat kamu mengetik.</p>
            </div>

            <!-- Deskripsi -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Deskripsi (opsional)</label>
                <textarea name="description" rows="3"
                    placeholder="Diskusi Laravel, Eloquent, dan arsitektur aplikasi web modern."
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 resize-none"></textarea>
            </div>

            <!-- Banner Komunitas (Dashed Dropzone Sesuai Gambar) -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Banner komunitas</label>
                <label
                    class="border-2 border-dashed border-blue-200 hover:border-blue-500 rounded-2xl p-6 text-center cursor-pointer transition bg-blue-50/20 block">
                    <svg class="w-6 h-6 text-blue-500 mx-auto mb-1.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    <p class="text-xs text-slate-700 font-bold">Seret gambar atau klik untuk unggah</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">PNG/JPG, maks 2 MB</p>
                    <input type="file" name="banner" accept="image/*" class="sr-only">
                </label>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" @click="createCommunityOpen = false"
                    class="px-5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-full bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" :disabled="!isAvailable && name.length >= 3"
                    class="px-6 py-2 rounded-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 text-white font-semibold text-xs shadow-xs transition">
                    Buat Komunitas
                </button>
            </div>

        </form>
    </div>
</div>

<!-- BARU: fungsi pratinjau & validasi gambar untuk form buat postingan -->
<script>
    function postImageUpload() {
        const MAX_BYTES = 2 * 1024 * 1024;
        const ALLOWED = ['image/png', 'image/jpeg', 'image/webp'];

        return {
            preview: null,
            error: '',
            pick(event) {
                const file = event.target.files[0];
                this.error = '';
                if (!file) { this.clear(); return; }

                if (!ALLOWED.includes(file.type)) {
                    this.clear();
                    this.error = 'Format harus PNG, JPG, atau WEBP.';
                    return;
                }
                if (file.size > MAX_BYTES) {
                    this.clear();
                    this.error = 'Ukuran gambar maksimal 2 MB.';
                    return;
                }
                if (this.preview) URL.revokeObjectURL(this.preview);
                this.preview = URL.createObjectURL(file);
            },
            clear() {
                if (this.preview) URL.revokeObjectURL(this.preview);
                this.preview = null;
                this.$refs.file.value = '';
<script>
    function modalCommunityForm() {
        return {
            name: '',
            checking: false,
            isAvailable: true,
            statusText: '',
            checkName() {
                if (this.name.length < 3) { this.statusText = ''; return; }
                this.checking = true;
                fetch(`{{ route('communities.check-name') }}?name=${encodeURIComponent(this.name)}`)
                    .then(r => r.json())
                    .then(d => {
                        this.isAvailable = d.available;
                        this.statusText = d.available ? '✓ Nama tersedia' : '✕ Terpakai';
                    })
                    .finally(() => { this.checking = false; });
            }
        }
    }
</script>