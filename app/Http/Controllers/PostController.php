<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Support\PostFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;


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

    /**
     * Urutan ID postingan (JSON) untuk tab feed di halaman yang kartunya sudah dirender.
     */
    public function order(Request $request)
    {
        return response()->json([
            'ids' => PostFeed::ids($request->query('tab')),
        ]);
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
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('post-images', 'public');
        }

        $post = $request->user()->posts()->create($data);

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
        $data = $request->validated();
        unset($data['image'], $data['remove_image']);

        if ($request->hasFile('image')) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $data['image_path'] = $request->file('image')->store('post-images', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $data['image_path'] = null;
        }

        $post->update($data);

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Postingan berhasil diperbarui.');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Postingan berhasil dihapus.');
    }
}
