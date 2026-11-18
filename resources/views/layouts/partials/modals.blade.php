
<!-- MODALS GLOBAL (Sesuai Desain PDF Halaman Buat Post & Buat Komunitas) -->

<!-- 1. MODAL BUAT POSTINGAN (PDF 1 Halaman 6) -->
<div x-show="createPostOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs p-4 sm:p-6 flex items-start sm:items-center justify-center"
    style="display: none;">

    <div @click.away="createPostOpen = false"
        class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 transform transition-all relative my-auto max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] flex flex-col">

        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
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
        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4 overflow-y-auto flex-1 pr-1 overscroll-contain"
              x-data="{ content: @js(old('content', '')), ...postImageUpload() }"
              x-init="{{ $errors->hasAny(['community_id', 'title', 'content', 'image']) ? 'createPostOpen = true' : '' }}">
            @csrf

            <!-- Pilih Komunitas (Opsional) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">
                    Komunitas <span class="font-normal text-slate-400">(Opsional)</span>
                </label>

                <select name="community_id" x-model="selectedCommunityId"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">

                    <option value="">Postingan umum (tanpa komunitas)</option>

                    @foreach ($globalCommunities as $comm)
                        <option value="{{ $comm->id }}"
                            {{ (string) old('community_id') === (string) $comm->id ? 'selected' : '' }}>
                            c/{{ $comm->name }}
                        </option>
                    @endforeach
                </select>

                <p class="text-[11px] text-slate-400 mt-1">
                    Pilih komunitas jika ingin membagikan postingan di komunitas tertentu.
                </p>

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
            </div>

            <!-- Lampiran Gambar (Drag & Drop + Preview) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gambar (Opsional)</label>
                <input type="file" name="image" x-ref="file"
                       accept="image/png,image/jpeg,image/webp" class="sr-only"
                       @change="pick($event)">

                <div x-show="!preview"
                     @click="$refs.file.click()"
                     @dragover.prevent="isDragging = true"
                     @dragenter.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="drop($event)"
                     :class="isDragging ? 'border-blue-500 bg-blue-50/70 ring-2 ring-blue-300/40' : 'border-slate-200 hover:border-blue-400 bg-slate-50 hover:bg-blue-50/20'"
                     class="border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition-all">
                    <div class="pointer-events-none flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="text-xs font-semibold text-slate-700">Pilih gambar atau seret ke sini</span>
                        <span class="text-[11px] text-slate-400">(PNG, JPG, WEBP, maks 2 MB)</span>
                    </div>
                </div>

                <div x-show="preview" class="relative mt-2 inline-block" style="display: none;">
                    <img :src="preview" alt="Pratinjau gambar" class="max-h-48 rounded-xl border border-slate-200 object-cover">
                    <button type="button" @click="clear()" aria-label="Hapus gambar"
                            class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-slate-800 text-white text-xs flex items-center justify-center hover:bg-red-600 transition-colors">
                        &times;
                    </button>
                </div>

                <p class="text-[11px] text-red-600 font-medium mt-1" x-show="error" x-text="error" style="display: none;"></p>

                @error('image')
                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100 shrink-0">
                <button type="button" @click="createPostOpen = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                    Simpan draft
                </button>
                <div class="flex items-center gap-3">
                    <span class="text-[11px] text-slate-400 font-mono" x-text="content.length + ' / 500'">0 / 500</span>
                    <button type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-all active:scale-95">
                        Posting
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>


<!-- 2. MODAL BUAT KOMUNITAS BARU -->
<div x-show="createCommunityOpen" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs p-4 sm:p-6 flex items-start sm:items-center justify-center"
    style="display: none;" x-data="modalCommunityForm()">

    <div @click.away="createCommunityOpen = false"
        class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 transform transition-all relative my-auto max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] flex flex-col">

        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
            <h3 class="text-base font-bold text-slate-900">Buat komunitas baru</h3>
            <button @click="createCommunityOpen = false"
                class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                &times;
            </button>
        </div>

        <!-- Form Buat Komunitas (Scrollable Body) -->
        <form action="{{ route('communities.store') }}" method="POST" enctype="multipart/form-data"
            class="mt-3 space-y-4 overflow-y-auto flex-1 pr-1 overscroll-contain">
            @csrf

            <!-- IDENTITAS VISUAL: BANNER & AVATAR (TERKUNCI & TIDAK BISA MELEDAK) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-800">Tampilan Visual (Opsional)</label>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden shadow-2xs">
                    <!-- Area Banner (Tinggi Terkunci 110px) -->
                    <div class="relative w-full bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100 overflow-hidden"
                         style="height: 110px; min-height: 110px; max-height: 110px;"
                         @dragover.prevent="isDraggingBanner = true"
                         @dragenter.prevent="isDraggingBanner = true"
                         @dragleave.prevent="isDraggingBanner = false"
                         @drop.prevent="handleBannerDrop($event)">

                        <input type="file" name="banner" x-ref="modalBannerInput"
                            accept="image/png,image/jpeg,image/webp" class="sr-only"
                            @change="handleBannerSelect($event)">

                        <template x-if="bannerPreview">
                            <img :src="bannerPreview" alt="Banner" class="w-full h-full object-cover"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </template>

                        <div x-show="isDraggingBanner"
                            class="absolute inset-0 bg-blue-600/50 backdrop-blur-xs flex items-center justify-center text-white text-xs font-bold z-20"
                            style="display: none;">
                            Lepaskan untuk unggah banner
                        </div>

                        <div class="absolute top-2 right-2 z-10 flex items-center gap-1.5">
                            <button type="button" @click="$refs.modalBannerInput.click()"
                                class="px-2.5 py-1 rounded-lg bg-white/90 hover:bg-white text-slate-700 hover:text-blue-600 text-[10px] font-semibold backdrop-blur-xs shadow-xs border border-white/60 transition cursor-pointer">
                                <span x-text="bannerPreview ? 'Ganti Banner' : '+ Banner'"></span>
                            </button>
                            <button type="button" x-show="bannerPreview" @click="clearBanner()"
                                class="px-2 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-semibold shadow-xs transition cursor-pointer"
                                style="display: none;">
                                Hapus
                            </button>
                        </div>
                    </div>

                    <!-- Area Avatar & Info -->
                    <div class="px-4 pb-3 pt-0">
                        <div class="flex items-end justify-between gap-3 -mt-8 relative z-10">
                            <div class="flex items-end gap-3 min-w-0">
                                <!-- Kotak Avatar (Terkunci 66px x 66px) -->
                                <div class="relative rounded-2xl bg-white p-1 shadow-md border border-slate-200 shrink-0"
                                     style="width: 66px; height: 66px; min-width: 66px; min-height: 66px; max-width: 66px; max-height: 66px;">

                                    <input type="file" name="avatar" x-ref="modalAvatarInput"
                                        accept="image/png,image/jpeg,image/webp" class="sr-only"
                                        @change="handleAvatarSelect($event)">

                                    <div class="w-full h-full rounded-xl overflow-hidden bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg relative group cursor-pointer"
                                         style="width: 100%; height: 100%;"
                                         @click="$refs.modalAvatarInput.click()"
                                         @dragover.prevent="isDraggingAvatar = true"
                                         @dragenter.prevent="isDraggingAvatar = true"
                                         @dragleave.prevent="isDraggingAvatar = false"
                                         @drop.prevent="handleAvatarDrop($event)"
                                         title="Klik untuk memilih logo">

                                        <template x-if="avatarPreview">
                                            <img :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        </template>

                                        <template x-if="!avatarPreview">
                                            <span x-text="name && name.length >= 2 ? name.substring(0, 2).toUpperCase() : 'C/'"></span>
                                        </template>

                                        <div class="absolute inset-0 bg-slate-900/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                             :class="isDraggingAvatar ? 'opacity-100 bg-blue-600/60' : ''">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-0 pb-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-800">Logo</span>
                                        <span class="text-[10px] text-slate-400">· maks 2 MB</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <button type="button" @click="$refs.modalAvatarInput.click()"
                                            class="text-xs font-semibold text-blue-600 hover:underline cursor-pointer">
                                            <span x-text="avatarPreview ? 'Ganti logo' : '+ Unggah logo'"></span>
                                        </button>
                                        <span x-show="avatarPreview" class="text-slate-300">·</span>
                                        <button type="button" x-show="avatarPreview" @click="clearAvatar()"
                                            class="text-xs font-semibold text-rose-600 hover:underline cursor-pointer"
                                            style="display: none;">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p x-show="avatarError" x-text="avatarError" class="text-xs text-rose-600 font-medium mt-1.5" style="display: none;"></p>
                        <p x-show="bannerError" x-text="bannerError" class="text-xs text-rose-600 font-medium mt-1.5" style="display: none;"></p>
                    </div>
                </div>

                @error('avatar')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
                @error('banner')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nama Komunitas (Live AJAX Check) -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Nama komunitas</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs text-slate-400 font-bold">c/</span>
                    <input type="text" name="name" x-model="name" @input.debounce.350ms="checkName()"
                        placeholder="laravel-indonesia" required
                        class="w-full pl-8 pr-32 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">

                    <span x-show="checking" class="absolute right-3 text-[10px] text-slate-400"
                        style="display: none;">Mengecek...</span>
                    <span x-show="!checking && statusText"
                        :class="isAvailable ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                        class="absolute right-3 px-2 py-0.5 rounded-md text-[10px] font-semibold" x-text="statusText"
                        style="display: none;"></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Gunakan huruf kecil, angka, atau strip (-).</p>
            </div>

            <!-- Deskripsi -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Deskripsi (opsional)</label>
                <textarea name="description" rows="2"
                    placeholder="Diskusi Laravel, Eloquent, dan arsitektur aplikasi web modern."
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors resize-none"></textarea>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 shrink-0">
                <button type="button" @click="createCommunityOpen = false"
                    class="px-5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-full bg-slate-100 hover:bg-slate-200 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="!isAvailable && name.length >= 3"
                    class="px-6 py-2 rounded-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                    Buat Komunitas
                </button>
            </div>

        </form>
    </div>
</div>


<!-- Pratinjau & validasi gambar untuk form buat postingan -->
<script>
    function postImageUpload() {
        const MAX_BYTES = 2 * 1024 * 1024;
        const ALLOWED = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];

        return {
            preview: null,
            error: '',
            isDragging: false,
            process(file) {
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
            pick(event) {
                const file = event.target.files?.[0];
                this.process(file);
            },
            drop(event) {
                this.isDragging = false;
                const files = event.dataTransfer?.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    this.process(file);
                    if (this.$refs.file) {
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            this.$refs.file.files = dt.files;
                        } catch (e) {
                            this.$refs.file.files = files;
                        }
                    }
                }
            },
            clear() {
                if (this.preview) URL.revokeObjectURL(this.preview);
                this.preview = null;
                this.error = '';
                if (this.$refs.file) this.$refs.file.value = '';
            },
        };
    }
</script>


<script>
    function modalCommunityForm() {
        return {
            name: '',
            checking: false,
            isAvailable: true,
            statusText: '',
            bannerExpanded: false,

            // Avatar
            isDraggingAvatar: false,
            avatarPreview: null,
            avatarFileName: '',
            avatarFileSize: '',
            avatarError: null,

            // Banner
            isDraggingBanner: false,
            bannerPreview: null,
            bannerFileName: '',
            bannerFileSize: '',
            bannerError: null,

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
            },

            validateImage(file) {
                const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    return { valid: false, message: 'Format harus PNG, JPG, JPEG, atau WEBP.' };
                }
                if (file.size > 2 * 1024 * 1024) {
                    return { valid: false, message: 'Ukuran file maksimal 2 MB.' };
                }
                return { valid: true };
            },

            // Avatar
            handleAvatarDrop(event) {
                this.isDraggingAvatar = false;
                const files = event.dataTransfer?.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    const check = this.validateImage(file);
                    if (!check.valid) {
                        this.avatarError = check.message;
                        this.clearAvatar();
                        return;
                    }
                    this.avatarError = null;
                    const input = this.$refs.modalAvatarInput;
                    if (input) {
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            input.files = dt.files;
                        } catch (e) {
                            input.files = files;
                        }
                    }
                    this.setAvatarPreview(file);
                }
            },
            handleAvatarSelect(event) {
                const file = event.target.files?.[0];
                if (file) {
                    const check = this.validateImage(file);
                    if (!check.valid) {
                        this.avatarError = check.message;
                        this.clearAvatar();
                        return;
                    }
                    this.avatarError = null;
                    this.setAvatarPreview(file);
                }
            },
            setAvatarPreview(file) {
                if (this.avatarPreview) URL.revokeObjectURL(this.avatarPreview);
                this.avatarPreview = URL.createObjectURL(file);
                this.avatarFileName = file.name;
                this.avatarFileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            },
            clearAvatar() {
                if (this.avatarPreview) URL.revokeObjectURL(this.avatarPreview);
                this.avatarPreview = null;
                this.avatarFileName = '';
                this.avatarFileSize = '';
                this.avatarError = null;
                if (this.$refs.modalAvatarInput) this.$refs.modalAvatarInput.value = '';
            },

            // Banner
            handleBannerDrop(event) {
                this.isDraggingBanner = false;
                const files = event.dataTransfer?.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    const check = this.validateImage(file);
                    if (!check.valid) {
                        this.bannerError = check.message;
                        this.clearBanner();
                        return;
                    }
                    this.bannerError = null;
                    const input = this.$refs.modalBannerInput;
                    if (input) {
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            input.files = dt.files;
                        } catch (e) {
                            input.files = files;
                        }
                    }
                    this.setBannerPreview(file);
                }
            },
            handleBannerSelect(event) {
                const file = event.target.files?.[0];
                if (file) {
                    const check = this.validateImage(file);
                    if (!check.valid) {
                        this.bannerError = check.message;
                        this.clearBanner();
                        return;
                    }
                    this.bannerError = null;
                    this.setBannerPreview(file);
                }
            },
            setBannerPreview(file) {
                if (this.bannerPreview) URL.revokeObjectURL(this.bannerPreview);
                this.bannerPreview = URL.createObjectURL(file);
                this.bannerFileName = file.name;
                this.bannerFileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            },
            clearBanner() {
                if (this.bannerPreview) URL.revokeObjectURL(this.bannerPreview);
                this.bannerPreview = null;
                this.bannerFileName = '';
                this.bannerFileSize = '';
                this.bannerError = null;
                if (this.$refs.modalBannerInput) this.$refs.modalBannerInput.value = '';
            }
        }
    }
</script>

<!-- 3. MODALS AUTH (Login, Register, Reset Password Sesuai Desain) -->
@guest
    @include('auth.modals')
@endguest
