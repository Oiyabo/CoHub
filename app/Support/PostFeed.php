<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

/**
 * Query feed postingan (dipakai halaman beranda & endpoint AJAX filter).
 * Eager load + withCount dipakai supaya jumlah query tetap kecil
 * (database Supabase itu cloud, tiap query ~200 ms).
 */
class PostFeed
{
    public const TABS = ['untuk-anda', 'terbaru', 'teratas'];

    public static function normalizeTab(?string $tab): string
    {
        return in_array($tab, self::TABS, true) ? $tab : 'untuk-anda';
    }

    public static function get(?string $tab = null, int $limit = 30): Collection
    {
        $query = Post::query()
            ->with(['user.moderatedCommunities', 'community'])
            ->withCount([
                'comments',
                'votes as upvotes_count' => fn ($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes_count' => fn ($q) => $q->where('vote_type', 'downvote'),
            ]);

        // "Teratas" = skor tertinggi (upvote - downvote).
        // Ditulis sebagai subquery langsung karena PostgreSQL tidak boleh
        // memakai alias select di dalam ekspresi ORDER BY.
        if (self::normalizeTab($tab) === 'teratas') {
            $query->orderByRaw(
                "(select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'upvote')"
                ." - (select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'downvote') desc"
            );
        }

        // "Untuk Anda" dan "Terbaru" sementara sama-sama urut terbaru
        // (belum ada tabel keanggotaan komunitas untuk personalisasi).
        return $query->latest()->limit($limit)->get();
    }
}
