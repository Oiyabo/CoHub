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

<body class="bg-slate-100 text-slate-800 antialiased min-h-screen relative selection:bg-blue-500 selection:text-white">

    <!-- Mock Background / CoHub App Feed Background (Simulates App Underneath) -->
    <div class="fixed inset-0 pointer-events-none select-none filter blur-[4px] opacity-70 scale-[1.01] transform" aria-hidden="true">
        <!-- Top bar mock -->
        <header class="h-16 bg-white/90 border-b border-slate-200 px-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-xs">Co</div>
                <div class="h-4 w-20 bg-slate-300 rounded-md"></div>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-8 w-16 bg-slate-100 rounded-xl"></div>
                <div class="h-8 w-16 bg-blue-600 rounded-xl"></div>
            </div>
        </header>

        <!-- Feed content mock -->
        <div class="max-w-[1300px] mx-auto px-4 py-6 flex gap-5">
            <div class="w-12 hidden sm:flex flex-col gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100"></div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-100"></div>
                <div class="w-10 h-10 rounded-2xl bg-purple-100"></div>
            </div>
            <div class="flex-1 space-y-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-200"></div>
                        <div class="h-3 w-28 bg-slate-200 rounded"></div>
                    </div>
                    <div class="h-4 w-3/4 bg-slate-200 rounded"></div>
                    <div class="h-3 w-full bg-slate-100 rounded"></div>
                    <div class="h-3 w-2/3 bg-slate-100 rounded"></div>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-200"></div>
                        <div class="h-3 w-24 bg-slate-200 rounded"></div>
                    </div>
                    <div class="h-4 w-1/2 bg-slate-200 rounded"></div>
                    <div class="h-3 w-full bg-slate-100 rounded"></div>
                </div>
            </div>
            <div class="w-80 hidden lg:block space-y-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200 h-48"></div>
            </div>
        </div>
    </div>

    <!-- Backdrop Overlay Darkening (Matches Image Backdrop: bg-slate-900/60 backdrop-blur-sm) -->
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
        style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 1rem;">
        <!-- Backdrop click takes back to home -->
        <a href="{{ url('/') }}" class="fixed inset-0 cursor-default" aria-label="Tutup"></a>

        <!-- Modal Card Container (Exact Match of Screenshot) -->
        <div class="bg-white rounded-[28px] max-w-[420px] w-full p-8 sm:p-9 shadow-2xl border border-slate-100 transform transition-all relative z-10"
            style="width: 100%; max-width: 420px; border-radius: 28px; background-color: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem 2.25rem; position: relative; margin: auto;">
            <!-- Close Button (X) -->
            <a href="{{ url('/') }}"
                class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition text-lg leading-none"
                style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background-color: #f8fafc; border: none; cursor: pointer; color: #94a3b8;"
                title="Tutup">
                &times;
            </a>

            {{ $slot }}
        </div>
    </div>

</body>

</html>
