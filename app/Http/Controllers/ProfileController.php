<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\SafeSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** * Menampilkan profil publik pengguna.*/
    public function show(Request $request, User $user): View
    {
        $tab = $request->query('tab', 'posts');

        $allowedTabs = [
            'posts',
            'replies',
            'votes',
            'communities',
        ];

        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'posts';
        }

        $hasHiddenComments = SafeSchema::hasColumn('comments', 'is_hidden');

        // Statistik profil
        $postCount = $user->posts()->where('status', 'published')->count();
        $commentCount = $user->comments()
            ->whereNull('deleted_at')
            ->when($hasHiddenComments, fn ($q) => $q->where('is_hidden', false))
            ->count();

        // Menggabungkan komunitas yang dibuat dan dimoderasi.
        $communityCount = $user->joinedCommunities()->count();

        // Data default untuk masing-masing tab.
        $posts = collect();
        $replies = collect();
        $postVotes = collect();
        $commentVotes = collect();
        $communities = collect();

        // Tab Postingan
        if ($tab === 'posts') {
            $posts = $user->posts()
                ->where('status', 'published')
                ->with(['user', 'community', 'votes'])
                ->withCount('comments')
                ->latest()
                ->paginate(6, ['*'], 'posts_page')
                ->withQueryString();
        }

        // Tab Balasan
        if ($tab === 'replies') {
            $replies = $user->comments()
                ->whereNull('deleted_at')
                ->when($hasHiddenComments, fn ($q) => $q->where('is_hidden', false))
                ->whereNotNull('parent_id')
                ->with([
                    'post.community',
                    'parent.user',
                ])
                ->latest()
                ->paginate(6, ['*'], 'replies_page')
                ->withQueryString();
        }

        // Tab Vote
        if ($tab === 'votes') {
            $postVotes = $user->votes()
                ->with([
                    'post.user',
                    'post.community',
                ])
                ->latest()
                ->limit(20)
                ->get();

            $commentVotes = $user->commentVotes()
                ->with([
                    'comment.user',
                    'comment.post.community',
                ])
                ->latest()
                ->limit(20)
                ->get();
        }

        // Tab Komunitas
        if ($tab === 'communities') {
            $communities = $user->joinedCommunities()
                ->withCount('posts')
                ->orderBy('name')
                ->get();
        }

        return view('profiles.show', compact(
            'user',
            'tab',
            'posts',
            'replies',
            'postVotes',
            'commentVotes',
            'communities',
            'postCount',
            'commentCount',
            'communityCount'
        ));
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }

    /**Menghapus akun pengguna.*/
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
