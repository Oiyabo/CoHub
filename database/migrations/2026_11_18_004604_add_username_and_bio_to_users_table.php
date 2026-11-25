
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
            $table->text('bio')->nullable()->after('username');
        });

        // Membuat username otomatis untuk akun yang sudah terdaftar.
        $users = DB::table('users')
            ->select('id', 'email')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $base = Str::slug(
                Str::before($user->email, '@'),
                ''
            );

            if ($base === '') {
                $base = 'user' . $user->id;
            }

            $username = $base;
            $counter = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base . $counter;
                $counter++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'bio']);
        });
    }
};
