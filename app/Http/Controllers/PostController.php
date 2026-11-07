<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Support\PostFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * Potongan HTML daftar kartu post (dipanggil AJAX oleh tab feed).
     */
    public function feed(Request $request)
    {
        $posts = PostFeed::get($request->query('tab'));

        return view('posts.partials.feed-list', compact('posts'));
    }

    public function show(Post $post)
    {
        $post->load(['user.moderatedCommunities', 'community'])
            ->loadCount([
                'comments',
                'votes as upvotes_count' => fn ($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes_count' => fn ($q) => $q->where('vote_type', 'downvote'),
            ]);

        return view('posts.show', compact('post'));
    }

    public function store(StorePostRequest $request)
    {
        $post = $request->user()->posts()->create($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Postingan berhasil dibuat.');
    }

    public function edit(Post $post)
    {
        Gate::authorize('update', $post);

        $post->load('community');

        return view('posts.edit', compact('post'));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->update($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Postingan berhasil diperbarui.');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Postingan berhasil dihapus.');
    }
}
