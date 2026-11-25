<?php

namespace App\Support;

use App\Models\Post;
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

    public static function get(?string $tab = null, int $limit = 30): Collection
    {
        $query = Post::query()
            ->where('status', 'published')
            ->with(['user.moderatedCommunities', 'community', 'votes', 'comments.user', 'comments.votes'])
            ->withCount([
                'comments',
                'votes as upvotes_count' => fn($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes_count' => fn($q) => $q->where('vote_type', 'downvote'),
            ]);

        self::applyOrder($query, $tab);
        self::applyVisibility($query);

        return $query->latest()->limit($limit)->get();
    }

    public static function ids(?string $tab = null): SupportCollection
    {
        $query = Post::query()
            ->where('status', 'published')
            ->select('posts.id');

        self::applyOrder($query, $tab);
        self::applyVisibility($query);

        return $query->latest()->pluck('id');
    }

    public static function applyVisibility(Builder $query, ?\App\Models\User $user = null): void
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
