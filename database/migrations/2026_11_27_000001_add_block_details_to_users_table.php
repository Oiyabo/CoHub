<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'blocked_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('blocked_reason')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'posts_hidden')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('posts_hidden')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = array_values(array_filter(
            ['blocked_reason', 'posts_hidden'],
            fn (string $col) => Schema::hasColumn('users', $col)
        ));

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
