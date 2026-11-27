<div x-data="globalReportModal()"
     @open-report.window="open($event.detail)"
     x-show="isOpen"
     x-transition.opacity
     class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
     style="display: none;">

    <div @click.away="close()"
         class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">

        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700"
                          x-text="typeLabel()"></span>
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Laporkan Pelanggaran</h3>
                <p class="text-xs text-slate-500 mt-0.5 truncate max-w-xs" x-text="'Target: ' + targetLabel"></p>
            </div>

            <button type="button" @click="close()"
                    class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                &times;
            </button>
        </div>

        <form @submit.prevent="submit()" class="mt-4 space-y-3.5">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 mb-2">Pilih alasan cepat:</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="preset in presets" :key="preset">
                        <button type="button"
                                @click="reason = preset"
                                :class="reason === preset ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                class="px-2.5 py-1 rounded-full text-[11px] font-medium border transition-colors cursor-pointer"
                                x-text="preset"></button>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alasan laporan</label>
                <textarea x-model="reason"
                          rows="3"
                          required
                          minlength="3"
                          maxlength="255"
                          placeholder="Jelaskan alasan pelanggaran agar dapat ditinjau oleh Admin..."
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-400 focus:bg-white transition-colors resize-none"></textarea>
                <p class="text-[10px] text-slate-400 mt-1 text-right" x-text="reason.length + ' / 255'"></p>
            </div>

            <div x-show="errorMsg" style="display: none;"
                 class="px-3.5 py-2 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"
                 x-text="errorMsg"></div>

            <div x-show="successMsg" style="display: none;"
                 class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-700 font-medium"
                 x-text="successMsg"></div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="close()"
                        class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="submitting || reason.trim().length < 3"
                        class="px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 rounded-xl transition-colors cursor-pointer">
                    <span x-show="!submitting">Kirim Laporan</span>
                    <span x-show="submitting" style="display: none;">Mengirim...</span>
                </button>
            </div>
        </form>
    </div>
</div>

@once
<script>
    window.globalReportModal = function () {
        return {
            isOpen: false,
            targetType: '',
            targetId: null,
            targetLabel: '',
            reason: '',
            submitting: false,
            errorMsg: '',
            successMsg: '',
            presets: [
                'Spam atau iklan',
                'Ujaran kebencian / pelecehan',
                'Konten tidak sesuai topik',
                'Informasi palsu / penipuan',
                'Melanggar aturan komunitas',
            ],

            open(detail) {
                if (!detail || !detail.type || !detail.id) return;
                this.targetType = detail.type;
                this.targetId = detail.id;
                this.targetLabel = detail.label || '';
                this.reason = '';
                this.errorMsg = '';
                this.successMsg = '';
                this.isOpen = true;
            },

            close() {
                this.isOpen = false;
            },

            typeLabel() {
                return {
                    post: 'Postingan',
                    comment: 'Komentar',
                    user: 'Pengguna',
                    community: 'Komunitas',
                }[this.targetType] || 'Konten';
            },

            async submit() {
                if (this.submitting || this.reason.trim().length < 3) return;
                this.submitting = true;
                this.errorMsg = '';
                this.successMsg = '';

                try {
                    const res = await fetch('{{ route('reports.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reportable_type: this.targetType,
                            reportable_id: this.targetId,
                            reason: this.reason.trim(),
                        }),
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        this.successMsg = data.message || 'Laporan berhasil dikirim.';
                        setTimeout(() => { this.close(); }, 1100);
                    } else {
                        const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                        this.errorMsg = firstError || data.message || 'Gagal mengirim laporan.';
                    }
                } catch (err) {
                    this.errorMsg = 'Terjadi kesalahan jaringan saat mengirim laporan.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    };
</script>
@endonce
