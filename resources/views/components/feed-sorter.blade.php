{{--
    Tab urutan feed (Untuk Anda / Terbaru / Teratas) untuk kartu yang SUDAH ada di halaman.

    Cara kerja: klik tab -> minta urutan ID postingan ke route posts.feed.order (AJAX, JSON),
    lalu kartu <article> yang sudah ada disusun ulang di tempat. Tidak ada reload halaman
    dan tidak ada markup kartu yang diubah, jadi aman dipasang di halaman bersama.

    Pemakaian: letakkan satu baris  <x-feed-sorter />  tepat di atas daftar kartu.
    Syarat: kartu <article> adalah anak langsung dari pembungkus yang sama dengan komponen ini,
    dan tiap kartu punya tautan ke /posts/{id}.
--}}
<script>
    window.feedSorter = function () {
        return {
            tab: 'untuk-anda',
            loading: false,
            async load(next) {
                if (this.loading || next === this.tab) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route('posts.feed.order') }}?tab=' + encodeURIComponent(next), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('Gagal memuat urutan feed');
                    const data = await res.json();

                    const parent = this.$root.parentElement;
                    const cards = {};
                    parent.querySelectorAll(':scope > article').forEach((card) => {
                        const link = card.querySelector('a[href*="/posts/"]');
                        const match = link && link.getAttribute('href').match(/\/posts\/(\d+)/);
                        if (match) cards[match[1]] = card;
                    });

                    data.ids.forEach((id) => {
                        if (cards[id]) parent.appendChild(cards[id]);
                    });
                    this.tab = next;
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
        };
    };
</script>

<div x-data="feedSorter()"
     class="flex items-center gap-1 bg-slate-200/60 rounded-xl p-1 w-fit text-xs font-semibold">
    @foreach (['untuk-anda' => 'Untuk Anda', 'terbaru' => 'Terbaru', 'teratas' => 'Teratas'] as $key => $label)
        <button type="button" @click="load('{{ $key }}')"
                :class="tab === '{{ $key }}' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-3.5 py-1.5 rounded-lg transition-all cursor-pointer">
            {{ $label }}
        </button>
    @endforeach
</div>
