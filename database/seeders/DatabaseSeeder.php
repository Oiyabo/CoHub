<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. BERSIHKAN DATA LAMA SECARA HIERARKIS (DARI TABEL ANAK KE INDUK)
        CommentVote::query()->delete();
        Vote::query()->delete();
        Comment::query()->delete();
        Post::query()->delete();
        DB::table('community_moderators')->delete();
        Community::query()->delete();
        User::query()->delete();

        // 2. BUAT AKUN PENGGUNA (1 Admin + 4 User Anggota Kelompok & Dummy)
        // Password seragam: password123
        $password = Hash::make('password123');

        // Admin Utama (Maulana Ramadhan)
        $admin = User::create([
            'name' => 'Maulana Ramadhan',
            'email' => 'admin@cohub.com',
            'password' => $password,
            'role' => 'admin',
        ]);

        // User 2: Rizka Aprilia
        $rizka = User::create([
            'name' => 'Rizka Aprilia',
            'email' => 'rizka@cohub.com',
            'password' => $password,
            'role' => 'user',
        ]);

        // User 3: Iqlima Juliana
        $iqlima = User::create([
            'name' => 'Iqlima Juliana',
            'email' => 'iqlima@cohub.com',
            'password' => $password,
            'role' => 'user',
        ]);

        // User 4: Sakinah
        $sakinah = User::create([
            'name' => 'Sakinah',
            'email' => 'sakinah@cohub.com',
            'password' => $password,
            'role' => 'user',
        ]);

        // User 5: Budi Santoso (User Tambahan untuk Uji Coba)
        $budi = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@cohub.com',
            'password' => $password,
            'role' => 'user',
        ]);

        $users = collect([$admin, $rizka, $iqlima, $sakinah, $budi]);

        // 3. BUAT 2 KOMUNITAS AWAL
        $commLaravel = Community::create([
            'name' => 'laravel-indonesia',
            'description' => 'Diskusi seputar framework Laravel, Eloquent ORM, dan arsitektur aplikasi web modern.',
            'created_by' => $admin->id,
        ]);

        $commIlkom = Community::create([
            'name' => 'ilkom-unila',
            'description' => 'Info akademik, tugas besar, dan diskusi mahasiswa Jurusan Ilmu Komputer Universitas Lampung.',
            'created_by' => $rizka->id,
        ]);

        // 4. PENUGASAN MODERATOR (Sesuai Spesifikasi)
        // - Sakinah jadi moderator di 2 komunitas berbeda (laravel-indonesia & ilkom-unila)
        // - Rizka Aprilia jadi moderator di 1 komunitas (laravel-indonesia)
        DB::table('community_moderators')->insert([
            [
                'user_id' => $sakinah->id,
                'community_id' => $commLaravel->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $sakinah->id,
                'community_id' => $commIlkom->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $rizka->id,
                'community_id' => $commLaravel->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 5. BUAT POSTINGAN DUMMY (2 Postingan untuk bahan tes)
        $post1 = Post::create([
            'user_id' => $admin->id,
            'community_id' => $commLaravel->id,
            'title' => 'Arsitektur PostgreSQL Supabase & optimasi query tree balasan di Laravel 12',
            'content' => 'Metode flat-tree grouping memuat ratusan balasan hierarkis hanya dalam 1 kueri database untuk mencegah masalah N+1 query. Dokumentasi lengkapnya ada di laporan proyek CoHub.',
        ]);

        $post2 = Post::create([
            'user_id' => $rizka->id,
            'community_id' => $commIlkom->id,
            'title' => 'Jadwal uji coba terpadu & simulasi skenario 4 peran CoHub 2026',
            'content' => 'Halo rekan-rekan mahasiswa! Besok kita mulai pengujian end-to-end hak akses CoHub: Guest (read-only), User (unggah post & komentar), Moderator (menjaga ketertiban komunitas tertentu), dan Admin (manajerial penuh).',
        ]);

        // 6. BUAT KOMENTAR BERSARANG (NESTED COMMENTS)
        // Komentar Induk pada Post 1 oleh Rizka
        $c1 = Comment::create([
            'user_id' => $rizka->id,
            'post_id' => $post1->id,
            'parent_id' => null,
            'content' => 'Penanganan foreign key cascade pada created_by sudah tepat menggunakan nullOnDelete() agar komunitas tidak musnah saat akun pembuat dinonaktifkan!',
        ]);

        // Balasan level 1 oleh Iqlima
        $c2 = Comment::create([
            'user_id' => $iqlima->id,
            'post_id' => $post1->id,
            'parent_id' => $c1->id,
            'content' => 'Betul kak! Ditambah lagi dengan logika tombstone soft-delete, kalau komentar induk dihapus, susunan balasan di bawahnya tetap rapi dan tidak hilang.',
        ]);

        // Balasan level 2 oleh Sakinah
        $c3 = Comment::create([
            'user_id' => $sakinah->id,
            'post_id' => $post1->id,
            'parent_id' => $c2->id,
            'content' => 'Untuk sistem voting juga sudah ditambah counter cache atomic di database, jadi perhitungan skor tetap instan.',
        ]);

        // Komentar Induk terpisah pada Post 1 oleh Budi
        $c4 = Comment::create([
            'user_id' => $budi->id,
            'post_id' => $post1->id,
            'parent_id' => null,
            'content' => 'Keren banget pembagian tugas dan arsitekturnya! Sukses selalu tim kelompok 6.',
        ]);

        // Komentar pada Post 2 oleh Iqlima
        $c5 = Comment::create([
            'user_id' => $iqlima->id,
            'post_id' => $post2->id,
            'parent_id' => null,
            'content' => 'Siap kak, besok kita simulasikan skenario moderasi di c/laravel-indonesia.',
        ]);

        // Balasan pada Post 2 oleh Admin Maulana
        $c6 = Comment::create([
            'user_id' => $admin->id,
            'post_id' => $post2->id,
            'parent_id' => $c5->id,
            'content' => 'Mantap, pastikan alur login dan session di Supabase sudah sinkron.',
        ]);

        // 7. BUAT VOTES (SESUAI ATURAN UNIQUE USER_ID & POST_ID)
        // Post 1 Votes
        $voters1 = $users->random(rand(3, 5));
        foreach ($voters1 as $user) {
            Vote::create([
                'user_id' => $user->id,
                'post_id' => $post1->id,
                'vote_type' => (rand(1, 10) > 2) ? 'upvote' : 'downvote',
            ]);
        }

        // Post 2 Votes
        $voters2 = $users->random(rand(2, 4));
        foreach ($voters2 as $user) {
            Vote::create([
                'user_id' => $user->id,
                'post_id' => $post2->id,
                'vote_type' => 'upvote',
            ]);
        }

        // Comment Votes
        $allComments = collect([$c1, $c2, $c3, $c4, $c5, $c6]);
        foreach ($allComments as $comment) {
            $commentVoters = $users->random(rand(1, 4));
            foreach ($commentVoters as $user) {
                CommentVote::create([
                    'user_id' => $user->id,
                    'comment_id' => $comment->id,
                    'vote_type' => 'upvote',
                ]);
            }
        }
    }
}
