<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6" x-data="moderatorManager()">

        <!-- HEADER KELOLA MODERATOR -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Kelola Moderator c/{{ $community->name }}</h1>
                    <p class="text-xs text-slate-500 mt-1">Moderator dapat menyaring atau menghapus postingan & komentar
                        yang melanggar aturan.</p>
                </div>
                <a href="{{ route('communities.show', $community->name) }}"
                    class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 rounded-xl transition-colors">
                    &larr; Kembali
                </a>
            </div>

            <!-- FORM TAMBAH MODERATOR DENGAN AJAX FETCH -->
            <form @submit.prevent="submitAddModerator()" class="mt-6 pt-5 border-t border-slate-100">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tambah Moderator Baru</label>
                <div class="flex items-center gap-2">
                    <input type="email" x-model="emailInput" placeholder="Masukkan alamat email pengguna..." required
                        class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors">
                    <button type="submit" :disabled="loading"
                        class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:bg-slate-300 text-white font-semibold text-xs rounded-xl transition-colors shrink-0">
                        <span x-show="!loading">+ Jadikan Mod</span>
                        <span x-show="loading" style="display: none;">Memproses...</span>
                    </button>
                </div>
                <!-- Notifikasi Toast / Pesan Feedback -->
                <div x-show="alertMessage"
                    :class="alertSuccess ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                    class="mt-3 p-3 rounded-xl border text-xs font-medium flex items-center justify-between"
                    style="display: none;">
                    <span x-text="alertMessage"></span>
                    <button type="button" @click="alertMessage = ''"
                        class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                </div>
            </form>
        </div>

        <!-- DAFTAR MODERATOR SAAT INI (REAL-TIME LIST) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-2xs">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">
                Daftar Moderator Aktif (<span x-text="moderatorList.length"></span>)
            </h2>

            <div class="divide-y divide-slate-100">
                <template x-for="mod in moderatorList" :key="mod.id">
                    <div class="py-3.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs shrink-0"
                                x-text="mod.initial">
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800" x-text="mod.name"></span>
                                    <template x-if="mod.id === {{ $community->created_by ?? 0 }}">
                                        <span
                                            class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">Owner</span>
                                    </template>
                                </div>
                                <span class="text-[11px] text-slate-400" x-text="mod.email"></span>
                            </div>
                        </div>

                        <!-- Tombol Hapus Moderator (Hanya jika bukan Owner) -->
                        <template x-if="mod.id !== {{ $community->created_by ?? 0 }}">
                            <button type="button" @click="removeModerator(mod.id, mod.name)"
                                class="px-2.5 py-1 text-[11px] font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors border border-transparent hover:border-rose-200">
                                Copot
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>

    </div>

    <!-- LOGIKA JAVASCRIPT AJAX UNTUK KELOLA MODERATOR -->
    <script>
        function moderatorManager() {
            return {
                emailInput: '',
                loading: false,
                alertMessage: '',
                alertSuccess: true,
                moderatorList: [
                    @foreach($moderators as $m)
                            {
                            id: {{ $m->id }},
                            name: "{{ addslashes($m->name) }}",
                            email: "{{ addslashes($m->email) }}",
                            initial: "{{ strtoupper(substr($m->name, 0, 2)) }}"
                        },
                    @endforeach
                ],

                // A. Tambah Moderator via AJAX Fetch
                submitAddModerator() {
                    this.loading = true;
                    this.alertMessage = '';

                    fetch("{{ route('communities.moderators.add', $community->name) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ email: this.emailInput })
                    })
                        .then(async res => {
                            const data = await res.json();
                            if (!res.ok) throw new Error(data.message || 'Gagal menambahkan moderator.');
                            return data;
                        })
                        .then(data => {
                            this.alertSuccess = true;
                            this.alertMessage = data.message;
                            this.moderatorList.push(data.user);
                            this.emailInput = '';
                        })
                        .catch(err => {
                            this.alertSuccess = false;
                            this.alertMessage = err.message;
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                // B. Copot Moderator via AJAX Fetch
                removeModerator(userId, userName) {
                    if (!confirm(`Copot hak moderator dari ${userName}?`)) return;

                    fetch(`{{ url('/c/' . $community->name . '/moderators') }}/${userId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                        .then(async res => {
                            const data = await res.json();
                            if (!res.ok) throw new Error(data.message || 'Gagal mencopot moderator.');
                            return data;
                        })
                        .then(data => {
                            this.alertSuccess = true;
                            this.alertMessage = data.message;
                            this.moderatorList = this.moderatorList.filter(item => item.id !== userId);
                        })
                        .catch(err => {
                            this.alertSuccess = false;
                            this.alertMessage = err.message;
                        });
                }
            }
        }
    </script>
</x-app-layout>