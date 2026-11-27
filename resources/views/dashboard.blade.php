<x-app-layout>

    <div class="space-y-4">

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs">
            <div class="flex items-center gap-3">
                @php
                    $initials = strtoupper(substr(Auth::user()->name ?? 'U', 0, 2));
                @endphp
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden">
                    @if(Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display: none;">{{ $initials }}</span>
                    @else
                        <span>{{ $initials }}</span>
                    @endif
                </div>
                <button type="button"
                    @click="$dispatch('open-create-post')"
                    class="flex-1 bg-slate-50 hover:bg-slate-100 text-left text-xs text-slate-400 px-4 py-2.5 rounded-xl border border-slate-200 transition-colors cursor-pointer">
                    Apa yang ingin kamu diskusikan hari ini?
                </button>
            </div>
        </div>

        <x-feed-sorter :current-tab="$tab ?? null" />

        @forelse($posts as $post)
            <x-post-card :post="$post" />
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 text-sm">
                Belum ada postingan dalam komunitas.
            </div>
        @endforelse

    </div>
</x-app-layout>
