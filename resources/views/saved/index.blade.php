<x-app-layout>

    <div class="space-y-4">

        <!-- HEADER -->
        <section class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">

            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">

                    <svg class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 4.75A1.75 1.75 0 017.75 3h8.5A1.75 1.75 0 0118 4.75V21l-6-3.5L6 21V4.75Z">
                        </path>

                    </svg>

                </div>

                <div>
                    <h1 class="text-lg font-extrabold text-slate-900">
                        Tersimpan
                    </h1>

                    <p class="text-xs text-slate-400">
                        Kumpulan postingan yang kamu simpan.
                    </p>
                </div>

            </div>
        </section>

        <!-- PESAN SUKSES -->
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <!-- DAFTAR POSTINGAN -->
        <div class="space-y-4">

            @forelse($posts as $post)

                <x-post-card :post="$post" />

            @empty

                <!-- EMPTY STATE -->
                <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">

                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">

                        <svg class="w-7 h-7"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M6 4.75A1.75 1.75 0 017.75 3h8.5A1.75 1.75 0 0118 4.75V21l-6-3.5L6 21V4.75Z">
                            </path>

                        </svg>

                    </div>

                    <h2 class="text-sm font-bold text-slate-700 mb-1">
                        Belum Ada Postingan Tersimpan
                    </h2>

                    <p class="text-xs text-slate-400 mb-5">
                        Simpan postingan menarik agar mudah ditemukan kembali.
                    </p>

                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors">

                        Jelajahi Beranda

                    </a>

                </div>

            @endforelse

            <!-- PAGINATION -->
            @if($posts->hasPages())
                <div class="pt-3">
                    {{ $posts->links() }}
                </div>
            @endif

        </div>

    </div>

</x-app-layout>