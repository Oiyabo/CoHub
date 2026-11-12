{{--
    Tab feed (Untuk Anda / Terbaru / Teratas) + daftar kartu post.
    Klik tab -> ambil potongan HTML dari route posts.feed lewat fetch (tanpa reload).

    Pemakaian di beranda:  <x-feed-tabs :posts="$posts" />
--}}
@props(['posts', 'tab' => 'untuk-anda'])

<div class="space-y-4"
     x-data="{
        tab: '{{ $tab }}',
        loading: false,
        async load(next) {
            if (this.loading || next === this.tab) return;
            this.loading = true;
            try {
                const res = await fetch('{{ route('posts.feed') }}?tab=' + encodeURIComponent(next), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('Gagal memuat feed');
                this.$refs.list.innerHTML = await res.text();
                this.tab = next;
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        }
     }">

    <div class="flex items-center gap-1 bg-slate-200/60 rounded-xl p-1 w-fit text-xs font-semibold">
        @foreach (['untuk-anda' => 'Untuk Anda', 'terbaru' => 'Terbaru', 'teratas' => 'Teratas'] as $key => $label)
            <button type="button" @click="load('{{ $key }}')"
                    :class="tab === '{{ $key }}' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                    class="px-3.5 py-1.5 rounded-lg transition-all cursor-pointer">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div x-ref="list" :class="loading ? 'opacity-50 pointer-events-none' : ''" class="space-y-4 transition-opacity">
        @include('posts.partials.feed-list', ['posts' => $posts])
    </div>
</div>
