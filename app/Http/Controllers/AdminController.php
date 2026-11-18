<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users'       => User::count(),
            'total_posts'       => Post::count(),
            'total_comments'    => Comment::count(),
            'total_communities' => Community::count(),
            'admin_count'       => User::where('role', 'admin')->count(),
            'blocked_count'     => User::where('is_blocked', true)->count(),
        ];

        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $activityData[] = [
                'date'     => now()->subDays($i)->format('d M'),
                'posts'    => Post::whereDate('created_at', $date)->count(),
                'comments' => Comment::whereDate('created_at', $date)->count(),
            ];
        }

        $recentUsers = User::latest()->take(5)->get();

        $topPosts = Post::with(['user', 'community', 'votes'])
            ->get()
            ->sortByDesc(fn ($p) => $p->score)
            ->take(5)
            ->values();

        return view('admin.dashboard', compact('stats', 'activityData', 'recentUsers', 'topPosts'));
    }

    public function manageUsers(Request $request)
    {
        $search = $request->get('search', '');
        $filter = $request->get('filter', 'all');

        $query = User::withCount(['posts', 'comments']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($filter === 'admin') {
            $query->where('role', 'admin');
        } elseif ($filter === 'blocked') {
            $query->where('is_blocked', true);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users', compact('users', 'search', 'filter'));
    }

    public function toggleBlockUser(User $user)
    {
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Admin tidak dapat diblokir.',
            ], 403);
        }

        $user->update(['is_blocked' => ! $user->is_blocked]);

        return response()->json([
            'success'    => true,
            'is_blocked' => $user->is_blocked,
            'message'    => $user->is_blocked
                ? "Akun {$user->name} berhasil diblokir."
                : "Akun {$user->name} berhasil diaktifkan kembali.",
        ]);
    }

    public function manageCommunities(Request $request)
    {
        $search = $request->get('search', '');

        $communities = Community::with('creator')
            ->withCount('posts')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.communities', compact('communities', 'search'));
    }

    public function deleteCommunity(Community $community)
    {
        $name = $community->name;
        $community->delete();

        return response()->json([
            'success' => true,
            'message' => "Komunitas c/{$name} berhasil dihapus.",
        ]);
    }
}
