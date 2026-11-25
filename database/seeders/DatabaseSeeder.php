<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        // 1. Akun Admin
        User::create([
            'name' => 'Admin CoHub',
            'username' => 'admin',
            'email' => 'admin@cohub.com',
            'password' => $password,
            'role' => 'admin',
            'bio' => 'Administrator resmi platform CoHub.',
            'email_verified_at' => now(),
        ]);

        // 2. Akun Biasa 1
        User::create([
            'name' => 'User Satu',
            'username' => 'user1',
            'email' => 'user1@cohub.com',
            'password' => $password,
            'role' => 'user',
            'bio' => 'Pengguna aktif komunitas CoHub.',
            'email_verified_at' => now(),
        ]);

        // 3. Akun Biasa 2
        User::create([
            'name' => 'User Dua',
            'username' => 'user2',
            'email' => 'user2@cohub.com',
            'password' => $password,
            'role' => 'user',
            'bio' => 'Pengguna komunitas CoHub.',
            'email_verified_at' => now(),
        ]);
    }
}
