<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin 
                            {--name= : Nama lengkap admin}
                            {--email= : Alamat email admin}
                            {--password= : Password untuk akun admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menambahkan atau mempromosikan akun admin langsung ke database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('======================================');
        $this->info('  Injeksi / Tambah Akun Admin CoHub   ');
        $this->info('======================================');

        // 1. Ambil atau minta input Nama
        $name = $this->option('name');
        if (empty($name)) {
            $name = $this->ask('Masukkan Nama Admin', 'Administrator');
        }

        // 2. Ambil atau minta input Email
        $email = $this->option('email');
        while (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Format email tidak valid. Silakan coba lagi.');
            }
            $email = $this->ask('Masukkan Email Admin');
        }

        // 3. Cek apakah user dengan email tersebut sudah ada di database
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $this->warn("User dengan email [{$email}] sudah terdaftar di database.");

            // Jika akun sudah berstatus admin
            if ($existingUser->isAdmin()) {
                $this->line("Status user: Sudah berstatus ADMIN.");
                $newPassword = $this->option('password');

                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        $this->error('Password minimal harus 8 karakter!');
                        return Command::FAILURE;
                    }
                    $existingUser->password = $newPassword;
                    $existingUser->save();
                    $this->info("Password untuk admin [{$email}] berhasil diperbarui.");
                } elseif ($this->confirm('Apakah Anda ingin memperbarui password admin ini?', false)) {
                    $password = $this->promptPassword();
                    $existingUser->password = $password;
                    $existingUser->save();
                    $this->info("Password untuk admin [{$email}] berhasil diperbarui.");
                }

                $this->showUserTable($existingUser);
                return Command::SUCCESS;
            }

            // Jika user ada tapi role-nya bukan admin
            $shouldPromote = $this->input->isInteractive() 
                ? $this->confirm("Role user saat ini adalah [{$existingUser->role}]. Jadikan akun ini sebagai ADMIN?", true) 
                : true;

            if ($shouldPromote) {
                $existingUser->role = 'admin';

                $newPassword = $this->option('password');
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        $this->error('Password minimal harus 8 karakter!');
                        return Command::FAILURE;
                    }
                    $existingUser->password = $newPassword;
                } elseif ($this->input->isInteractive() && $this->confirm('Apakah Anda juga ingin memperbarui password?', false)) {
                    $password = $this->promptPassword();
                    $existingUser->password = $password;
                }

                if (!$existingUser->email_verified_at) {
                    $existingUser->email_verified_at = now();
                }

                $existingUser->save();

                $this->info("Berhasil! User [{$existingUser->email}] sekarang telah memiliki hak akses ADMIN.");
                $this->showUserTable($existingUser);
                return Command::SUCCESS;
            }

            $this->warn('Operasi dibatalkan.');
            return Command::INVALID;
        }

        // 4. Jika user belum ada, ambil/minta input Password
        $password = $this->option('password');
        if (empty($password)) {
            $password = $this->promptPassword(true);
        } else {
            if (strlen($password) < 8) {
                $this->error('Password minimal harus 8 karakter!');
                return Command::FAILURE;
            }
        }

        // 5. Simpan user admin baru ke database
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->newLine();
        $this->info('✓ Akun admin berhasil dibuat dan disimpan ke database!');
        $this->showUserTable($user);

        return Command::SUCCESS;
    }

    /**
     * Helper untuk meminta input password (dengan konfirmasi jika interactive)
     */
    protected function promptPassword(bool $confirm = false): string
    {
        while (true) {
            $password = $this->secret('Masukkan Password Admin (min. 8 karakter)');

            if (empty($password) || strlen($password) < 8) {
                $this->error('Password tidak boleh kosong dan minimal harus 8 karakter.');
                continue;
            }

            if ($confirm) {
                $passwordConfirmation = $this->secret('Konfirmasi Password Admin');
                if ($password !== $passwordConfirmation) {
                    $this->error('Konfirmasi password tidak cocok! Silakan ulangi.');
                    continue;
                }
            }

            return $password;
        }
    }

    /**
     * Tampilkan detail user dalam bentuk tabel di terminal
     */
    protected function showUserTable(User $user): void
    {
        $this->table(
            ['ID', 'Nama', 'Email', 'Role', 'Status Verifikasi', 'Dibuat Pada'],
            [[
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->email_verified_at ? 'Terverifikasi (' . $user->email_verified_at->format('Y-m-d H:i') . ')' : 'Belum verifikasi',
                $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-',
            ]]
        );
    }
}
