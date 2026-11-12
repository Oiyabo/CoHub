<x-app-layout>
    <div class="max-w-2xl mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm"
        x-data="communityForm()">

        <div class="pb-4 border-b border-slate-100 mb-6">
            <h1 class="text-lg font-bold text-slate-900">Buat Komunitas Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Ruang diskusi bersama untuk topik favorit atau mata kuliahmu.</p>
        </div>

        <form action="{{ route('communities.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

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

            <!-- DESKRIPSI -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi (Opsional)</label>
                <textarea name="description" rows="3" placeholder="Tuliskan tujuan komunitas ini..."
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors resize-none">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- BANNER KOMUNITAS -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Banner Gambar (Opsional)</label>
                <input type="file" name="banner" accept="image/png,image/jpeg,image/webp"
                    class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors">
                <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, maksimal 2 MB.</p>
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

    <!-- SCRIPT ALPINE AJAX VALIDASI NAMA REAL-TIME -->
    <script>
        function communityForm() {
            return {
                name: '{{ old('name', '') }}',
                checking: false,
                isAvailable: false,
                statusText: '',
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
                }
            }
        }
    </script>
</x-app-layout>