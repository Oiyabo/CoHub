<nav class="sticky top-0 z-40 w-full bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-2xs">
    <div class="w-full max-w-[1300px] mx-auto px-3 sm:px-5 lg:px-6">
        <div class="flex items-center justify-between h-16 gap-2">

            <div class="flex items-center gap-3 lg:gap-6 min-w-0">

                <a href="{{ url('/') }}" class="flex items-center gap-2.5 shrink-0">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-sm shadow-xs">
                        Co
                    </div>

                    <span class="text-lg font-bold tracking-tight text-slate-900">
                        CoHub
                    </span>
                </a>

                <div class="flex items-center gap-1 text-xs font-semibold text-slate-600 whitespace-nowrap">

                    <a href="{{ Auth::check() ? route('dashboard') : url('/') }}"
                        title="Beranda"
                        class="flex items-center gap-1.5 px-2 sm:px-2.5 lg:px-3 py-2 rounded-xl transition-colors
                        {{ request()->routeIs('dashboard') || (request()->is('/') && !request()->routeIs('communities.*'))
                            ? 'text-blue-600 bg-blue-50 font-bold'
                            : 'hover:text-slate-900 hover:bg-slate-50' }}">

                        <svg class="w-4 h-4 shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10.5L12 3l9 7.5M5.5 9v11h13V9M9 20v-6h6v6">
                            </path>
                        </svg>

                        <span class="hidden lg:inline">Beranda</span>
                    </a>

                    <a href="{{ route('communities.index') }}"
                        title="Jelajahi"
                        class="flex items-center gap-1.5 px-2 sm:px-2.5 lg:px-3 py-2 rounded-xl transition-colors
                        {{ request()->routeIs('communities.*')
                            ? 'text-blue-600 bg-blue-50 font-bold'
                            : 'hover:text-slate-900 hover:bg-slate-50' }}">

                        <svg class="w-4 h-4 shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" stroke-width="2"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.5 8.5L13 13l-4.5 2.5L11 11l4.5-2.5Z">
                            </path>
                        </svg>

                        <span class="hidden lg:inline">Jelajahi</span>
                    </a>

                    @auth
                        @php
                            $unreadNotificationCount = Auth::user()->unreadNotifications()->count();
                        @endphp

                        <a href="{{ route('saved.index') }}"
                            title="Tersimpan"
                            class="flex items-center gap-1.5 px-2 sm:px-2.5 lg:px-3 py-2 rounded-xl transition-colors
                            {{ request()->routeIs('saved.*')
                                ? 'text-blue-600 bg-blue-50 font-bold'
                                : 'hover:text-slate-900 hover:bg-slate-50' }}">

                            <svg class="w-4 h-4 shrink-0"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 4.75A1.75 1.75 0 017.75 3h8.5A1.75 1.75 0 0118 4.75V21l-6-3.5L6 21V4.75Z">
                                </path>
                            </svg>

                            <span class="hidden lg:inline">Tersimpan</span>
                        </a>

                        <a href="{{ route('notifications.index') }}"
                            title="Notifikasi"
                            class="relative flex items-center gap-1.5 px-2 sm:px-2.5 lg:px-3 py-2 rounded-xl transition-colors
                            {{ request()->routeIs('notifications.*')
                                ? 'text-blue-600 bg-blue-50 font-bold'
                                : 'hover:text-slate-900 hover:bg-slate-50' }}">

                            <svg class="w-4 h-4 shrink-0"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4">
                                </path>
                            </svg>

                            <span class="hidden lg:inline">Notifikasi</span>

                            @if($unreadNotificationCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1
                                    flex items-center justify-center rounded-full
                                    bg-red-500 text-white text-[9px] font-bold">
                                    {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                </span>
                            @endif
                        </a>

                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                                title="Admin Panel"
                                class="flex items-center gap-1.5 px-2 sm:px-2.5 lg:px-3 py-2 rounded-xl transition-colors
                                {{ request()->routeIs('admin.*')
                                    ? 'text-amber-700 bg-amber-50 font-bold'
                                    : 'text-amber-600 hover:text-amber-700 hover:bg-amber-50' }}">

                                <svg class="w-4 h-4 shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                    </path>
                                </svg>

                                <span class="hidden lg:inline">Admin</span>
                            </a>
                        @endif
                    @endauth

                </div>
            </div>

            <div class="ml-auto flex items-center justify-end gap-2 sm:gap-3 shrink-0">

                @auth

                    <button type="button"
                        @click="$dispatch('open-create-post')"
                        title="Buat Post"
                        class="inline-flex items-center justify-center gap-1.5 px-2.5 lg:px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors cursor-pointer whitespace-nowrap">

                        <svg class="w-4 h-4 lg:hidden"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 5v14m-7-7h14">
                            </path>
                        </svg>

                        <span class="hidden lg:inline">+ Buat Post</span>
                    </button>

                    <div x-data="{ open: false }" class="relative shrink-0">

                        <button type="button"
                            @click="open = !open"
                            @click.outside="open = false"
                            title="Menu Profil"
                            aria-label="Menu Profil"
                            class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100 transition-colors">

                            @php
                                $initials = strtoupper(substr(Auth::user()->name, 0, 2));
                                $isAdmin = Auth::user()->isAdmin();
                            @endphp

                            <div class="w-8 h-8 rounded-xl
                                {{ $isAdmin ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}
                                flex items-center justify-center font-bold text-xs shrink-0">

                                {{ $initials }}
                            </div>

                            <div class="hidden lg:flex flex-col text-left leading-tight">
                                <span class="text-xs font-bold text-slate-800">
                                    {{ Str::words(Auth::user()->name, 1, '') }}
                                </span>

                                <span class="text-[10px] text-slate-400 capitalize">
                                    {{ Auth::user()->role }}
                                </span>
                            </div>

                            <svg class="hidden lg:block w-3.5 h-3.5 text-slate-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7">
                                </path>
                            </svg>

                        </button>

                        <div x-show="open"
                            x-transition.opacity
                            @click.outside="open = false"
                            style="display: none;"
                            class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-lg border border-slate-100 py-1.5 z-50 text-xs">

                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="font-bold text-slate-800 truncate">
                                    {{ Auth::user()->name }}
                                </p>

                                <p class="text-slate-400 text-[11px] truncate mt-0.5">
                                    {{ Auth::user()->email }}
                                </p>
                            </div>

                            @if(Auth::user()->username)
                                <a href="{{ route('profile.show', ['user' => Auth::user()->username]) }}"
                                    class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 transition-colors">
                                    Lihat Profil
                                </a>
                            @endif

                            <a href="{{ route('saved.index') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-slate-700 hover:bg-slate-50 transition-colors">

                                <svg class="w-4 h-4"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 4.75A1.75 1.75 0 017.75 3h8.5A1.75 1.75 0 0118 4.75V21l-6-3.5L6 21V4.75Z">
                                    </path>
                                </svg>

                                Tersimpan
                            </a>

                            <a href="{{ route('posts.drafts') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-slate-700 hover:bg-slate-50 transition-colors">

                                <svg class="w-4 h-4"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 3h7l5 5v13H5V3h2zm7 0v5h5M8 13h8M8 17h8">
                                    </path>
                                </svg>

                                Draft
                            </a>

                            @if(Auth::user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-amber-700 hover:bg-amber-50 transition-colors font-semibold">

                                    <svg class="w-4 h-4"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                        </path>
                                    </svg>

                                    Admin Panel
                                </a>
                            @endif

                            <a href="{{ route('profile.edit') }}"
                                class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 transition-colors">
                                Pengaturan Akun
                            </a>

                            <div class="border-t border-slate-100 mt-1 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <button type="submit"
                                        class="w-full text-left px-4 py-2.5 text-rose-600 hover:bg-rose-50 font-medium transition-colors">
                                        Keluar
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>

                @else

                    <div class="flex items-center gap-2">
                        <button type="button"
                            @click="authModal = 'login'"
                            class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                            Masuk
                        </button>

                        <button type="button"
                            @click="authModal = 'register'"
                            class="px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors cursor-pointer shadow-xs">
                            Daftar
                        </button>
                    </div>

                @endauth

            </div>
        </div>
    </div>
</nav>
