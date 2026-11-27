<x-app-layout>
    <div class="space-y-4">

        <div class="bg-blue-600 rounded-2xl p-6 text-white shadow-xs">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight">
                CoHub (Community Hub)
            </h1>
            <p class="text-xs sm:text-sm text-blue-100 mt-1 leading-relaxed">
                Platform forum diskusi berbasis komunitas dengan hak akses peran (Admin, Moderator, User, Guest) dan
                balasan bertingkat.
            </p>
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
