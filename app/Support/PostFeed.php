<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

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

    /**
     * Daftar postingan lengkap (relasi + hitungan) untuk dirender sebagai kartu.
     */
    public static function get(?string $tab = null, int $limit = 30): Collection
    {
        $query = Post::query()
            ->where('status', 'published')
            ->with(['user.moderatedCommunities', 'community'])
            ->withCount([
                'comments',
                'votes as upvotes_count' => fn($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes_count' => fn($q) => $q->where('vote_type', 'downvote'),
            ]);

        self::applyOrder($query, $tab);
        self::applyVisibility($query);

        // "Untuk Anda" dan "Terbaru" sementara sama-sama urut terbaru
        // (belum ada tabel keanggotaan komunitas untuk personalisasi).
        return $query->latest()->limit($limit)->get();
    }

    /**
     * Hanya urutan ID postingan (ringan). Dipakai untuk menyusun ulang kartu
     * yang sudah ada di halaman tanpa memuat ulang halaman.
     */
    public static function ids(?string $tab = null): SupportCollection
    {
        $query = Post::query()
            ->where('status', 'published')
            ->select('posts.id');

        self::applyOrder($query, $tab);
        self::applyVisibility($query);

        return $query->latest()->pluck('id');
    }

    /**
     * Filter visibilitas postingan: sembunyikan postingan komunitas privat
     * dari pengguna yang bukan anggota/pembuat/admin.
     */
    public static function applyVisibility(Builder $query, ?\App\Models\User $user = null): void
    {
        $user = $user ?? auth()->user();

        $query->where(function (Builder $q) use ($user) {
            // 1. Post tanpa komunitas atau dari komunitas publik
            $q->whereHas('community', fn($c) => $c->where('is_private', false))
                ->orWhereNull('community_id');

            // 2. Atau dari komunitas privat yang user merupakan anggota, pembuat, atau admin global
            if ($user) {
                if ($user->isAdmin()) {
                    $q->orWhereNotNull('community_id');
                } else {
                    $q->orWhereHas('community', function ($c) use ($user) {
                        $c->where('is_private', true)
                            ->where(function ($sub) use ($user) {
                                $sub->where('created_by', $user->id)
                                    ->orWhereHas('members', fn($m) => $m->where('users.id', $user->id));
                            });
                    });
                }
            }
        });
    }

    /**
     * "Teratas" = skor tertinggi (upvote - downvote).
     * Ditulis sebagai subquery langsung karena PostgreSQL tidak boleh
     * memakai alias select di dalam ekspresi ORDER BY.
     */
    private static function applyOrder(Builder $query, ?string $tab): void
    {
        if (self::normalizeTab($tab) === 'teratas') {
            $query->orderByRaw(
                "(select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'upvote')"
                . " - (select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'downvote') desc"
            );
        }
    }
}
