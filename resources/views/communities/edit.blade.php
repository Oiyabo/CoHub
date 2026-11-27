<x-app-layout>
    @php
        $initialRules = collect(is_array($community->rules) ? $community->rules : [])->map(function($r, $idx) {
            $text = is_array($r) ? ($r['text'] ?? $r['title'] ?? '') : (string) $r;
            return [
                'id' => is_array($r) ? ($r['id'] ?? (string)($idx + 1)) : (string)($idx + 1),
                'text' => $text,
            ];
        })->values()->all();

        $initialModerators = $community->moderators->map(function($m) use ($community) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'initial' => strtoupper(substr($m->name, 0, 2)),
                'is_owner' => $m->id === $community->created_by,
            ];
        })->values()->all();

        $initialMembers = $community->members->map(function($m) use ($community) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'username' => $m->username ?? '',
                'initial' => strtoupper(substr($m->name, 0, 2)),
                'is_owner' => $m->id === $community->created_by,
            ];
        })->values()->all();
    @endphp

    <x-slot name="sidebar">
        <!-- SIDEBAR PENGATURAN KOMUNITAS - ATURAN & MODERATOR (Independent Scroll Flow) -->
        <div data-sidebar-manager
            x-data="communitySidebarManager()"
            class="space-y-4 cohub-scroll-right pr-2 pb-12">

            <!-- Hidden Inputs to associate with main form via HTML5 form attribute -->
            <input type="hidden" name="rules" :value="JSON.stringify(rules)" form="community-edit-form">
            <template x-for="mod in moderators" :key="mod.id">
                <input type="hidden" name="moderators[]" :value="mod.id" form="community-edit-form">
            </template>

            <!-- ================= CARD 1: ATURAN KOMUNITAS ================= -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-3.5">
                <!-- HEADER ATURAN DENGAN TOMBOL TULISAN "Add default rules" -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <h4 class="text-sm font-bold text-slate-800">Aturan Komunitas</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600"
                            x-text="rules.length"></span>
                    </div>

                    <!-- TOMBOL PASANG ATURAN STANDAR MENGGANTIKAN TOMBOL ADD DI HEADER -->
                    <button type="button" @click="installDefaultRulesInMemory()"
                        title="Pasang 3 aturan rekomendasi default CoHub"
                        class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold text-[11px] rounded-full transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Add default rules</span>
                    </button>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Tata tertib diskusi. Ubah isi aturan langsung pada kolom teks di bawah.
                </p>

                <!-- DAFTAR ATURAN DENGAN INLINE TEXTAREA (NGE-BLEND, TANPA NOMOR, BISA DIPERBESAR) -->
                <div x-show="rules.length > 0" class="space-y-2.5">
                    <template x-for="(rule, index) in rules" :key="rule.id || index">
                        <div class="p-3 rounded-2xl bg-slate-50/80 hover:bg-slate-50 border border-slate-200/70 hover:border-slate-300/80 focus-within:border-blue-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100/70 transition flex items-start gap-2.5">
                            <!-- TEXTAREA: NGE-BLEND, MELIHAT SELURUH ISI, BISA DIPERBESAR -->
                            <textarea x-model="rule.text"
                                rows="2"
                                x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight) + 'px'; })"
                                @input="$el.style.height = 'auto'; $el.style.height = ($el.scrollHeight) + 'px'"
                                placeholder="Tulis isi aturan komunitas..."
                                maxlength="300"
                                style="border: none !important; outline: none !important; box-shadow: none !important;"
                                class="flex-1 min-w-0 bg-transparent border-none p-0 text-xs font-normal text-slate-800 leading-relaxed placeholder-slate-400 focus:outline-none focus:ring-0 focus:border-transparent resize-y"></textarea>

                            <!-- TOMBOL HAPUS ATURAN DENGAN ICON -->
                            <button type="button" @click="removeRuleInMemory(index)" title="Hapus aturan"
                                class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Empty State jika aturan kosong -->
                <div x-show="rules.length === 0" class="text-center py-4 px-3 rounded-2xl bg-slate-50/70 border border-dashed border-slate-200">
                    <p class="text-xs text-slate-400">Belum ada aturan. Klik tombol di bawah untuk menambah.</p>
                </div>

                <!-- TOMBOL ADD DI BAWAH WIDTH GROW (SEUKURAN SATU LIST) -->
                <button type="button" @click="addNewRuleInline()"
                    class="w-full py-2.5 px-4 rounded-2xl border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50/50 hover:bg-blue-50/30 text-slate-600 hover:text-blue-600 font-semibold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah Aturan</span>
                </button>
            </div>

            <!-- ================= CARD 2: MODERATOR KOMUNITAS ================= -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-3.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <h4 class="text-sm font-bold text-slate-800">Moderator Komunitas</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700"
                            x-text="moderators.length"></span>
                    </div>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Pengelola yang membantu menyaring postingan & diskusi. Perubahan disimpan saat tombol simpan ditekan.
                </p>

                <!-- Notifikasi Alert jika ada -->
                <div x-show="modAlert" :class="modAlertSuccess ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'"
                    class="text-[11px] font-medium p-2.5 rounded-xl border leading-tight flex items-center justify-between gap-2" style="display: none;">
                    <span x-text="modAlert"></span>
                    <button type="button" @click="modAlert = ''" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <!-- DAFTAR MODERATOR AKTIF (LIST) -->
                <div class="divide-y divide-slate-100">
                    <template x-for="(mod, index) in moderators" :key="mod.id">
                        <div class="py-2.5 flex items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-7 h-7 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-[11px] shrink-0"
                                    x-text="mod.initial"></div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-bold text-slate-800 truncate" x-text="mod.name"></span>
                                        <template x-if="mod.is_owner">
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Owner</span>
                                        </template>
                                    </div>
                                    <span class="text-[10px] text-slate-400 block truncate" x-text="mod.email"></span>
                                </div>
                            </div>

                            <!-- Tombol Copot (Hanya jika bukan Owner) -->
                            <template x-if="!mod.is_owner">
                                <button type="button" @click="removeModerator(index, mod.name)" title="Hapus dari daftar moderator"
                                    class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- TOMBOL ADD DI BAWAH DENGAN SIZE SEUKURAN SATU LIST (EXPAND & COLLAPSE) -->
                <button type="button" @click="addModOpen = !addModOpen"
                    class="w-full py-2.5 px-4 rounded-2xl border-2 border-dashed border-slate-200 hover:border-purple-400 bg-slate-50/50 hover:bg-purple-50/30 text-slate-600 hover:text-purple-600 font-semibold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <template x-if="!addModOpen">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Tambah Moderator</span>
                        </span>
                    </template>
                    <template x-if="addModOpen">
                        <span class="flex items-center gap-1.5 text-purple-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Tutup Tambah Moderator</span>
                        </span>
                    </template>
                </button>

                <!-- AREA EXPAND KE BAWAH: KOLOM SEARCH & DAFTAR SEMUA MEMBER -->
                <div x-show="addModOpen" x-transition.duration.200ms class="space-y-3 pt-3 border-t border-slate-100" style="display: none;">
                    <!-- KOLOM SEARCH MEMBER -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35"></path>
                            </svg>
                        </span>
                        <input type="text" x-model="memberSearchQuery"
                            placeholder="Cari nama atau email member..."
                            class="w-full pl-9 pr-3 py-2 bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-100 transition">
                    </div>

                    <!-- DAFTAR SEMUA MEMBER DENGAN TOMBOL "Jadikan Mod" (FIT CONTENT DENGAN MAX HEIGHT) -->
                    <div class="max-h-52 overflow-y-auto space-y-1.5 pr-1 divide-y divide-slate-100 custom-member-scroll">
                        <template x-for="member in filteredMembers" :key="member.id">
                            <div class="pt-2 first:pt-0 pb-1.5 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[10px] shrink-0"
                                        x-text="member.initial"></div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <span class="text-xs font-semibold text-slate-800 truncate" x-text="member.name"></span>
                                            <template x-if="member.is_owner">
                                                <span class="px-1 py-0.2 rounded text-[8px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Owner</span>
                                            </template>
                                        </div>
                                        <span class="text-[10px] text-slate-400 truncate block" x-text="member.email"></span>
                                    </div>
                                </div>

                                <!-- Tombol Jadikan Mod atau Status Mod -->
                                <template x-if="isModerator(member.id)">
                                    <span class="px-2 py-0.5 rounded-xl text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 shrink-0 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Sudah Mod</span>
                                    </span>
                                </template>
                                <template x-if="!isModerator(member.id)">
                                    <button type="button" @click="makeModerator(member)"
                                        class="px-2.5 py-1 text-[11px] font-semibold text-purple-700 hover:text-white bg-purple-50 hover:bg-purple-600 rounded-xl border border-purple-200 hover:border-purple-600 transition flex items-center gap-1 shrink-0 cursor-pointer shadow-2xs">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Jadikan Mod</span>
                                    </button>
                                </template>
                            </div>
                        </template>

                        <!-- Empty state jika query search tidak menemukan member di komunitas -->
                        <div x-show="filteredMembers.length === 0" class="text-center py-4 px-2 bg-slate-50 rounded-2xl border border-dashed border-slate-200 space-y-2">
                            <p class="text-xs text-slate-500">Tidak ada member komunitas yang cocok.</p>
                            <button type="button" @click="lookupAndAddFromSearch()" :disabled="loadingMod"
                                class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 disabled:bg-slate-300 text-white font-semibold text-[11px] rounded-xl transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35"></path>
                                </svg>
                                <span x-show="!loadingMod">Cari & Tambah dari Pengguna CoHub</span>
                                <span x-show="loadingMod" style="display: none;">Mencari pengguna...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </x-slot>

    <!-- ================= BAGIAN KIRI: KONTEN UTAMA (SATU KARTU TERPADU) ================= -->
    <div x-data="{ deleteModalOpen: false, isPrivate: '{{ old('is_private', $community->is_private ? '1' : '0') }}' }"
        class="space-y-5 cohub-scroll-left pr-3 pb-12">

        <!-- HEADER PENGATURAN DENGAN TOMBOL KEMBALI & SWITCH PRIVATE/PUBLIC (SATU BARIS DENGAN HEADER) -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <a href="{{ route('communities.show', $community->name) }}"
                    class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition shadow-2xs">
                    &larr;
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Komunitas</h1>
                    <p class="text-xs text-slate-400">c/{{ $community->name }} &bull; Atur identitas, tampilan, profil, aturan, dan moderator</p>
                </div>
            </div>

            <!-- SWITCH PRIVATE-PUBLIC DI HEADER (SATU BARIS DENGAN HEADER) -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-200/90 shadow-2xs">
                    <span class="text-xs font-semibold cursor-pointer select-none transition"
                        @click="isPrivate = '0'"
                        :class="isPrivate === '0' ? 'text-blue-600 font-bold' : 'text-slate-400'">
                        Publik
                    </span>

                    <button type="button"
                        @click="isPrivate = isPrivate === '1' ? '0' : '1'"
                        :class="isPrivate === '1' ? 'bg-amber-500' : 'bg-blue-600'"
                        class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        role="switch" :aria-checked="isPrivate === '1'">
                        <span :class="isPrivate === '1' ? 'translate-x-5' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"></span>
                    </button>

                    <span class="text-xs font-semibold cursor-pointer select-none transition flex items-center gap-1"
                        @click="isPrivate = '1'"
                        :class="isPrivate === '1' ? 'text-amber-600 font-bold' : 'text-slate-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span>Privat</span>
                    </span>
                </div>

                <a href="{{ route('communities.moderation.index', $community->name) }}"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition shadow-2xs">
                    <span>Ruang Moderasi</span>
                    <span class="text-blue-400">&rarr;</span>
                </a>

                <a href="{{ route('communities.show', $community->name) }}"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition shadow-2xs">
                    <span>Lihat Komunitas</span>
                    <span class="text-slate-400">&rarr;</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- ================= FORM UTAMA (SATU CARD UTUH) ================= -->
        <form id="community-edit-form" action="{{ route('communities.update', $community->name) }}" method="POST"
            enctype="multipart/form-data" onsubmit="syncSidebarInputsBeforeSubmit(this)" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Hidden input untuk nilai switch is_private yang ada di header -->
            <input type="hidden" name="is_private" :value="isPrivate">

            <!-- SATU KARTU TERPADU UNTUK SELURUH PENGATURAN KOMUNITAS -->
            <div x-data="communityVisualUpload('{{ $community->avatar_url }}', '{{ $community->banner_url }}')"
                class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-xs space-y-6">

                <!-- 1. TAMPILAN VISUAL (BANNER & PP SEPERTI DI CREATE COMMUNITY, DI PALING ATAS SEBELUM NAMA) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-800">Tampilan Visual</label>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden shadow-2xs">
                        <!-- BANNER AREA -->
                        <div class="relative w-full bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100 overflow-hidden"
                             style="height: 125px; min-height: 125px; max-height: 125px;"
                             @dragover.prevent="isDraggingBanner = true"
                             @dragenter.prevent="isDraggingBanner = true"
                             @dragleave.prevent="isDraggingBanner = false"
                             @drop.prevent="handleBannerDrop($event)">

                            <input type="file" name="banner" x-ref="editBannerInput"
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

                            <div class="absolute top-2.5 right-2.5 z-10 flex items-center gap-1.5">
                                <button type="button" @click="$refs.editBannerInput.click()"
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

                        <!-- AVATAR / PP OVERLAPPING BANNER SEPERTI DI CREATE COMMUNITY -->
                        <div class="px-4 pb-3 pt-0">
                            <div class="flex items-end justify-between gap-3 -mt-9 relative z-10">
                                <div class="flex items-end gap-3 min-w-0">
                                    <div class="relative rounded-2xl bg-white p-1 shadow-md border border-slate-200 shrink-0"
                                         style="width: 72px; height: 72px; min-width: 72px; min-height: 72px; max-width: 72px; max-height: 72px;">

                                        <input type="file" name="avatar" x-ref="editAvatarInput"
                                            accept="image/png,image/jpeg,image/webp" class="sr-only"
                                            @change="handleAvatarSelect($event)">

                                        <div class="w-full h-full rounded-xl overflow-hidden bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg relative group cursor-pointer"
                                             style="width: 100%; height: 100%;"
                                             @click="$refs.editAvatarInput.click()"
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
                                                <span>{{ strtoupper(substr($community->name, 0, 2)) }}</span>
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
                                            <span class="text-xs font-bold text-slate-800">Logo / Avatar Komunitas</span>
                                            <span class="text-[10px] text-slate-400">&bull; maks 2 MB</span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <button type="button" @click="$refs.editAvatarInput.click()"
                                                class="text-xs font-semibold text-blue-600 hover:underline cursor-pointer">
                                                <span x-text="avatarPreview ? 'Ganti logo' : '+ Unggah logo'"></span>
                                            </button>
                                            <span x-show="avatarPreview" class="text-slate-300">&bull;</span>
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

                <!-- 2. NAMA KOMUNITAS & KATEGORI KOMUNITAS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nama Komunitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama komunitas</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-xs text-slate-400 font-semibold">c/</span>
                            <input type="text" name="name" value="{{ old('name', $community->name) }}" required
                                class="w-full pl-8 pr-20 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                            <span
                                class="absolute right-3 px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-md text-[10px] font-semibold flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Unik</span>
                            </span>
                        </div>
                        @error('name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kategori Komunitas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori komunitas</label>
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
                </div>

                <!-- 3. DESKRIPSI SINGKAT -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Ringkas</label>
                    <textarea name="description" rows="2" placeholder="Ringkasan singkat tentang apa topik utama komunitas ini..."
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 resize-none leading-relaxed">{{ old('description', $community->description) }}</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Ditampilkan pada kartu daftar komunitas dan ringkasan header.</p>
                    @error('description')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- PEMISAH ELEGAN -->
                <div class="border-t border-slate-100"></div>

                <!-- 4. DESKRIPSI LENGKAP / CERITA KOMUNITAS (TENTANG) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Deskripsi Lengkap / Cerita Komunitas
                    </label>
                    <textarea name="content" rows="4"
                        placeholder="Tuliskan latar belakang terbentuknya komunitas, topik apa saja yang dibahas, dan siapa saja yang cocok bergabung..."
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 leading-relaxed resize-none">{{ old('content', is_array($community->about) ? ($community->about['content'] ?? $community->about['description'] ?? '') : ($community->about ?? $community->description)) }}</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Isi utama pada tab 'Tentang' dan sidebar informasi komunitas.</p>
                    @error('content')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 5. PEDOMAN & NILAI KOMUNITAS -->
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

                <!-- PEMISAH ELEGAN -->
                <div class="border-t border-slate-100"></div>

                <!-- 6. TAUTAN EKSTERNAL & SOSIAL -->
                <div>
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
            </div>

            <!-- ACTION BUTTONS: SIMPAN SEMUA PERUBAHAN SECARA BERSAMAAN -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('communities.show', $community->name) }}"
                    class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                    Batal
                </a>
                <button type="submit"
                    class="px-7 py-2.5 rounded-full bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                    Simpan Seluruh Perubahan
                </button>
            </div>
        </form>

        <!-- ================= ZONA BERBAHAYA: HAPUS KOMUNITAS ================= -->
        @can('delete', $community)
            <div class="bg-rose-50/60 border border-rose-100 rounded-3xl p-5 flex items-center justify-between gap-4">
                <div>
                    <h4 class="text-xs font-bold text-rose-700">Hapus komunitas</h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">Semua postingan, komentar, dan data komunitas akan dihapus secara permanen.</p>
                </div>
                <button type="button" @click="deleteModalOpen = true"
                    class="px-5 py-2 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold text-xs rounded-full transition shrink-0 cursor-pointer">
                    Hapus
                </button>
            </div>
        @endcan

        <!-- ================= MODAL HAPUS KOMUNITAS ================= -->
        <div x-show="deleteModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;">
            <div @click.away="deleteModalOpen = false"
                class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 transform transition-all text-left">
                <h3 class="text-base font-bold text-slate-900">Hapus c/{{ $community->name }}?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Seluruh postingan dan komentar di komunitas ini ikut terhapus permanen. Tindakan ini tidak dapat
                    dibatalkan.
                </p>
                <div class="flex items-center justify-end gap-2.5 mt-5">
                    <button type="button" @click="deleteModalOpen = false"
                        class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-full transition cursor-pointer">
                        Batal
                    </button>
                    <form action="{{ route('communities.destroy', $community->name) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-full shadow-xs transition cursor-pointer">
                            Ya, hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- SCRIPT UPLOAD GAMBAR DENGAN DRAG & DROP & SYNC DATA FORM -->
        <script>
            function communitySidebarManager() {
                return {
                    rules: @js($initialRules),
                    moderators: @js($initialModerators),
                    allMembers: @js($initialMembers),
                    addModOpen: false,
                    memberSearchQuery: '',
                    loadingMod: false,
                    modAlert: '',
                    modAlertSuccess: true,

                    get filteredMembers() {
                        const q = this.memberSearchQuery.trim().toLowerCase();
                        if (!q) {
                            return this.allMembers;
                        }
                        return this.allMembers.filter(m => {
                            return (m.name && m.name.toLowerCase().includes(q)) ||
                                   (m.email && m.email.toLowerCase().includes(q)) ||
                                   (m.username && m.username.toLowerCase().includes(q));
                        });
                    },

                    isModerator(id) {
                        return this.moderators.some(m => m.id === id);
                    },

                    makeModerator(member) {
                        if (this.isModerator(member.id)) {
                            return;
                        }
                        this.moderators.push({
                            id: member.id,
                            name: member.name,
                            email: member.email,
                            initial: member.initial,
                            is_owner: member.is_owner,
                        });
                        this.modAlertSuccess = true;
                        this.modAlert = member.name + ' berhasil ditambahkan. Klik simpan di bawah untuk menerapkan.';
                    },

                    addNewRuleInline() {
                        this.rules.push({
                            id: String(Date.now()),
                            text: ''
                        });
                        this.$nextTick(() => {
                            const textareas = document.querySelectorAll('.custom-rules-scroll textarea');
                            if (textareas.length > 0) {
                                textareas[textareas.length - 1].focus();
                            }
                        });
                    },

                    removeRuleInMemory(index) {
                        this.rules.splice(index, 1);
                    },

                    installDefaultRulesInMemory() {
                        if (this.rules.length > 0 && !confirm('Ganti aturan saat ini dengan 3 aturan rekomendasi default CoHub?')) return;
                        this.rules = [
                            {
                                id: String(Date.now()) + '1',
                                text: 'Tetap sopan dan hormati sesama anggota komunitas'
                            },
                            {
                                id: String(Date.now()) + '2',
                                text: 'Dilarang melakukan spam, promosi terselubung, dan iklan komersial'
                            },
                            {
                                id: String(Date.now()) + '3',
                                text: 'Pastikan diskusi dan postingan relevan dengan topik komunitas'
                            }
                        ];
                    },

                    lookupAndAddFromSearch() {
                        const query = this.memberSearchQuery.trim();
                        if (!query) {
                            this.modAlert = 'Ketik email atau username pengguna yang ingin dicari.';
                            this.modAlertSuccess = false;
                            return;
                        }

                        this.loadingMod = true;
                        this.modAlert = '';

                        fetch('{{ route('communities.moderators.lookup', $community->name) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: query })
                        })
                        .then(async r => {
                            const d = await r.json();
                            if (!r.ok) throw new Error(d.message || 'Pengguna tidak ditemukan.');
                            return d;
                        })
                        .then(d => {
                            if (this.isModerator(d.user.id)) {
                                throw new Error('Pengguna ini sudah terdaftar sebagai moderator.');
                            }
                            this.moderators.push(d.user);
                            if (!this.allMembers.some(m => m.id === d.user.id)) {
                                this.allMembers.push(d.user);
                            }
                            this.modAlertSuccess = true;
                            this.modAlert = d.user.name + ' berhasil ditambahkan. Klik simpan di bawah untuk menerapkan.';
                            this.memberSearchQuery = '';
                        })
                        .catch(e => {
                            this.modAlertSuccess = false;
                            this.modAlert = e.message;
                        })
                        .finally(() => {
                            this.loadingMod = false;
                        });
                    },

                    removeModerator(index, name) {
                        const mod = this.moderators[index];
                        if (mod && mod.is_owner) {
                            alert('Pembuat komunitas (Owner) tidak dapat dicopot dari peran moderator.');
                            return;
                        }
                        if (!confirm(`Hapus ${name} dari daftar moderator?`)) return;
                        this.moderators.splice(index, 1);
                    }
                };
            }

            function syncSidebarInputsBeforeSubmit(form) {
                const sidebarEl = document.querySelector('[data-sidebar-manager]');
                if (sidebarEl && window.Alpine) {
                    const state = window.Alpine.$data(sidebarEl);
                    if (state) {
                        // Pastikan input rules terisi
                        let rulesInput = form.querySelector('input[name="rules"]');
                        if (!rulesInput) {
                            rulesInput = document.createElement('input');
                            rulesInput.type = 'hidden';
                            rulesInput.name = 'rules';
                            form.appendChild(rulesInput);
                        }
                        rulesInput.value = JSON.stringify(state.rules);

                        // Sinkronisasi moderators array
                        form.querySelectorAll('input[data-sync-mod]').forEach(el => el.remove());
                        state.moderators.forEach(mod => {
                            const modInput = document.createElement('input');
                            modInput.type = 'hidden';
                            modInput.name = 'moderators[]';
                            modInput.value = mod.id;
                            modInput.setAttribute('data-sync-mod', '1');
                            form.appendChild(modInput);
                        });
                    }
                }
            }

            function communityVisualUpload(initialAvatar, initialBanner) {
                const MAX_BYTES = 2 * 1024 * 1024;
                const ALLOWED = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];

                return {
                    initialAvatar: initialAvatar || null,
                    avatarPreview: initialAvatar || null,
                    avatarError: '',
                    isDraggingAvatar: false,

                    initialBanner: initialBanner || null,
                    bannerPreview: initialBanner || null,
                    bannerError: '',
                    isDraggingBanner: false,

                    validateImage(file) {
                        if (!ALLOWED.includes(file.type)) {
                            return { valid: false, message: 'Format harus PNG, JPG, JPEG, atau WEBP.' };
                        }
                        if (file.size > MAX_BYTES) {
                            return { valid: false, message: 'Ukuran file maksimal 2 MB.' };
                        }
                        return { valid: true };
                    },

                    handleAvatarSelect(event) {
                        const file = event.target.files?.[0];
                        if (file) {
                            const res = this.validateImage(file);
                            if (!res.valid) {
                                this.avatarError = res.message;
                                return;
                            }
                            this.avatarError = '';
                            if (this.avatarPreview && this.avatarPreview !== this.initialAvatar) {
                                URL.revokeObjectURL(this.avatarPreview);
                            }
                            this.avatarPreview = URL.createObjectURL(file);
                        }
                    },

                    handleAvatarDrop(event) {
                        this.isDraggingAvatar = false;
                        const files = event.dataTransfer?.files;
                        if (files && files.length > 0) {
                            const file = files[0];
                            const res = this.validateImage(file);
                            if (!res.valid) {
                                this.avatarError = res.message;
                                return;
                            }
                            this.avatarError = '';
                            if (this.avatarPreview && this.avatarPreview !== this.initialAvatar) {
                                URL.revokeObjectURL(this.avatarPreview);
                            }
                            this.avatarPreview = URL.createObjectURL(file);
                            if (this.$refs.editAvatarInput) {
                                try {
                                    const dt = new DataTransfer();
                                    dt.items.add(file);
                                    this.$refs.editAvatarInput.files = dt.files;
                                } catch (e) {
                                    this.$refs.editAvatarInput.files = files;
                                }
                            }
                        }
                    },

                    clearAvatar() {
                        if (this.avatarPreview && this.avatarPreview !== this.initialAvatar) {
                            URL.revokeObjectURL(this.avatarPreview);
                        }
                        this.avatarPreview = null;
                        this.avatarError = '';
                        if (this.$refs.editAvatarInput) {
                            this.$refs.editAvatarInput.value = '';
                        }
                    },

                    handleBannerSelect(event) {
                        const file = event.target.files?.[0];
                        if (file) {
                            const res = this.validateImage(file);
                            if (!res.valid) {
                                this.bannerError = res.message;
                                return;
                            }
                            this.bannerError = '';
                            if (this.bannerPreview && this.bannerPreview !== this.initialBanner) {
                                URL.revokeObjectURL(this.bannerPreview);
                            }
                            this.bannerPreview = URL.createObjectURL(file);
                        }
                    },

                    handleBannerDrop(event) {
                        this.isDraggingBanner = false;
                        const files = event.dataTransfer?.files;
                        if (files && files.length > 0) {
                            const file = files[0];
                            const res = this.validateImage(file);
                            if (!res.valid) {
                                this.bannerError = res.message;
                                return;
                            }
                            this.bannerError = '';
                            if (this.bannerPreview && this.bannerPreview !== this.initialBanner) {
                                URL.revokeObjectURL(this.bannerPreview);
                            }
                            this.bannerPreview = URL.createObjectURL(file);
                            if (this.$refs.editBannerInput) {
                                try {
                                    const dt = new DataTransfer();
                                    dt.items.add(file);
                                    this.$refs.editBannerInput.files = dt.files;
                                } catch (e) {
                                    this.$refs.editBannerInput.files = files;
                                }
                            }
                        }
                    },

                    clearBanner() {
                        if (this.bannerPreview && this.bannerPreview !== this.initialBanner) {
                            URL.revokeObjectURL(this.bannerPreview);
                        }
                        this.bannerPreview = null;
                        this.bannerError = '';
                        if (this.$refs.editBannerInput) {
                            this.$refs.editBannerInput.value = '';
                        }
                    }
                };
            }
        </script>
    </div>

    <!-- CUSTOM STYLING DUAL INDEPENDENT SCROLL (DUA SCROLL TERPISAH KIRI & KANAN) -->
    <style>
        @media (min-width: 1024px) {
            html, body {
                height: 100vh !important;
                overflow: hidden !important;
            }

            .cohub-scroll-left {
                height: calc(100vh - 7rem);
                max-height: calc(100vh - 7rem);
                overflow-y: scroll !important;
                overscroll-behavior: contain;
            }

            .cohub-scroll-right {
                height: calc(100vh - 7rem);
                max-height: calc(100vh - 7rem);
                overflow-y: scroll !important;
                overscroll-behavior: contain;
            }
        }

        /* Styling scrollbar kedua panel agar estetik dan terpisah */
        .cohub-scroll-left,
        .cohub-scroll-right {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        .cohub-scroll-left::-webkit-scrollbar,
        .cohub-scroll-right::-webkit-scrollbar {
            width: 6px;
        }
        .cohub-scroll-left::-webkit-scrollbar-track,
        .cohub-scroll-right::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 9999px;
        }
        .cohub-scroll-left::-webkit-scrollbar-thumb,
        .cohub-scroll-right::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .cohub-scroll-left::-webkit-scrollbar-thumb:hover,
        .cohub-scroll-right::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Custom scrollbar untuk sub-list member di dalam panel expand */
        .custom-member-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .custom-member-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .custom-member-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-member-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .custom-member-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <script>
        if (typeof history !== 'undefined' && 'scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.scrollTo(0, 0);
        document.addEventListener('DOMContentLoaded', function() {
            window.scrollTo(0, 0);
        });
    </script>
</x-app-layout>
