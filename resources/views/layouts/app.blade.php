<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'CoHub') }} — Forum Diskusi Komunitas</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body x-data="{
        createPostOpen: false,
        createCommunityOpen: false,
        authModal: {{ request('auth') ? "'" . e(request('auth')) . "'" : ($errors->has('name') ? "'register'" : (session('status') || request()->routeIs('password.*') ? "'forgot'" : ($errors->any() ? "'login'" : 'null'))) }}
    }"
    @open-create-post.window="createPostOpen = true"
    @open-create-community.window="createCommunityOpen = true"
    @open-login.window="authModal = 'login'"
    @open-register.window="authModal = 'register'"
    @open-forgot.window="authModal = 'forgot'"
    @close-auth.window="authModal = null"
    @keydown.escape.window="authModal = null"
    class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- 1. TOP NAVBAR BERSAMA -->
    @include('layouts.navigation')

    <!-- 2. MAIN CONTAINER -->
    <div class="flex-1 max-w-[1300px] w-full mx-auto px-4 py-6">
        <div class="flex items-start gap-5">

            <!-- A. LEFT COMMUNITY RAIL (DINAMIS DARI DATABASE SEEDER) -->
            <aside class="hidden sm:flex flex-col items-center gap-2.5 w-12 shrink-0 sticky top-20 select-none py-1">
                @foreach($globalCommunities as $comm)
                    @php
                        $code = strtoupper(substr($comm->name, 0, 2));
                        $isCurrent = request()->is('c/' . $comm->name . '*');
                        $colors = [
                            'bg-blue-100 text-blue-700',
                            'bg-emerald-100 text-emerald-700',
                            'bg-purple-100 text-purple-700',
                            'bg-amber-100 text-amber-700',
                        ];
                        $colorClass = $colors[$loop->index % count($colors)];
                    @endphp
                    <div class="relative flex items-center">
                        @if($isCurrent)
                            <div class="absolute -left-3 w-1.5 h-6 bg-blue-600 rounded-r-full"></div>
                        @endif
                        <a href="{{ route('communities.show', $comm->name) }}" title="c/{{ $comm->name }}"
                            class="w-10 h-10 rounded-2xl {{ $colorClass }} flex items-center justify-center font-bold text-xs shadow-2xs hover:scale-105 transition-all {{ $isCurrent ? 'ring-2 ring-blue-500' : '' }}">
                            {{ $code }}
                        </a>
                    </div>
                @endforeach

                <!-- Tombol Tambah Komunitas (+) -->
                <button type="button" @click="$dispatch('open-create-community')" title="Buat Komunitas Baru"
                    class="w-10 h-10 rounded-2xl bg-white hover:bg-blue-50 border border-dashed border-slate-300 text-slate-400 hover:text-blue-600 flex items-center justify-center transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            </aside>

            <!-- B. KONTEN TENGAH & SIDEBAR KANAN -->
            <div
                class="flex-1 min-w-0 grid grid-cols-1 {{ (isset($noSidebar) && $noSidebar) || View::hasSection('no-sidebar') ? '' : 'lg:grid-cols-12' }} gap-5 items-start">

                <!-- MAIN CONTENT (Slot) -->
                <main
                    class="{{ (isset($noSidebar) && $noSidebar) || View::hasSection('no-sidebar') ? 'w-full' : 'col-span-1 lg:col-span-8' }} min-w-0">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>

                <!-- RIGHT SIDEBAR -->
                @if(!((isset($noSidebar) && $noSidebar) || View::hasSection('no-sidebar')))
                    <div class="col-span-1 lg:col-span-4 sticky top-20 hidden lg:block">
                        @if(isset($sidebar))
                            {{ $sidebar }}
                        @elseif(View::hasSection('sidebar'))
                            @yield('sidebar')
                        @else
                            @include('layouts.partials.default-sidebar')
                        @endif
                    </div>
                @endif

            </div>

        </div>
    </div>

    <!-- 3. GUEST BOTTOM BANNER -->
    @guest
        <div class="sticky bottom-4 z-40 max-w-xl mx-auto px-4 w-full">
            <div
                class="bg-slate-900 text-white p-3.5 rounded-2xl shadow-lg flex items-center justify-between gap-3 text-xs">
                <div>
                    <span class="font-bold block">Ikut berdiskusi di CoHub</span>
                    <span class="text-slate-400 text-[11px]">Masuk untuk berkomentar dan membuat postingan.</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="authModal = 'login'"
                        class="px-3 py-1 text-slate-200 hover:text-white rounded-lg bg-slate-800 transition-colors cursor-pointer">
                        Masuk
                    </button>
                    <button type="button" @click="authModal = 'register'"
                        class="px-3 py-1 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors cursor-pointer">
                        Daftar
                    </button>
                </div>
            </div>
        </div>
    @endguest

    <!-- 4. MODALS REUSABLE (Alpine.js) -->
    @include('layouts.partials.modals')

</body>

</html>