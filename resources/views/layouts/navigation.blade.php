<!-- TOP NAVBAR COHUB (Sederhana & Bersih) -->
<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-2xs">
    <div class="max-w-[1300px] mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16 gap-4">
            
            <!-- 1. BRAND LOGO -->
            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-sm shadow-xs">
                        Co
                    </div>
                    <span class="text-lg font-bold tracking-tight text-slate-900">
                        CoHub
                    </span>
                </a>

                <!-- 2. NAV LINKS -->
                <div class="hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <a href="{{ Auth::check() ? route('dashboard') : url('/') }}" 
                       class="px-3 py-2 rounded-xl transition-colors {{ request()->routeIs('dashboard') || request()->is('/') ? 'text-blue-600 bg-blue-50 font-bold' : 'hover:text-slate-900 hover:bg-slate-50' }}">
                        Beranda
                    </a>

                    @auth
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                               class="px-3 py-2 rounded-xl transition-colors flex items-center gap-1.5
                                      {{ request()->routeIs('admin.*') ? 'text-amber-700 bg-amber-50 font-bold' : 'text-amber-600 hover:text-amber-700 hover:bg-amber-50' }}">
                                ⚡ Admin Panel
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- 3. RIGHT ACTIONS -->
            <div class="flex items-center gap-3">
                @auth
                    <!-- Tombol "+ Buat Post" -->
                    <button type="button" 
                            @click="$dispatch('open-create-post')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        <span>+ Buat Post</span>
                    </button>

                    <!-- Profil User -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100 transition-colors">
                            @php
                                $initials = strtoupper(substr(Auth::user()->name, 0, 2));
                                $isAdmin = Auth::user()->isAdmin();
                            @endphp
                            <div class="w-8 h-8 rounded-xl {{ $isAdmin ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center font-bold text-xs shrink-0">
                                {{ $initials }}
                            </div>
                            <div class="hidden sm:flex flex-col text-left leading-tight">
                                <span class="text-xs font-bold text-slate-800">{{ Str::words(Auth::user()->name, 1, '') }}</span>
                                <span class="text-[10px] text-slate-400 capitalize">{{ Auth::user()->role }}</span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Menu Dropdown -->
                        <div x-show="open" 
                             @click.away="open = false" 
                             class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-lg border border-slate-100 py-1.5 z-50 text-xs"
                             style="display: none;">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-slate-400 text-[11px] truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">
                                Pengaturan Akun
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-rose-600 hover:bg-rose-50 font-medium">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <!-- Guest -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                            Daftar
                        </a>
                    </div>
                @endauth
            </div>

        </div>
    </div>
</nav>
