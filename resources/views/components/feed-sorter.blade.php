@props(['currentTab' => null])

@php
    $currentTab = $currentTab ?? request('tab', 'untuk-anda');
    if (!in_array($currentTab, \App\Support\PostFeed::TABS, true)) {
        $currentTab = 'untuk-anda';
    }
@endphp

<script>
    window.feedSorter = function (initialTab = '{{ $currentTab }}') {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || initialTab;
        return {
            tab: activeTab,
            loading: false,
            init() {
                window.addEventListener('popstate', () => {
                    const params = new URLSearchParams(window.location.search);
                    const tabFromUrl = params.get('tab') || initialTab;
                    if (tabFromUrl !== this.tab) {
                        this.load(tabFromUrl, false);
                    }
                });
            },
            async load(next, updateHistory = true) {
                if (this.loading || (next === this.tab && updateHistory)) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route('posts.feed.order') }}?tab=' + encodeURIComponent(next), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('Gagal memuat urutan feed');
                    const data = await res.json();

                    const parent = this.$root.parentElement;
                    const cards = {};
                    let articles = parent.querySelectorAll(':scope > article');
                    if (articles.length === 0) {
                        articles = parent.querySelectorAll('article[data-post-id]');
                    }

                    articles.forEach((card) => {
                        let id = card.dataset.postId;
                        if (!id) {
                            const link = card.querySelector('a[href*="/posts/"]');
                            const match = link && link.getAttribute('href').match(/\/posts\/(\d+)/);
                            id = match ? match[1] : null;
                        }
                        if (id) cards[id] = card;
                    });

                    if (Array.isArray(data.ids)) {
                        data.ids.forEach((id) => {
                            if (cards[id]) parent.appendChild(cards[id]);
                        });
                    }
                    this.tab = next;

                    if (updateHistory) {
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', next);
                        window.history.pushState({ tab: next }, '', url);
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
        };
    };
</script>

<div x-data="feedSorter('{{ $currentTab }}')"
     class="flex items-center gap-1 bg-slate-200/60 rounded-xl p-1 w-fit text-xs font-semibold">
    @foreach (['untuk-anda' => 'Untuk Anda', 'terbaru' => 'Terbaru', 'teratas' => 'Teratas'] as $key => $label)
        <button type="button"
                @click="load('{{ $key }}')"
                :disabled="loading"
                :class="tab === '{{ $key }}' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                class="px-3.5 py-1.5 rounded-lg transition-all cursor-pointer disabled:opacity-60 disabled:cursor-wait">
            {{ $label }}
        </button>
    @endforeach

    <span x-show="loading" class="text-slate-400 px-1 inline-flex items-center" style="display: none;">
        <svg class="animate-spin h-3.5 w-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
    </span>
</div>
