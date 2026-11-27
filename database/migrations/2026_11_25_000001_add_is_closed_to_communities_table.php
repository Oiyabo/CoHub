<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communities') && ! Schema::hasColumn('communities', 'is_closed')) {
            Schema::table('communities', function (Blueprint $table) {
                $table->boolean('is_closed')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communities') && Schema::hasColumn('communities', 'is_closed')) {
            Schema::table('communities', function (Blueprint $table) {
                $table->dropColumn('is_closed');
            });
        }
    }
};
