<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): RedirectResponse
    {
        return redirect('/?auth=register');
    }

    /**
     * Check or generate unique username via API.
     */
    public function checkUsername(Request $request): JsonResponse
    {
        // 1. Mode Generate Otomatis Berdasarkan Nama
        if ($request->has('name')) {
            $name = trim($request->query('name', ''));
            $base = Str::slug($name, '');
            if (empty($base) || strlen($base) < 3) {
                $base = 'user' . rand(100, 999);
            }
            $base = strtolower(substr($base, 0, 30));

            $username = $base;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $counter;
                $counter++;
            }

            return response()->json([
                'username' => $username,
                'available' => true,
                'message' => 'Username tersedia ✓',
            ]);
        }

        // 2. Mode Pengecekan Ketersediaan Username Manual
        $username = strtolower(trim($request->query('username', '')));
        if (strlen($username) < 3) {
            return response()->json([
                'available' => false,
                'message' => 'Minimal 3 karakter.',
            ]);
        }
        if (strlen($username) > 40) {
            return response()->json([
                'available' => false,
                'message' => 'Maksimal 40 karakter.',
            ]);
        }
        if (!preg_match('/^[a-z0-9_.]+$/', $username)) {
            return response()->json([
                'available' => false,
                'message' => 'Hanya huruf, angka, titik (.), dan (_).',
            ]);
        }

        $exists = User::where('username', $username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Username sudah digunakan, pilih yang lain.' : 'Username tersedia ✓',
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Fallback: Jika username tidak diisi, otomatis buat dari nama
        if (!$request->filled('username') && $request->filled('name')) {
            $base = Str::slug($request->name, '');
            if (empty($base) || strlen($base) < 3) {
                $base = 'user' . rand(100, 999);
            }
            $base = strtolower(substr($base, 0, 30));
            $generatedUsername = $base;
            $counter = 1;
            while (User::where('username', $generatedUsername)->exists()) {
                $generatedUsername = $base . $counter;
                $counter++;
            }
            $request->merge(['username' => $generatedUsername]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[a-zA-Z0-9_.]+$/',
                'unique:'.User::class.',username',
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 40 karakter.',
            'username.regex' => 'Username hanya boleh huruf, angka, titik (.), dan garis bawah (_).',
            'username.unique' => 'Username tersebut sudah terpakai, pilih username lain.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => strtolower($request->username),
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
