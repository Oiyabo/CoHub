<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Support\PostFeed;
use App\Support\SafeSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tab' => ['nullable', 'in:posts,communities,users'],
        ]);

        $keyword = trim($validated['q'] ?? '');
        $term = mb_strtolower($keyword);
        $like = '%' . $term . '%';

        $tab = $validated['tab'] ?? 'posts';
        $user = $request->user();

        $communitySelect = 'community:id,name,is_private' . (SafeSchema::hasColumn('communities', 'is_closed') ? ',is_closed' : '');

        $postsQuery = Post::query()
            ->with([
                'user:id,name,username',
                $communitySelect,
            ])
            ->withCount('comments')
            ->where('status', 'published');

        if ($term !== '') {
            $postsQuery->where(function (Builder $query) use ($like) {
                $query->whereRaw("LOWER(title) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(content) LIKE ?", [$like]);
            });
        } else {
            $postsQuery->whereRaw('1 = 0');
        }

        // Gunakan aturan visibilitas feed yang sudah ada.
        PostFeed::applyVisibility($postsQuery, $user);

        $posts = $postsQuery
            ->latest()
            ->paginate(10, ['*'], 'posts_page')
            ->withQueryString();

        // 2. Pencarian komunitas
        $communitiesQuery = Community::query()
            ->with('creator:id,name,username');

        if ($term !== '') {
            $communityTerm = mb_strtolower(
                trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $keyword)))
            );

            $communityLike = '%' . $communityTerm . '%';

            $communitiesQuery->where(function (Builder $query) use ($communityLike) {
                $query->whereRaw(
                    "REPLACE(LOWER(name), '-', ' ') LIKE ?",
                    [$communityLike]
                )->orWhereRaw(
                    "REPLACE(LOWER(COALESCE(description, '')), '-', ' ') LIKE ?",
                    [$communityLike]
                );
            });
        } else {
            $communitiesQuery->whereRaw('1 = 0');
        }

        // Komunitas privat hanya terlihat oleh pihak yang berhak.
        if (! $user->isAdmin()) {
            $communitiesQuery->where(function (Builder $query) use ($user) {
                $query->where('is_private', false)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('members', function (Builder $members) use ($user) {
                        $members->where('users.id', $user->id);
                    });
            });
        }

        $communities = $communitiesQuery
            ->latest()
            ->paginate(10, ['*'], 'communities_page')
            ->withQueryString();

        $usersQuery = User::query()
            ->when(SafeSchema::hasColumn('users', 'is_blocked'), fn ($q) => $q->where('is_blocked', false))
            ->whereNotNull('username');

        if ($term !== '') {
            $usersQuery->where(function (Builder $query) use ($like) {
                $query->whereRaw("LOWER(name) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(username) LIKE ?", [$like]);
            });
        } else {
            $usersQuery->whereRaw('1 = 0');
        }

        $users = $usersQuery
            ->orderBy('name')
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();

        return view('search.index', compact(
            'keyword',
            'tab',
            'posts',
            'communities',
            'users'
        ));
    }
}
