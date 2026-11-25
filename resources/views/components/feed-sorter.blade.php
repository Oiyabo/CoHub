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
                        let id = card.dataset.postId;
                        if (!id) {
                            const link = card.querySelector('a[href*="/posts/"]');
                            const match = link && link.getAttribute('href').match(/\/posts\/(\d+)/);
                            id = match ? match[1] : null;
                        }
                        if (id) cards[id] = card;
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
