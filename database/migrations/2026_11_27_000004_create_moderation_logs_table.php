<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('moderation_logs')) {
            return;
        }

        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('target_snapshot');
            $table->foreignId('target_author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('note')->nullable();
            $table->unsignedInteger('reports_affected')->default(1);
            $table->timestamps();

            $table->index(['community_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
