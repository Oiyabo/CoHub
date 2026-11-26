<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_blocked')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_blocked')->default(false)->after('role');
            });
        }

        if (Schema::hasTable('posts') && ! Schema::hasColumn('posts', 'score')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->integer('score')->default(0)->after('content');
            });
        }

        if (Schema::hasTable('votes') && ! Schema::hasIndex('votes', 'votes_post_id_vote_type_index')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->index(['post_id', 'vote_type'], 'votes_post_id_vote_type_index');
            });
        }

        if (Schema::hasTable('comment_votes') && ! Schema::hasIndex('comment_votes', 'comment_votes_comment_id_vote_type_index')) {
            Schema::table('comment_votes', function (Blueprint $table) {
                $table->index(['comment_id', 'vote_type'], 'comment_votes_comment_id_vote_type_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_blocked')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_blocked');
            });
        }

        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'score')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('score');
            });
        }

        if (Schema::hasTable('votes') && Schema::hasIndex('votes', 'votes_post_id_vote_type_index')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->dropIndex('votes_post_id_vote_type_index');
            });
        }

        if (Schema::hasTable('comment_votes') && Schema::hasIndex('comment_votes', 'comment_votes_comment_id_vote_type_index')) {
            Schema::table('comment_votes', function (Blueprint $table) {
                $table->dropIndex('comment_votes_comment_id_vote_type_index');
            });
        }
    }
};
