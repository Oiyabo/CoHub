<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom is_blocked ke tabel users dan kolom score ke tabel posts.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('role');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->integer('score')->default(0)->after('content');
        });

        // Composite index pada tabel votes untuk performa tinggi
        Schema::table('votes', function (Blueprint $table) {
            $table->index(['post_id', 'vote_type'], 'votes_post_id_vote_type_index');
        });

        // Composite index pada tabel comment_votes
        Schema::table('comment_votes', function (Blueprint $table) {
            $table->index(['comment_id', 'vote_type'], 'comment_votes_comment_id_vote_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex('votes_post_id_vote_type_index');
        });

        Schema::table('comment_votes', function (Blueprint $table) {
            $table->dropIndex('comment_votes_comment_id_vote_type_index');
        });
    }
};
