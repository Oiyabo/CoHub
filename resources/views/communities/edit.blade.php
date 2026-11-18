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

        <!-- 3 TAB SWITCHER (Umum, Moderator, Aturan) -->
        <div class="bg-slate-100 p-1 rounded-2xl inline-flex gap-1 text-xs font-semibold select-none">
            <button type="button" @click="currentTab = 'umum'"
                :class="currentTab === 'umum' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all">
                Umum
            </button>
            <button type="button" @click="currentTab = 'moderator'"
                :class="currentTab === 'moderator' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all">
                Moderator
            </button>
            <button type="button" @click="currentTab = 'aturan'"
                :class="currentTab === 'aturan' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-4 py-2 rounded-xl transition-all">
                Aturan
            </button>
        </div>

        <!-- ================= TAB 1: UMUM ================= -->
        <div x-show="currentTab === 'umum'" class="space-y-4">
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs">
                <form action="{{ route('communities.update', $community->name) }}" method="POST"
                    enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')

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

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi</label>
                        <textarea name="description" rows="3"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 resize-none">{{ old('description', $community->description) }}</textarea>
                    </div>

                    <!-- Banner Komunitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Banner komunitas</label>
                        <div class="flex items-center gap-4">
                            <!-- Preview Box -->
                            <div
                                class="w-32 h-18 rounded-2xl bg-blue-100/70 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-xs text-slate-400">
                                @if($community->banner_path)
                                    <img src="{{ $community->banner_url }}" alt="Banner" class="w-full h-full object-cover">
                                @else
                                    <span>No banner</span>
                                @endif
                            </div>

                            <!-- Upload Dashed Box -->
                            <label
                                class="flex-1 border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-2xl p-4 text-center cursor-pointer transition">
                                <span class="text-xs text-slate-600 font-semibold block">&uarr; Ganti banner</span>
                                <input type="file" name="banner" accept="image/*" class="sr-only">
                            </label>
                        </div>
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
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Aturan Komunitas</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Aturan ini akan ditampilkan di sidebar komunitas untuk
                            menjaga ketertiban diskusi.</p>
                    </div>
                    <button type="button"
                        class="px-4 py-2 rounded-full bg-blue-600 text-white font-semibold text-xs shadow-xs hover:bg-blue-700 transition">
                        + Tambah Aturan
                    </button>
                </div>

                <!-- Numbered Rules Sesuai Gambar -->
                <div class="space-y-2.5">
                    <div class="p-3.5 rounded-2xl bg-slate-50 flex items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0">1</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Tetap sesuai topik</h4>
                                <p class="text-[11px] text-slate-500">Diskusi harus berkaitan dengan topik dan ekosistem
                                    terkait.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-400">
                            <button class="hover:text-slate-600">✏️</button>
                            <button class="hover:text-rose-600">🗑️</button>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 flex items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">2</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Hormati sesama anggota</h4>
                                <p class="text-[11px] text-slate-500">Gunakan bahasa yang santun, dilarang menyerang
                                    pribadi atau melecehkan.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-400">
                            <button class="hover:text-slate-600">✏️</button>
                            <button class="hover:text-rose-600">🗑️</button>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 flex items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-bold text-xs flex items-center justify-center shrink-0">3</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Dilarang spam & iklan</h4>
                                <p class="text-[11px] text-slate-500">Dilarang mempromosikan produk/jasa komersial tanpa
                                    izin moderator.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-400">
                            <button class="hover:text-slate-600">✏️</button>
                            <button class="hover:text-rose-600">🗑️</button>
                        </div>
                    </div>
                </div>
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

    </div>
</x-app-layout>