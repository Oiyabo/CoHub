<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ModerationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users'        => User::count(),
            'total_posts'        => Post::count(),
            'total_comments'     => Comment::count(),
            'total_communities'  => Community::count(),
            'closed_communities' => Community::where('is_closed', true)->count(),
            'admin_count'        => User::where('role', 'admin')->count(),
            'blocked_count'      => User::where('is_blocked', true)->count(),
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
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($filter === 'admin') {
            $query->where('role', 'admin');
        } elseif ($filter === 'blocked') {
            $query->where('is_blocked', true);
        } elseif ($filter === 'user') {
            $query->where('role', 'user');
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users', compact('users', 'search', 'filter'));
    }

    public function toggleBlockUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat memblokir akun sendiri.',
            ], 403);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Admin tidak dapat diblokir.',
            ], 403);
        }

        $user->update(['is_blocked' => ! $user->is_blocked]);

        $action = $user->is_blocked ? 'block_user' : 'unblock_user';
        ModerationNotification::send(
            actor: Auth::user(),
            actionType: $action,
            targetUser: $user
        );

        return response()->json([
            'success'    => true,
            'is_blocked' => $user->is_blocked,
            'message'    => $user->is_blocked
                ? "Akun {$user->name} berhasil diblokir."
                : "Akun {$user->name} berhasil diaktifkan kembali.",
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Kewenangan tidak mencukupi untuk mengubah peran.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        $newRole = $validated['role'];

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Demi keamanan, Anda tidak dapat mengubah peran akun Anda sendiri.',
            ], 403);
        }

        if ($user->isBlocked() && $newRole === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna yang sedang diblokir tidak dapat diberi peran Admin.',
            ], 422);
        }

        if ($user->isAdmin() && $newRole !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Sistem harus memiliki minimal satu Admin aktif.',
            ], 422);
        }

        if ($user->role === $newRole) {
            return response()->json([
                'success' => true,
                'role'    => $user->role,
                'message' => "Peran {$user->name} sudah berstatus {$newRole}.",
            ]);
        }

        $user->update(['role' => $newRole]);

        ModerationNotification::send(
            actor: Auth::user(),
            actionType: 'update_role',
            targetUser: $user,
            extra: ['new_role' => $newRole]
        );

        return response()->json([
            'success' => true,
            'role'    => $user->role,
            'message' => "Peran {$user->name} berhasil diubah menjadi " . ($newRole === 'admin' ? 'Admin' : 'User') . '.',
        ]);
    }

    public function manageCommunities(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');

        $communities = Community::with('creator')
            ->withCount(['posts', 'members'])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($q) => $q->where('is_closed', false))
            ->when($status === 'closed', fn ($q) => $q->where('is_closed', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.communities', compact('communities', 'search', 'status'));
    }

    public function toggleCloseCommunity(Community $community)
    {
        $community->update(['is_closed' => ! $community->is_closed]);

        $action = $community->is_closed ? 'close_community' : 'reopen_community';
        ModerationNotification::send(
            actor: Auth::user(),
            actionType: $action,
            community: $community
        );

        return response()->json([
            'success'   => true,
            'is_closed' => $community->is_closed,
            'message'   => $community->is_closed
                ? "Komunitas c/{$community->name} berhasil ditutup."
                : "Komunitas c/{$community->name} berhasil dibuka kembali.",
        ]);
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
