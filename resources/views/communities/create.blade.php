<x-app-layout>
    <div class="max-w-2xl mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm"
        x-data="communityForm()">

        <div class="pb-4 border-b border-slate-100 mb-6">
            <h1 class="text-lg font-bold text-slate-900">Buat Komunitas Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Ruang diskusi bersama untuk topik favorit atau mata kuliahmu.</p>
        </div>

        <form action="{{ route('communities.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Tipe Komunitas</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ isPrivate: '0' }">
                    <!-- Publik -->
                    <label
                        :class="isPrivate === '0' ? 'border-blue-500 bg-blue-50/30 ring-1 ring-blue-500' : 'border-slate-200 bg-slate-50'"
                        class="flex items-start gap-3 p-3.5 border rounded-2xl cursor-pointer transition">
                        <input type="radio" name="is_private" value="0" x-model="isPrivate"
                            class="mt-0.5 text-blue-600 focus:ring-0">
                        <div>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                <span>🌐 Publik</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-normal">Siapa saja bisa melihat, membaca
                                postingan, dan bergabung.</p>
                        </div>
                    </label>

                    <!-- Privat -->
                    <label
                        :class="isPrivate === '1' ? 'border-blue-500 bg-blue-50/30 ring-1 ring-blue-500' : 'border-slate-200 bg-slate-50'"
                        class="flex items-start gap-3 p-3.5 border rounded-2xl cursor-pointer transition">
                        <input type="radio" name="is_private" value="1" x-model="isPrivate"
                            class="mt-0.5 text-blue-600 focus:ring-0">
                        <div>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                <span>🔒 Privat</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-normal">Hanya anggota yang terdaftar
                                yang dapat melihat isi komunitas.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- NAMA KOMUNITAS (DENGAN AJAX CHECK-NAME) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama komunitas</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs font-semibold text-slate-400">c/</span>
                    <input type="text" name="name" x-model="name" @input.debounce.400ms="checkName()"
                        placeholder="contoh: rekayasa-web" required
                        class="w-full pl-8 pr-32 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">

                    <!-- Status Ketersediaan Nama -->
                    <span x-show="checking" class="absolute right-3 text-[11px] text-slate-400 font-medium"
                        style="display: none;">
                        Mengecek...
                    </span>
                    <span x-show="!checking && statusText"
                        :class="isAvailable ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-rose-50 text-rose-600 border-rose-200'"
                        class="absolute right-3 px-2 py-0.5 border rounded-md text-[10px] font-semibold"
                        x-text="statusText" style="display: none;">
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Gunakan huruf kecil, angka, atau tanda strip (-). Dicek
                    instan via AJAX.</p>
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <!-- KATEGORI KOMUNITAS -->
             <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Kategori komunitas
                </label>

                <select name="category" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
                    <option value="">Pilih kategori</option>
                    @foreach (['Teknologi', 'Akademik', 'Kampus', 'Data', 'Karier'] as $category)
                        <option value="{{ $category }}" @selected(old('category') === $category)>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            <!-- DESKRIPSI -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi (Opsional)</label>
                <textarea name="description" rows="3" placeholder="Tuliskan tujuan komunitas ini..."
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors resize-none">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- IDENTITAS VISUAL KOMUNITAS: BANNER & AVATAR -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-700">Tampilan Visual Komunitas (Opsional)</label>
                <p class="text-[11px] text-slate-400">Pratinjau tampilan banner dan avatar logo komunitasmu.</p>

                <!-- KARTU PREVIEW GAYA TWITTER/DISCORD (UKURAN TERKUNCI & TIDAK BISA MELEDAK) -->
                <div class="rounded-3xl border border-slate-200 bg-slate-50 overflow-hidden shadow-2xs">

                    <!-- 1. AREA BANNER (TINGGI TERKUNCI 130PX) -->
                    <div class="relative w-full bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100 overflow-hidden"
                        style="height: 130px; min-height: 130px; max-height: 130px;"
                        @dragover.prevent="isDraggingBanner = true" @dragenter.prevent="isDraggingBanner = true"
                        @dragleave.prevent="isDraggingBanner = false" @drop.prevent="handleBannerDrop($event)">

                        <!-- Hidden Native Input Banner -->
                        <input type="file" name="banner" x-ref="bannerInput" accept="image/png,image/jpeg,image/webp"
                            class="sr-only" @change="handleBannerSelect($event)">

                        <!-- Gambar Banner Jika Terpilih -->
                        <template x-if="bannerPreview">
                            <img :src="bannerPreview" alt="Banner" class="w-full h-full object-cover"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </template>

                        <!-- Dragging Overlay untuk Banner -->
                        <div x-show="isDraggingBanner"
                            class="absolute inset-0 bg-blue-600/50 backdrop-blur-xs flex items-center justify-center text-white text-xs font-bold z-20"
                            style="display: none;">
                            Lepaskan untuk mengunggah banner
                        </div>

                        <!-- Tombol Aksi Banner (Pojok Kanan Atas) -->
                        <div class="absolute top-2.5 right-2.5 z-10 flex items-center gap-1.5">
                            <button type="button" @click="$refs.bannerInput.click()"
                                class="px-2.5 py-1 rounded-xl bg-white/90 hover:bg-white text-slate-700 hover:text-blue-600 text-[11px] font-semibold backdrop-blur-xs shadow-xs border border-white/60 transition-all flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span x-text="bannerPreview ? 'Ganti Banner' : '+ Banner'"></span>
                            </button>

                            <button type="button" x-show="bannerPreview" @click="clearBanner()"
                                class="px-2 py-1 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-semibold shadow-xs transition-colors cursor-pointer"
                                style="display: none;" title="Hapus Banner">
                                Hapus
                            </button>
                        </div>
                    </div>

                    <!-- 2. AREA AVATAR & INFO (MENGAPUNG DI ATAS BANNER & CARD) -->
                    <div class="px-5 pb-4 pt-0">
                        <div class="flex items-end justify-between gap-3 -mt-9 relative z-10">

                            <div class="flex items-end gap-3.5 min-w-0">
                                <!-- Kotak Avatar Bulat/Rounded Terkunci Ukurannya (76x76px) -->
                                <div class="relative rounded-2xl bg-white p-1 shadow-md border border-slate-200 shrink-0"
                                    style="width: 76px; height: 76px; min-width: 76px; min-height: 76px; max-width: 76px; max-height: 76px;">

                                    <!-- Hidden Native Input Avatar -->
                                    <input type="file" name="avatar" x-ref="avatarInput"
                                        accept="image/png,image/jpeg,image/webp" class="sr-only"
                                        @change="handleAvatarSelect($event)">

                                    <div class="w-full h-full rounded-xl overflow-hidden bg-blue-100 text-blue-700 flex items-center justify-center font-black text-xl relative group cursor-pointer"
                                        style="width: 100%; height: 100%;" @click="$refs.avatarInput.click()"
                                        @dragover.prevent="isDraggingAvatar = true"
                                        @dragenter.prevent="isDraggingAvatar = true"
                                        @dragleave.prevent="isDraggingAvatar = false"
                                        @drop.prevent="handleAvatarDrop($event)"
                                        title="Klik atau seret gambar untuk memilih logo">

                                        <!-- Gambar Avatar Jika Ada -->
                                        <template x-if="avatarPreview">
                                            <img :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </template>

                                        <!-- Inisial Huruf Jika Belum Ada Gambar -->
                                        <template x-if="!avatarPreview">
                                            <span
                                                x-text="name && name.length >= 2 ? name.substring(0, 2).toUpperCase() : 'C/'"></span>
                                        </template>

                                        <!-- Overlay Kamera Saat Hover / Drag -->
                                        <div class="absolute inset-0 bg-slate-900/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                            :class="isDraggingAvatar ? 'opacity-100 bg-blue-600/60' : ''">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Keterangan & Tombol Avatar -->
                                <div class="min-w-0 pb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-800">Logo Komunitas</span>
                                        <span class="text-[10px] text-slate-400">· Maks 2 MB</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <button type="button" @click="$refs.avatarInput.click()"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline cursor-pointer">
                                            <span x-text="avatarPreview ? 'Ganti logo' : '+ Unggah logo'"></span>
                                        </button>
                                        <span x-show="avatarPreview" class="text-slate-300">·</span>
                                        <button type="button" x-show="avatarPreview" @click="clearAvatar()"
                                            class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline cursor-pointer"
                                            style="display: none;">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Info Error jika ada -->
                        <p x-show="avatarError" x-text="avatarError" class="text-xs text-rose-600 font-medium mt-2"
                            style="display: none;"></p>
                        <p x-show="bannerError" x-text="bannerError" class="text-xs text-rose-600 font-medium mt-2"
                            style="display: none;"></p>
                    </div>
                </div>

                @error('avatar')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
                @error('banner')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- BUTTONS -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('communities.index') }}"
                    class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                    Batal
                </a>
                <button type="submit" :disabled="!isAvailable && name.length >= 3"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors">
                    Buat Komunitas Sekarang
                </button>
            </div>
        </form>
    </div>

    <!-- SCRIPT ALPINE AJAX VALIDASI NAMA & DRAG-DROP AVATAR + BANNER -->
    <script>
        function communityForm() {
            return {
                name: '{{ old('name', '') }}',
                checking: false,
                isAvailable: false,
                statusText: '',
                bannerExpanded: {{ $errors->has('banner') ? 'true' : 'false' }},

                // Avatar state
                isDraggingAvatar: false,
                avatarPreview: null,
                avatarFileName: '',
                avatarFileSize: '',
                avatarError: null,

                // Banner state
                isDraggingBanner: false,
                bannerPreview: null,
                bannerFileName: '',
                bannerFileSize: '',
                bannerError: null,

                init() {
                    if (this.name.length >= 3) {
                        this.checkName();
                    }
                },

                checkName() {
                    if (this.name.length < 3) {
                        this.statusText = '';
                        return;
                    }
                    this.checking = true;
                    fetch(`{{ route('communities.check-name') }}?name=${encodeURIComponent(this.name)}`)
                        .then(res => res.json())
                        .then(data => {
                            this.isAvailable = data.available;
                            this.statusText = data.message;
                        })
                        .catch(() => {
                            this.statusText = 'Gagal memeriksa nama';
                        })
                        .finally(() => {
                            this.checking = false;
                        });
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

                // Avatar methods
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
                        const input = this.$refs.avatarInput;
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
                    if (this.$refs.avatarInput) {
                        this.$refs.avatarInput.value = '';
                    }
                },

                // Banner methods
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
                        const input = this.$refs.bannerInput;
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
                    if (this.$refs.bannerInput) {
                        this.$refs.bannerInput.value = '';
                    }
                }
            };
        }
    </script>
</x-app-layout>