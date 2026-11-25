<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SavedPostController extends Controller
{
    // Menampilkan daftar postingan yang disimpan pengguna
    public function index(Request $request): View
    {
        $posts = $request->user()
            ->savedPosts()
            ->with(['user', 'community', 'votes'])
            ->withCount('comments')
            ->orderBy('saved_posts.created_at', 'desc')
            ->paginate(10);

        return view('saved.index', compact('posts'));
    }

    // Menyimpan postingan
    public function store(Request $request, Post $post): RedirectResponse
    {
        $request->user()
            ->savedPosts()
            ->syncWithoutDetaching([$post->id]);

        return back()->with('success', 'Postingan berhasil disimpan.');
    }

    // Menghapus postingan dari daftar simpanan
    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $request->user()
            ->savedPosts()
            ->detach($post->id);

        return back()->with('success', 'Postingan dihapus dari Tersimpan.');
    }
}