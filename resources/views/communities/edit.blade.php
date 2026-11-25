<x-app-layout>
    <x-slot name="sidebar">
        <!-- SIDEBAR PENGATURAN KOMUNITAS (Sesuai Gambar) -->
        <div class="space-y-4">
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs">
                <h4 class="text-sm font-bold text-slate-800 mb-1.5">Siapa yang bisa mengubah?</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Hanya pembuat komunitas dan Admin yang dapat mengubah pengaturan dan mengangkat moderator.
                </p>
            </div>

            <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-3xl p-6 text-white shadow-sm">
                <h3 class="text-base font-bold">Punya topik sendiri?</h3>
                <p class="text-xs text-blue-100 mt-1.5 leading-relaxed">
                    Buat komunitasmu dan angkat moderator pilihanmu.
                </p>
                <button type="button" @click="$dispatch('open-create-community')"
                    class="mt-4 px-5 py-2.5 bg-white text-blue-600 hover:bg-blue-50 font-bold text-xs rounded-full shadow-xs transition-all cursor-pointer">
                    Buat Komunitas
                </button>
            </div>
        </div>
    </x-slot>

    <div x-data="{ 
            currentTab: new URLSearchParams(window.location.search).get('tab') || 'umum',
            deleteModalOpen: false,
            emailInput: '',
            loadingMod: false,
            modAlert: '',
            modAlertSuccess: true,
            moderators: [
                @foreach($community->moderators as $m)
                    {
                        id: {{ $m->id }},
                        name: '{{ addslashes($m->name) }}',
                        initial: '{{ strtoupper(substr($m->name, 0, 2)) }}',
                        date: 'diangkat {{ $m->pivot->created_at ? $m->pivot->created_at->format('d M Y') : '12 Okt 2026' }}'
                    },
                @endforeach
            ],
            rules: @js($community->rules ?? []),
            ruleModalOpen: false,
            isEditingRule: false,
            editingRuleId: null,
            ruleForm: {
                title: '',
                description: ''
            },
            ruleAlert: '',
            loadingRule: false,
            openAddRuleModal() {
                this.isEditingRule = false;
                this.editingRuleId = null;
                this.ruleForm = { title: '', description: '' };
                this.ruleAlert = '';
                this.ruleModalOpen = true;
            },
            openEditRuleModal(rule, index) {
                this.isEditingRule = true;
                this.editingRuleId = rule.id || index;
                this.ruleForm = {
                    title: rule.title || '',
                    description: rule.description || ''
                };
                this.ruleAlert = '';
                this.ruleModalOpen = true;
            },
            submitRule() {
                if (!this.ruleForm.title.trim()) {
                    this.ruleAlert = 'Judul aturan wajib diisi.';
                    return;
                }
                this.loadingRule = true;
                this.ruleAlert = '';
                const url = this.isEditingRule
                    ? `{{ url('/c/' . $community->name . '/rules') }}/${this.editingRuleId}`
                    : `{{ route('communities.rules.add', $community->name) }}`;
                const method = this.isEditingRule ? 'PUT' : 'POST';

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.ruleForm)
                })
                .then(async r => {
                    const d = await r.json();
                    if (!r.ok) throw new Error(d.message || 'Gagal menyimpan aturan.');
                    return d;
                })
                .then(d => {
                    this.rules = d.rules || [];
                    this.ruleModalOpen = false;
                })
                .catch(e => {
                    this.ruleAlert = e.message;
                })
                .finally(() => {
                    this.loadingRule = false;
                });
            },
            removeRule(ruleId, title) {
                if (!confirm(`Hapus aturan '${title}'?`)) return;
                fetch(`{{ url('/c/' . $community->name . '/rules') }}/${ruleId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(async r => {
                    const d = await r.json();
                    if (!r.ok) throw new Error(d.message || 'Gagal menghapus aturan.');
                    return d;
                })
                .then(d => {
                    this.rules = d.rules || [];
                })
                .catch(e => {
                    alert(e.message);
                });
            },
            installDefaultRules() {
                if (this.rules.length > 0 && !confirm('Ganti aturan saat ini dengan 3 aturan rekomendasi standar?')) return;
                fetch(`{{ route('communities.rules.reset-default', $community->name) }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(async r => {
                    const d = await r.json();
                    if (!r.ok) throw new Error(d.message || 'Gagal memasang aturan standar.');
                    return d;
                })
                .then(d => {
                    this.rules = d.rules || [];
                })
                .catch(e => {
                    alert(e.message);
                });
            },
            submitMod() {
                this.loadingMod = true;
                this.modAlert = '';
                fetch('{{ route('communities.moderators.add', $community->name) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ email: this.emailInput })
                })
                .then(async r => { const d = await r.json(); if(!r.ok) throw new Error(d.message); return d; })
                .then(d => {
                    this.modAlertSuccess = true;
                    this.modAlert = d.message;
                    this.moderators.push({ id: d.user.id, name: d.user.name, initial: d.user.initial, date: 'diangkat Baru saja' });
                    this.emailInput = '';
                })
                .catch(e => { this.modAlertSuccess = false; this.modAlert = e.message; })
                .finally(() => { this.loadingMod = false; });
            },
            removeMod(id, name) {
                if(!confirm(`Copot hak moderator dari ${name}?`)) return;
                fetch(`{{ url('/c/' . $community->name . '/moderators') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(async r => { const d = await r.json(); if(!r.ok) throw new Error(d.message); return d; })
                .then(d => {
                    this.modAlertSuccess = true;
                    this.modAlert = d.message;
                    this.moderators = this.moderators.filter(m => m.id !== id);
                })
                .catch(e => { this.modAlertSuccess = false; this.modAlert = e.message; });
            }
         }" class="space-y-5">

        <!-- HEADER PENGATURAN DENGAN TOMBOL KEMBALI -->
        <div class="flex items-center gap-3">
            <a href="{{ route('communities.show', $community->name) }}"
                class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition shadow-2xs">
                &larr;
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Komunitas</h1>
                <p class="text-xs text-slate-400">c/{{ $community->name }}</p>
            </div>
        </div>

        <!-- 4 TAB SWITCHER (Umum, Tentang, Moderator, Aturan) -->
        <div class="bg-slate-100 p-1 rounded-2xl inline-flex gap-1 text-xs font-semibold select-none flex-wrap">
            <button type="button" @click="currentTab = 'umum'"
                :class="currentTab === 'umum' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all cursor-pointer">
                Umum
            </button>
            <button type="button" @click="currentTab = 'tentang'"
                :class="currentTab === 'tentang' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all cursor-pointer">
                Tentang
            </button>
            <button type="button" @click="currentTab = 'moderator'"
                :class="currentTab === 'moderator' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all cursor-pointer">
                Moderator
            </button>
            <button type="button" @click="currentTab = 'aturan'"
                :class="currentTab === 'aturan' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all cursor-pointer">
                Aturan (<span x-text="rules.length"></span>)
            </button>
        </div>

        <!-- ================= TAB 1: UMUM ================= -->
        <div x-show="currentTab === 'umum'" class="space-y-4">
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <form action="{{ route('communities.update', $community->name) }}" method="POST"
                    enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <!-- Tipe Komunitas (Privasi) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Komunitas (Privasi)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ isPrivate: '{{ old('is_private', $community->is_private ? '1' : '0') }}' }">
                            <!-- Publik -->
                            <label :class="isPrivate === '0' ? 'border-blue-500 bg-blue-50/30 ring-1 ring-blue-500' : 'border-slate-200 bg-slate-50'"
                                class="flex items-start gap-3 p-3.5 border rounded-2xl cursor-pointer transition">
                                <input type="radio" name="is_private" value="0" x-model="isPrivate"
                                    class="mt-0.5 text-blue-600 focus:ring-0">
                                <div>
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                        <span>🌐 Publik</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-normal">Siapa saja bisa melihat, membaca postingan, dan bergabung.</p>
                                </div>
                            </label>

                            <!-- Privat -->
                            <label :class="isPrivate === '1' ? 'border-blue-500 bg-blue-50/30 ring-1 ring-blue-500' : 'border-slate-200 bg-slate-50'"
                                class="flex items-start gap-3 p-3.5 border rounded-2xl cursor-pointer transition">
                                <input type="radio" name="is_private" value="1" x-model="isPrivate"
                                    class="mt-0.5 text-blue-600 focus:ring-0">
                                <div>
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                        <span>🔒 Privat</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-normal">Hanya anggota yang terdaftar yang dapat melihat postingan dan berdiskusi.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Nama Komunitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama komunitas</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-xs text-slate-400">c/</span>
                            <input type="text" name="name" value="{{ old('name', $community->name) }}" required
                                class="w-full pl-8 pr-20 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                            <span
                                class="absolute right-3 px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-md text-[10px] font-semibold">
                                ✓ Unik
                            </span>
                        </div>
                    </div>
                    <!-- Kategori Komunitas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Kategori komunitas
                            </label>

                            <select name="category" required
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                                <option value="">Pilih kategori</option>
                                @foreach (['Teknologi', 'Akademik', 'Kampus', 'Data', 'Karier'] as $category)
                                    <option value="{{ $category }}"
                                        @selected(old('category', $community->category) === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>

                            @error('category')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi</label>
                        <textarea name="description" rows="3"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 resize-none">{{ old('description', $community->description) }}</textarea>
                    </div>

                    <!-- Avatar / Logo Komunitas -->
                    <div x-data="editAvatarUpload('{{ $community->avatar_url }}')">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Avatar / Logo komunitas</label>

                        <input type="file" name="avatar" x-ref="editAvatarInput"
                            accept="image/png,image/jpeg,image/webp" class="sr-only"
                            @change="handleSelect($event)">

                        <div class="flex items-center gap-4">
                            <!-- Preview Box -->
                            <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-700 font-bold text-lg border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center relative shadow-xs">
                                <template x-if="currentPreview">
                                    <img :src="currentPreview" alt="Avatar" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!currentPreview">
                                    <span>{{ strtoupper(substr($community->name, 0, 2)) }}</span>
                                </template>
                            </div>

                            <!-- Upload Dashed Box / Dropzone -->
                            <div
                                @click="$refs.editAvatarInput.click()"
                                @dragover.prevent="isDragging = true"
                                @dragenter.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop($event)"
                                :class="isDragging ? 'border-blue-500 bg-blue-50/70' : 'border-slate-200 hover:border-blue-400 bg-slate-50/50 hover:bg-blue-50/20'"
                                class="flex-1 border-2 border-dashed rounded-2xl p-3.5 text-center cursor-pointer transition">
                                <div class="pointer-events-none">
                                    <span class="text-xs text-slate-600 font-semibold block">&uarr; Ganti avatar / logo</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">PNG, JPG, WEBP, maks 2 MB</span>
                                </div>
                            </div>
                        </div>

                        <div x-show="fileName" class="flex items-center justify-between text-xs text-slate-600 mt-2 px-1" style="display: none;">
                            <span class="truncate" x-text="fileName + ' (' + fileSize + ')'"></span>
                            <button type="button" @click="resetAvatar()" class="text-rose-600 hover:underline shrink-0 ml-2">Batal ganti</button>
                        </div>

                        <p x-show="error" x-text="error" class="text-xs text-rose-600 font-medium mt-1.5" style="display: none;"></p>
                        @error('avatar')
                            <p class="text-xs text-rose-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Banner Komunitas (Drag & Drop + Preview) -->
                    <div x-data="editBannerUpload('{{ $community->banner_url }}')">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Banner komunitas</label>
                        
                        <input type="file" name="banner" x-ref="editBannerInput"
                            accept="image/png,image/jpeg,image/webp" class="sr-only"
                            @change="handleSelect($event)">

                        <div class="flex items-center gap-4">
                            <!-- Preview Box -->
                            <div
                                class="w-32 h-18 rounded-2xl bg-blue-100/70 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-xs text-slate-400 relative">
                                <template x-if="currentPreview">
                                    <img :src="currentPreview" alt="Banner" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!currentPreview">
                                    <span>No banner</span>
                                </template>
                            </div>

                            <!-- Upload Dashed Box / Dropzone -->
                            <div
                                @click="$refs.editBannerInput.click()"
                                @dragover.prevent="isDragging = true"
                                @dragenter.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop($event)"
                                :class="isDragging ? 'border-blue-500 bg-blue-50/70 ring-2 ring-blue-300/40' : 'border-slate-200 hover:border-blue-400 bg-slate-50/50 hover:bg-blue-50/20'"
                                class="flex-1 border-2 border-dashed rounded-2xl p-4 text-center cursor-pointer transition">
                                <div class="pointer-events-none">
                                    <span class="text-xs text-slate-600 font-semibold block">&uarr; Ganti banner / Seret gambar ke sini</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">PNG, JPG, WEBP, maks 2 MB</span>
                                </div>
                            </div>
                        </div>

                        <div x-show="fileName" class="flex items-center justify-between text-xs text-slate-600 mt-2 px-1" style="display: none;">
                            <span class="truncate" x-text="fileName + ' (' + fileSize + ')'"></span>
                            <button type="button" @click="resetBanner()" class="text-rose-600 hover:underline shrink-0 ml-2">Batal ganti</button>
                        </div>

                        <p x-show="error" x-text="error" class="text-xs text-rose-600 font-medium mt-1.5" style="display: none;"></p>
                        @error('banner')
                            <p class="text-xs text-rose-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-3">
                        <a href="{{ route('communities.show', $community->name) }}"
                            class="px-5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-6 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition">
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone: Hapus Komunitas (Sesuai Gambar Bawah) -->
            @can('delete', $community)
                <div class="bg-rose-50/60 border border-rose-100 rounded-3xl p-5 flex items-center justify-between gap-4">
                    <div>
                        <h4 class="text-xs font-bold text-rose-700">Hapus komunitas</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Semua postingan dan komentar ikut terhapus permanen.
                        </p>
                    </div>
                    <button type="button" @click="deleteModalOpen = true"
                        class="px-5 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold text-xs rounded-full transition shrink-0 cursor-pointer">
                        Hapus
                    </button>
                </div>
            @endcan
        </div>

        <!-- ================= TAB: TENTANG ================= -->
        <div x-show="currentTab === 'tentang'" style="display: none;" class="space-y-4">
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <div class="mb-5 pb-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Tentang Komunitas</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Informasi mendalam mengenai tujuan, visi, panduan, dan tautan resmi komunitas ini.</p>
                </div>

                @if(session('success') && request('tab') === 'tentang')
                    <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center gap-2">
                        <span>✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('communities.about.update', $community->name) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <!-- Deskripsi Lengkap / Cerita Komunitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Deskripsi Lengkap / Cerita Komunitas
                        </label>
                        <textarea name="content" rows="5"
                            placeholder="Tuliskan cerita lengkap tentang komunitas ini, latar belakang dibentuknya, topik diskusi yang dibahas, dan siapa saja yang cocok bergabung..."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 leading-relaxed resize-none">{{ old('content', is_array($community->about) ? ($community->about['content'] ?? $community->about['description'] ?? '') : ($community->about ?? $community->description)) }}</textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Teks ini akan menjadi isi utama di tab "Tentang" pada halaman komunitas.</p>
                        @error('content')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Pedoman & Nilai-nilai Komunitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pedoman & Nilai Komunitas <span class="font-normal text-slate-400">(Opsional)</span>
                        </label>
                        <textarea name="guidelines" rows="3"
                            placeholder="Contoh: Menjunjung tinggi rasa saling menghargai, terbuka terhadap pemula, dan fokus pada kolaborasi positif..."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 leading-relaxed resize-none">{{ old('guidelines', is_array($community->about) ? ($community->about['guidelines'] ?? '') : '') }}</textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Panduan umum kultur dan etika interaksi bagi setiap anggota.</p>
                        @error('guidelines')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tautan Eksternal & Sosial -->
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-xs font-bold text-slate-800 mb-3">Tautan Eksternal & Sosial (Opsional)</h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Website Resmi -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Website Resmi / Blog</label>
                                <input type="url" name="website"
                                    value="{{ old('website', is_array($community->about) ? ($community->about['website'] ?? '') : '') }}"
                                    placeholder="https://contoh-komunitas.com"
                                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                                @error('website')
                                    <p class="text-[10px] text-rose-600 mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Server Discord -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Server Discord</label>
                                <input type="url" name="discord"
                                    value="{{ old('discord', is_array($community->about) ? ($community->about['discord'] ?? '') : '') }}"
                                    placeholder="https://discord.gg/kode-invite"
                                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                                @error('discord')
                                    <p class="text-[10px] text-rose-600 mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Repositori GitHub / Medsos -->
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">GitHub / Forum / Akun Media Sosial</label>
                                <input type="url" name="github"
                                    value="{{ old('github', is_array($community->about) ? ($community->about['github'] ?? '') : '') }}"
                                    placeholder="https://github.com/organisasi-komunitas"
                                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                                @error('github')
                                    <p class="text-[10px] text-rose-600 mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('communities.show', ['community' => $community->name, 'tab' => 'tentang']) }}"
                            class="px-5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                            Lihat Halaman
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                            Simpan Informasi Tentang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= TAB 2: MODERATOR ================= -->
        <div x-show="currentTab === 'moderator'" style="display: none;" class="space-y-4">
            <!-- Form Angkat Moderator -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Angkat moderator baru</h3>
                <form @submit.prevent="submitMod()" class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute left-3.5 top-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="email" x-model="emailInput" placeholder="Masukkan email pengguna..." required
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                    </div>
                    <button type="submit" :disabled="loadingMod"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 text-white font-semibold text-xs rounded-xl shadow-xs transition">
                        <span x-show="!loadingMod">Angkat</span>
                        <span x-show="loadingMod" style="display: none;">...</span>
                    </button>
                </form>

                <div x-show="modAlert" :class="modAlertSuccess ? 'text-emerald-600' : 'text-rose-600'"
                    class="text-xs font-semibold mt-2" x-text="modAlert" style="display: none;"></div>
            </div>

            <!-- Daftar Moderator Aktif -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 mb-4">
                    Moderator aktif (<span x-text="moderators.length"></span>)
                </h3>

                <div class="divide-y divide-slate-100">
                    <template x-for="mod in moderators" :key="mod.id">
                        <div class="py-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs"
                                    x-text="mod.initial"></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800" x-text="mod.name"></p>
                                    <p class="text-[10px] text-slate-400" x-text="mod.date"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-3 py-1 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">Moderator</span>
                                <button type="button" @click="removeMod(mod.id, mod.name)"
                                    class="px-3.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 hover:bg-rose-100 text-rose-600 transition">
                                    Copot
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ================= TAB 3: ATURAN ================= -->
        <div x-show="currentTab === 'aturan'" style="display: none;" class="space-y-4">
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Aturan Komunitas</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Aturan ini akan ditampilkan di sidebar dan halaman tentang komunitas untuk menjaga ketertiban diskusi.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openAddRuleModal()"
                            class="px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Tambah Aturan</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State jika belum ada aturan -->
                <div x-show="rules.length === 0" class="text-center py-10 px-4 rounded-2xl bg-slate-50/70 border-2 border-dashed border-slate-200">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2.5 font-bold text-base">
                        📜
                    </div>
                    <h4 class="text-xs font-bold text-slate-800">Belum ada aturan khusus</h4>
                    <p class="text-[11px] text-slate-400 mt-1 max-w-sm mx-auto">
                        Tentukan tata tertib komunitas agar anggota dapat berdiskusi dengan nyaman dan teratur.
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-2.5 flex-wrap">
                        <button type="button" @click="openAddRuleModal()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-full shadow-xs transition cursor-pointer">
                            + Buat Aturan Pertama
                        </button>
                        <button type="button" @click="installDefaultRules()"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-xs rounded-full shadow-xs transition cursor-pointer">
                            Pasang 3 Aturan Standar
                        </button>
                    </div>
                </div>

                <!-- Numbered Rules (Real Data) -->
                <div x-show="rules.length > 0" class="space-y-2.5">
                    <template x-for="(rule, index) in rules" :key="rule.id || index">
                        <div class="p-4 rounded-2xl bg-slate-50 hover:bg-slate-50/80 border border-slate-100 flex items-start justify-between gap-3 transition">
                            <div class="flex items-start gap-3 min-w-0">
                                <span
                                    class="w-6 h-6 rounded-full font-bold text-xs flex items-center justify-center shrink-0 mt-0.5"
                                    :class="[
                                        'bg-blue-100 text-blue-700',
                                        'bg-emerald-100 text-emerald-700',
                                        'bg-amber-100 text-amber-700',
                                        'bg-purple-100 text-purple-700'
                                    ][index % 4]"
                                    x-text="index + 1"></span>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-800 break-words" x-text="rule.title"></h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug break-words" x-show="rule.description" x-text="rule.description"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-400 shrink-0">
                                <button type="button" @click="openEditRuleModal(rule, index)" title="Edit aturan"
                                    class="p-1.5 rounded-lg hover:bg-slate-200/60 hover:text-slate-700 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="removeRule(rule.id || index, rule.title)" title="Hapus aturan"
                                    class="p-1.5 rounded-lg hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ================= MODAL TAMBAH / EDIT ATURAN ================= -->
        <div x-show="ruleModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;">
            <div @click.away="ruleModalOpen = false"
                class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900" x-text="isEditingRule ? 'Edit Aturan Komunitas' : 'Tambah Aturan Baru'"></h3>
                    <button type="button" @click="ruleModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg leading-none cursor-pointer">
                        &times;
                    </button>
                </div>

                <div x-show="ruleAlert" class="mt-3 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl" x-text="ruleAlert" style="display: none;"></div>

                <form @submit.prevent="submitRule()" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Aturan <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="ruleForm.title" maxlength="120" required
                            placeholder="Contoh: Tetap sesuai topik"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition">
                        <p class="text-[10px] text-slate-400 mt-1">Maksimal 120 karakter.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi / Penjelasan <span class="font-normal text-slate-400">(Opsional)</span></label>
                        <textarea x-model="ruleForm.description" rows="3" maxlength="500"
                            placeholder="Jelaskan secara singkat batasan atau maksud dari aturan ini..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="ruleModalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-full transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" :disabled="loadingRule"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold text-xs rounded-full shadow-xs transition cursor-pointer flex items-center gap-1.5">
                            <span x-show="!loadingRule" x-text="isEditingRule ? 'Simpan Perubahan' : 'Tambah Aturan'"></span>
                            <span x-show="loadingRule" style="display: none;">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL HAPUS KOMUNITAS (Sesuai Gambar "Modal Hapus Komunitas") ================= -->
        <div x-show="deleteModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;">
            <div @click.away="deleteModalOpen = false"
                class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
                <h3 class="text-base font-bold text-slate-900">Hapus c/{{ $community->name }}?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Seluruh postingan dan komentar di komunitas ini ikut terhapus permanen. Tindakan ini tidak dapat
                    dibatalkan.
                </p>
                <div class="flex items-center justify-end gap-2.5 mt-5">
                    <button type="button" @click="deleteModalOpen = false"
                        class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-full transition">
                        Batal
                    </button>
                    <form action="{{ route('communities.destroy', $community->name) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-full shadow-xs transition">
                            Ya, hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function editAvatarUpload(initialUrl) {
                return {
                    currentPreview: initialUrl || null,
                    initialUrl: initialUrl || null,
                    fileName: '',
                    fileSize: '',
                    error: '',
                    isDragging: false,
                    validateFile(file) {
                        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
                        if (!allowedTypes.includes(file.type)) {
                            this.error = 'Format harus PNG, JPG, JPEG, atau WEBP.';
                            return false;
                        }
                        if (file.size > 2 * 1024 * 1024) {
                            this.error = 'Ukuran file maksimal 2 MB.';
                            return false;
                        }
                        this.error = '';
                        return true;
                    },
                    setPreview(file) {
                        if (this.currentPreview && this.currentPreview !== this.initialUrl) {
                            URL.revokeObjectURL(this.currentPreview);
                        }
                        this.currentPreview = URL.createObjectURL(file);
                        this.fileName = file.name;
                        this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                    },
                    handleDrop(event) {
                        this.isDragging = false;
                        const files = event.dataTransfer?.files;
                        if (files && files.length > 0) {
                            const file = files[0];
                            if (!this.validateFile(file)) return;
                            const input = this.$refs.editAvatarInput;
                            if (input) {
                                try {
                                    const dt = new DataTransfer();
                                    dt.items.add(file);
                                    input.files = dt.files;
                                } catch (e) {
                                    input.files = files;
                                }
                            }
                            this.setPreview(file);
                        }
                    },
                    handleSelect(event) {
                        const file = event.target.files?.[0];
                        if (file) {
                            if (!this.validateFile(file)) return;
                            this.setPreview(file);
                        }
                    },
                    resetAvatar() {
                        if (this.currentPreview && this.currentPreview !== this.initialUrl) {
                            URL.revokeObjectURL(this.currentPreview);
                        }
                        this.currentPreview = this.initialUrl;
                        this.fileName = '';
                        this.fileSize = '';
                        this.error = '';
                        if (this.$refs.editAvatarInput) {
                            this.$refs.editAvatarInput.value = '';
                        }
                    }
                };
            }

            function editBannerUpload(initialUrl) {
                return {
                    currentPreview: initialUrl || null,
                    initialUrl: initialUrl || null,
                    fileName: '',
                    fileSize: '',
                    error: '',
                    isDragging: false,
                    validateFile(file) {
                        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
                        if (!allowedTypes.includes(file.type)) {
                            this.error = 'Format harus PNG, JPG, JPEG, atau WEBP.';
                            return false;
                        }
                        if (file.size > 2 * 1024 * 1024) {
                            this.error = 'Ukuran gambar maksimal 2 MB.';
                            return false;
                        }
                        this.error = '';
                        return true;
                    },
                    setPreview(file) {
                        if (this.currentPreview && this.currentPreview !== this.initialUrl) {
                            URL.revokeObjectURL(this.currentPreview);
                        }
                        this.currentPreview = URL.createObjectURL(file);
                        this.fileName = file.name;
                        this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                    },
                    handleDrop(event) {
                        this.isDragging = false;
                        const files = event.dataTransfer?.files;
                        if (files && files.length > 0) {
                            const file = files[0];
                            if (!this.validateFile(file)) return;
                            const input = this.$refs.editBannerInput;
                            if (input) {
                                try {
                                    const dt = new DataTransfer();
                                    dt.items.add(file);
                                    input.files = dt.files;
                                } catch (e) {
                                    input.files = files;
                                }
                            }
                            this.setPreview(file);
                        }
                    },
                    handleSelect(event) {
                        const file = event.target.files?.[0];
                        if (file) {
                            if (!this.validateFile(file)) return;
                            this.setPreview(file);
                        }
                    },
                    resetBanner() {
                        if (this.currentPreview && this.currentPreview !== this.initialUrl) {
                            URL.revokeObjectURL(this.currentPreview);
                        }
                        this.currentPreview = this.initialUrl;
                        this.fileName = '';
                        this.fileSize = '';
                        this.error = '';
                        if (this.$refs.editBannerInput) {
                            this.$refs.editBannerInput.value = '';
                        }
                    }
                };
            }
        </script>
    </div>
</x-app-layout>