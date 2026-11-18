<!-- MODALS AUTH GLOBAL (Login, Register, Reset Password) -->

<!-- 1. MODAL LOGIN (Sesuai Gambar 1: Masuk ke CoHub) -->
<div x-show="authModal === 'login'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="authModal = null"
    class="fixed inset-0 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
    style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 1rem;">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-[28px] shadow-2xl border border-slate-100 p-8 sm:p-9 relative my-auto w-full max-w-[420px]"
        style="width: 100%; max-width: 420px; border-radius: 28px; background-color: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem 2.25rem; position: relative; margin: auto;">

        <!-- Tombol Tutup (X) -->
        <button type="button" @click="authModal = null"
            class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition cursor-pointer text-lg leading-none"
            style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background-color: #f8fafc; border: none; cursor: pointer; color: #94a3b8;"
            title="Tutup">
            &times;
        </button>

        <!-- Logo Co -->
        <div class="flex justify-center mb-4" style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <div
                class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm"
                style="width: 3rem; height: 3rem; border-radius: 1rem; background-color: #2563eb; display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 800; font-size: 1.125rem;">
                Co
            </div>
        </div>

        <!-- Judul Modal -->
        <h2 class="text-2xl font-black text-slate-900 text-center tracking-tight mb-6"
            style="font-size: 1.5rem; font-weight: 800; color: #0f172a; text-align: center; letter-spacing: -0.025em; margin-bottom: 1.5rem;">
            Masuk ke CoHub
        </h2>

        <!-- Form Login -->
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="modal_login_email" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Email
                </label>
                <input type="email" id="modal_login_email" name="email" value="{{ old('email') }}" required autofocus
                    placeholder="rizka@email.com"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('email') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kata Sandi -->
            <div>
                <div class="flex items-center justify-between mb-1.5" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                    <label for="modal_login_password" class="block text-xs font-bold text-slate-700"
                        style="font-size: 0.75rem; font-weight: 700; color: #334155;">
                        Kata sandi
                    </label>
                    <button type="button" @click="authModal = 'forgot'"
                        class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 cursor-pointer"
                        style="font-size: 0.7rem; font-weight: 600; color: #2563eb; background: none; border: none; cursor: pointer; padding: 0;">
                        Lupa kata sandi?
                    </button>
                </div>
                <input type="password" id="modal_login_password" name="password" required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('password') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('password')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <input type="hidden" name="remember" value="1">

            <!-- Tombol Masuk -->
            <div class="pt-2" style="padding-top: 0.5rem;">
                <button type="submit"
                    class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition cursor-pointer"
                    style="width: 100%; padding: 0.875rem 1rem; background-color: #2563eb; color: #ffffff; font-weight: 700; font-size: 0.875rem; border-radius: 1rem; border: none; cursor: pointer;">
                    Masuk
                </button>
            </div>

            <!-- Footer Switch ke Register -->
            <p class="text-center text-xs text-slate-600 pt-2" style="text-align: center; font-size: 0.75rem; color: #475569; padding-top: 0.5rem; margin: 0;">
                Belum punya akun?
                <button type="button" @click="authModal = 'register'"
                    class="text-blue-600 font-bold hover:underline ml-0.5 cursor-pointer"
                    style="color: #2563eb; font-weight: 700; background: none; border: none; cursor: pointer; text-decoration: underline;">
                    Daftar
                </button>
            </p>
        </form>
    </div>
</div>


<!-- 2. MODAL REGISTER (Sesuai Gambar 2: Buat akunmu) -->
<div x-show="authModal === 'register'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="authModal = null"
    class="fixed inset-0 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
    style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 1rem;">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-[28px] shadow-2xl border border-slate-100 p-8 sm:p-9 relative my-auto w-full max-w-[420px]"
        style="width: 100%; max-width: 420px; border-radius: 28px; background-color: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem 2.25rem; position: relative; margin: auto;">

        <!-- Tombol Tutup (X) -->
        <button type="button" @click="authModal = null"
            class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition cursor-pointer text-lg leading-none"
            style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background-color: #f8fafc; border: none; cursor: pointer; color: #94a3b8;"
            title="Tutup">
            &times;
        </button>

        <!-- Logo Co -->
        <div class="flex justify-center mb-4" style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <div
                class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm"
                style="width: 3rem; height: 3rem; border-radius: 1rem; background-color: #2563eb; display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 800; font-size: 1.125rem;">
                Co
            </div>
        </div>

        <!-- Judul Modal -->
        <h2 class="text-2xl font-black text-slate-900 text-center tracking-tight mb-6"
            style="font-size: 1.5rem; font-weight: 800; color: #0f172a; text-align: center; letter-spacing: -0.025em; margin-bottom: 1.5rem;">
            Buat akunmu
        </h2>

        <!-- Form Register -->
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Nama Lengkap -->
            <div>
                <label for="modal_register_name" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Nama lengkap
                </label>
                <input type="text" id="modal_register_name" name="name" value="{{ old('name') }}" required autofocus
                    placeholder="Rizka Aprilia"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('name') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="modal_register_email" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Email
                </label>
                <input type="email" id="modal_register_email" name="email" value="{{ old('email') }}" required
                    placeholder="rizka@email.com"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('email') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kata Sandi -->
            <div>
                <label for="modal_register_password" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Kata sandi
                </label>
                <input type="password" id="modal_register_password" name="password" required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('password') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('password')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Konfirmasi Kata Sandi -->
            <div>
                <label for="modal_register_password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Konfirmasi kata sandi
                </label>
                <input type="password" id="modal_register_password_confirmation" name="password_confirmation" required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('password_confirmation') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tombol Daftar -->
            <div class="pt-2" style="padding-top: 0.5rem;">
                <button type="submit"
                    class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition cursor-pointer"
                    style="width: 100%; padding: 0.875rem 1rem; background-color: #2563eb; color: #ffffff; font-weight: 700; font-size: 0.875rem; border-radius: 1rem; border: none; cursor: pointer;">
                    Daftar
                </button>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-slate-500 pt-2" style="text-align: center; font-size: 0.75rem; color: #64748b; padding-top: 0.5rem; margin: 0;">
                Akun baru otomatis berperan sebagai <strong class="text-slate-800 font-bold" style="color: #1e293b; font-weight: 700;">User</strong>.
            </p>

            <p class="text-center text-xs text-slate-600 pt-1" style="text-align: center; font-size: 0.75rem; color: #475569; padding-top: 0.25rem; margin: 0;">
                Sudah punya akun?
                <button type="button" @click="authModal = 'login'"
                    class="text-blue-600 font-bold hover:underline ml-0.5 cursor-pointer"
                    style="color: #2563eb; font-weight: 700; background: none; border: none; cursor: pointer; text-decoration: underline;">
                    Masuk
                </button>
            </p>
        </form>
    </div>
</div>


<!-- 3. MODAL RESET PASSWORD REQUEST (Lupa Kata Sandi) -->
<div x-show="authModal === 'forgot'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="authModal = null"
    class="fixed inset-0 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
    style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 1rem;">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-[28px] shadow-2xl border border-slate-100 p-8 sm:p-9 relative my-auto w-full max-w-[420px]"
        style="width: 100%; max-width: 420px; border-radius: 28px; background-color: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem 2.25rem; position: relative; margin: auto;">

        <!-- Tombol Tutup (X) -->
        <button type="button" @click="authModal = null"
            class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition cursor-pointer text-lg leading-none"
            style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background-color: #f8fafc; border: none; cursor: pointer; color: #94a3b8;"
            title="Tutup">
            &times;
        </button>

        <!-- Logo Co -->
        <div class="flex justify-center mb-4" style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <div
                class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm"
                style="width: 3rem; height: 3rem; border-radius: 1rem; background-color: #2563eb; display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 800; font-size: 1.125rem;">
                Co
            </div>
        </div>

        <!-- Judul Modal -->
        <h2 class="text-2xl font-black text-slate-900 text-center tracking-tight mb-2"
            style="font-size: 1.5rem; font-weight: 800; color: #0f172a; text-align: center; letter-spacing: -0.025em; margin-bottom: 0.5rem;">
            Reset kata sandi
        </h2>
        <p class="text-xs text-slate-500 text-center mb-6 leading-relaxed max-w-xs mx-auto"
            style="font-size: 0.75rem; color: #64748b; text-align: center; line-height: 1.4; max-width: 20rem; margin-left: auto; margin-right: auto; margin-bottom: 1.5rem;">
            Masukkan email akun CoHub kamu dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
        </p>

        <!-- Form Reset Password -->
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="modal_forgot_email" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Email
                </label>
                <input type="email" id="modal_forgot_email" name="email" value="{{ old('email') }}" required autofocus
                    placeholder="rizka@email.com"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('email') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tombol Kirim Tautan Reset -->
            <div class="pt-2" style="padding-top: 0.5rem;">
                <button type="submit"
                    class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition cursor-pointer"
                    style="width: 100%; padding: 0.875rem 1rem; background-color: #2563eb; color: #ffffff; font-weight: 700; font-size: 0.875rem; border-radius: 1rem; border: none; cursor: pointer;">
                    Kirim Tautan Reset
                </button>
            </div>

            <!-- Footer Switch ke Login -->
            <p class="text-center text-xs text-slate-600 pt-2" style="text-align: center; font-size: 0.75rem; color: #475569; padding-top: 0.5rem; margin: 0;">
                Sudah ingat kata sandi?
                <button type="button" @click="authModal = 'login'"
                    class="text-blue-600 font-bold hover:underline ml-0.5 cursor-pointer"
                    style="color: #2563eb; font-weight: 700; background: none; border: none; cursor: pointer; text-decoration: underline;">
                    Masuk
                </button>
            </p>
        </form>
    </div>
</div>


<!-- 4. MODAL ATUR KATA SANDI BARU (Token dari Email) -->
<div x-show="authModal === 'reset'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="authModal = null"
    class="fixed inset-0 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
    style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 1rem;">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-[28px] shadow-2xl border border-slate-100 p-8 sm:p-9 relative my-auto w-full max-w-[420px]"
        style="width: 100%; max-width: 420px; border-radius: 28px; background-color: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem 2.25rem; position: relative; margin: auto;">

        <!-- Tombol Tutup (X) -->
        <button type="button" @click="authModal = null"
            class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition cursor-pointer text-lg leading-none"
            style="position: absolute; top: 1.25rem; right: 1.25rem; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background-color: #f8fafc; border: none; cursor: pointer; color: #94a3b8;"
            title="Tutup">
            &times;
        </button>

        <!-- Logo Co -->
        <div class="flex justify-center mb-4" style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <div
                class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm"
                style="width: 3rem; height: 3rem; border-radius: 1rem; background-color: #2563eb; display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 800; font-size: 1.125rem;">
                Co
            </div>
        </div>

        <!-- Judul Modal -->
        <h2 class="text-2xl font-black text-slate-900 text-center tracking-tight mb-2"
            style="font-size: 1.5rem; font-weight: 800; color: #0f172a; text-align: center; letter-spacing: -0.025em; margin-bottom: 0.5rem;">
            Atur kata sandi baru
        </h2>
        <p class="text-xs text-slate-500 text-center mb-6 leading-relaxed max-w-xs mx-auto"
            style="font-size: 0.75rem; color: #64748b; text-align: center; line-height: 1.4; max-width: 20rem; margin-left: auto; margin-right: auto; margin-bottom: 1.5rem;">
            Silakan masukkan kata sandi baru untuk akun kamu.
        </p>

        <!-- Form Atur Kata Sandi Baru -->
        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ request('token') }}">

            <!-- Email -->
            <div>
                <label for="modal_reset_email" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Email
                </label>
                <input type="email" id="modal_reset_email" name="email" value="{{ old('email', request('email')) }}" required autofocus
                    placeholder="rizka@email.com"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('email') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('email')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kata Sandi Baru -->
            <div>
                <label for="modal_reset_password" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Kata sandi baru
                </label>
                <input type="password" id="modal_reset_password" name="password" required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('password') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('password')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div>
                <label for="modal_reset_password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5"
                    style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.375rem;">
                    Konfirmasi kata sandi baru
                </label>
                <input type="password" id="modal_reset_password_confirmation" name="password_confirmation" required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors @error('password_confirmation') border-rose-500 @enderror"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background-color: #ffffff; outline: none;">
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tombol Simpan -->
            <div class="pt-2" style="padding-top: 0.5rem;">
                <button type="submit"
                    class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition cursor-pointer"
                    style="width: 100%; padding: 0.875rem 1rem; background-color: #2563eb; color: #ffffff; font-weight: 700; font-size: 0.875rem; border-radius: 1rem; border: none; cursor: pointer;">
                    Perbarui Kata Sandi
                </button>
            </div>

            <!-- Footer Switch ke Login -->
            <p class="text-center text-xs text-slate-600 pt-2" style="text-align: center; font-size: 0.75rem; color: #475569; padding-top: 0.5rem; margin: 0;">
                Sudah ingat kata sandi?
                <button type="button" @click="authModal = 'login'"
                    class="text-blue-600 font-bold hover:underline ml-0.5 cursor-pointer"
                    style="color: #2563eb; font-weight: 700; background: none; border: none; cursor: pointer; text-decoration: underline;">
                    Masuk
                </button>
            </p>
        </form>
    </div>
</div>
