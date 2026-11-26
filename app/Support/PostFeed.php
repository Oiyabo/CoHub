<?php

namespace App\Support;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class PostFeed
{
    public const TABS = ['untuk-anda', 'terbaru', 'teratas'];

    public static function normalizeTab(?string $tab): string
    {
        return in_array($tab, self::TABS, true) ? $tab : 'untuk-anda';
    }

    public static function get(?string $tab = null, int $limit = 30, ?User $user = null): Collection
    {
        $user = $user ?? auth()->user();

        $query = Post::query()
            ->where('status', 'published')
            ->with(['user.moderatedCommunities', 'community', 'votes', 'comments.user', 'comments.votes'])
            ->withCount([
                'comments' => fn($q) => $q->whereNull('deleted_at'),
                'votes as upvotes_count' => fn($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes_count' => fn($q) => $q->where('vote_type', 'downvote'),
            ]);

        self::applyVisibility($query, $user);
        self::applyOrder($query, $tab, $user);

        return $query->limit($limit)->get();
    }

    public static function ids(?string $tab = null, ?User $user = null): SupportCollection
    {
        $user = $user ?? auth()->user();

        $query = Post::query()
            ->where('status', 'published')
            ->select('posts.id');

        self::applyVisibility($query, $user);
        self::applyOrder($query, $tab, $user);

        return $query->pluck('id');
    }

    public static function applyVisibility(Builder $query, ?User $user = null): void
    {
        $user = $user ?? auth()->user();

        $query->where(function (Builder $q) use ($user) {
            $q->whereHas('community', fn($c) => $c->where('is_private', false))
                ->orWhereNull('community_id');

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

    public static function applyOrder(Builder $query, ?string $tab, ?User $user = null): void
    {
        $tab = self::normalizeTab($tab);
        $user = $user ?? auth()->user();

        if ($tab === 'teratas') {
            $query->orderByRaw(
                "(select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'upvote')"
                . " - (select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'downvote') desc"
            )->orderBy('posts.created_at', 'desc')->orderBy('posts.id', 'desc');
        } elseif ($tab === 'terbaru') {
            $query->orderBy('posts.created_at', 'desc')->orderBy('posts.id', 'desc');
        } elseif ($tab === 'untuk-anda') {
            if ($user) {
                $query->orderByRaw(
                    "case when posts.community_id in (select community_id from community_members where user_id = ?) then 1 else 0 end desc",
                    [$user->id]
                );
            }

            $query->orderByRaw(
                "((select count(*) from comments where comments.post_id = posts.id and comments.deleted_at is null)"
                . " + (select count(*) from votes where votes.post_id = posts.id and votes.vote_type = 'upvote')) desc"
            )->orderBy('posts.created_at', 'desc')->orderBy('posts.id', 'desc');
        }
    }
}
