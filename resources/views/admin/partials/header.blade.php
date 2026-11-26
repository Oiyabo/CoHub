<div class="mb-5">
    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Dashboard Admin</h1>
    <p class="text-xs text-slate-500 mt-0.5">Kelola seluruh platform CoHub</p>

    <div class="mt-4 inline-flex items-center gap-1 bg-slate-200/70 p-1 rounded-2xl flex-wrap">
        <a href="{{ route('admin.dashboard') }}"
           class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('admin.index') || request()->routeIs('admin.dashboard') ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            Ringkasan
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            Pengguna
        </a>

        <a href="{{ route('admin.communities.index') }}"
           class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('admin.communities.*') ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            Komunitas
        </a>

        <a href="{{ route('admin.reports.index') }}"
           class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('admin.reports.*') ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            Laporan
        </a>

        <a href="{{ route('admin.roles.index') }}"
           class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('admin.roles.*') ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
            Hak Akses
        </a>
    </div>
</div>
