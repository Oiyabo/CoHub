<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\Vote;
use App\Support\SafeSchema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $admin = User::firstOrCreate(
            ['email' => 'admin@cohub.com'],
            [
                'name'              => 'Admin CoHub',
                'username'          => 'admin',
                'password'          => $password,
                'role'              => 'admin',
                'bio'               => 'Administrator resmi platform CoHub.',
                'email_verified_at' => now(),
            ]
        );

        $user1 = User::firstOrCreate(
            ['email' => 'user1@cohub.com'],
            [
                'name'              => 'User Satu',
                'username'          => 'user1',
                'password'          => $password,
                'role'              => 'user',
                'bio'               => 'Pengguna aktif komunitas CoHub.',
                'email_verified_at' => now(),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'user2@cohub.com'],
            [
                'name'              => 'User Dua',
                'username'          => 'user2',
                'password'          => $password,
                'role'              => 'user',
                'bio'               => 'Pengguna komunitas CoHub.',
                'email_verified_at' => now(),
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'sakinah@cohub.com'],
            [
                'name'              => 'Sakinah',
                'username'          => 'sakinah',
                'password'          => $password,
                'role'              => 'user',
                'bio'               => 'Kontributor CoHub.',
                'email_verified_at' => now(),
            ]
        );

        $tech = Community::firstOrCreate(
            ['name' => 'teknologi'],
            [
                'description' => 'Diskusi seputar pemrograman web, Laravel, AI, dan rekayasa perangkat lunak.',
                'created_by'  => $user1->id,
            ]
        );
        $tech->members()->syncWithoutDetaching([
            $user1->id => ['role' => 'admin'],
            $user2->id => ['role' => 'moderator'],
            $user3->id => ['role' => 'member'],
            $admin->id => ['role' => 'member'],
        ]);

        $kampus = Community::firstOrCreate(
            ['name' => 'kampus'],
            [
                'description' => 'Ruang berbagi informasi perkuliahan, proyek akhir, dan kegiatan mahasiswa.',
                'created_by'  => $user2->id,
            ]
        );
        $kampus->members()->syncWithoutDetaching([
            $user2->id => ['role' => 'admin'],
            $user1->id => ['role' => 'member'],
            $user3->id => ['role' => 'member'],
        ]);

        if (Post::count() === 0) {
            $post1 = Post::create([
                'user_id'      => $user1->id,
                'community_id' => $tech->id,
                'title'        => 'Tips Optimasi Query Laravel Eloquent untuk Forum Diskusi',
                'content'      => 'Gunakan eager loading dengan with() dan withCount() agar tidak terjadi masalah N+1 query saat menampilkan daftar postingan beserta skor vote dan jumlah komentarnya.',
            ]);

            $post2 = Post::create([
                'user_id'      => $user2->id,
                'community_id' => $kampus->id,
                'title'        => 'Persiapan Presentasi Proyek Akhir Pemrograman Web Lanjut',
                'content'      => 'Bagaimana progres modul masing-masing kelompok? Pastikan fitur CRUD, otorisasi policy, AJAX voting, serta dashboard admin sudah diuji sebelum demo.',
            ]);

            $post3 = Post::create([
                'user_id'      => $user3->id,
                'community_id' => null,
                'title'        => 'Selamat Datang di CoHub — Forum Diskusi Umum',
                'content'      => 'Sekarang postingan juga dapat dibuat secara umum tanpa harus terikat pada satu komunitas tertentu. Silakan sampaikan ide atau pertanyaanmu di sini!',
            ]);

            $c1 = Comment::create([
                'user_id' => $user2->id,
                'post_id' => $post1->id,
                'content' => 'Setuju sekali! Eager loading di relasi votes juga sangat membantu mempercepat perhitungan skor.',
            ]);

            Comment::create([
                'user_id'   => $user3->id,
                'post_id'   => $post1->id,
                'parent_id' => $c1->id,
                'content'   => 'Betul, apalagi kalau komentarnya bersarang banyak level.',
            ]);

            Comment::create([
                'user_id' => $user1->id,
                'post_id' => $post2->id,
                'content' => 'Modul voting dan panel admin sudah siap untuk diuji coba langsung di browser.',
            ]);

            Vote::firstOrCreate(['user_id' => $user2->id, 'post_id' => $post1->id], ['vote_type' => 'upvote']);
            Vote::firstOrCreate(['user_id' => $user3->id, 'post_id' => $post1->id], ['vote_type' => 'upvote']);
            Vote::firstOrCreate(['user_id' => $admin->id, 'post_id' => $post1->id], ['vote_type' => 'upvote']);
            Vote::firstOrCreate(['user_id' => $user1->id, 'post_id' => $post2->id], ['vote_type' => 'upvote']);
            Vote::firstOrCreate(['user_id' => $user1->id, 'post_id' => $post3->id], ['vote_type' => 'upvote']);

            CommentVote::firstOrCreate(['user_id' => $user1->id, 'comment_id' => $c1->id], ['vote_type' => 'upvote']);
            CommentVote::firstOrCreate(['user_id' => $user3->id, 'comment_id' => $c1->id], ['vote_type' => 'upvote']);
        }

        if (SafeSchema::hasTable('reports') && Report::count() === 0) {
            $firstPost = Post::first();
            $firstComment = Comment::first();
            $firstCommunity = Community::first();

            if ($firstCommunity) {
                Report::create([
                    'reporter_id'     => $user3->id,
                    'reportable_type' => 'community',
                    'reportable_id'   => $firstCommunity->id,
                    'reason'          => 'Terindikasi spam berulang tanpa moderasi',
                    'status'          => 'new',
                ]);
            }

            if ($firstPost) {
                Report::create([
                    'reporter_id'     => $user2->id,
                    'reportable_type' => 'post',
                    'reportable_id'   => $firstPost->id,
                    'reason'          => 'Konten promosi di luar topik komunitas',
                    'status'          => 'new',
                ]);
            }

            if ($firstComment) {
                Report::create([
                    'reporter_id'     => $user1->id,
                    'reportable_type' => 'comment',
                    'reportable_id'   => $firstComment->id,
                    'reason'          => 'Bahasa kurang santun dalam diskusi',
                    'status'          => 'reviewed',
                    'reviewed_by'     => $admin->id,
                    'reviewed_at'     => now(),
                ]);
            }

            Report::create([
                'reporter_id'     => $user1->id,
                'reportable_type' => 'user',
                'reportable_id'   => $user2->id,
                'reason'          => 'Spam di beberapa komunitas',
                'status'          => 'new',
            ]);
        }
    }
}
