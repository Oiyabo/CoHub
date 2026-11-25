<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\ImageService;
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
        if ($post->community && $post->community->is_private) {
            Gate::authorize('view', $post->community);
        }
        $post->load(['user.moderatedCommunities', 'community', 'comments.user'])
            ->loadCount([
                'comments',
                'votes as upvotes_count' => fn($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes_count' => fn($q) => $q->where('vote_type', 'downvote'),
            ]);

        // Susun komentar bertingkat (dipakai komponen comment-tree milik Iqlima)
        $post->buildCommentTree();

        return view('posts.show', compact('post'));
    }


    public function saveDraft(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:500'],
            'community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        // Validasi keanggotaan komunitas jika draft ditujukan ke komunitas
        if (!empty($data['community_id'])) {
            $isMember = $request->user()
                ->joinedCommunities()
                ->where('communities.id', $data['community_id'])
                ->exists();

            if (!$isMember) {
                return back()->withErrors([
                    'community_id' => 'Kamu harus bergabung ke komunitas tersebut.',
                ])->withInput();
            }
        }

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageService::compressAndStore(
                $request->file('image'),
                'post-images',
                maxWidth: 1200
            );
        }

        $data['status'] = 'draft';

        $request->user()->posts()->create($data);

        return redirect()
            ->route('posts.drafts')
            ->with('status', 'Draft berhasil disimpan.');
    }

    public function drafts()
    {
        $posts = Post::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['community'])
            ->latest('updated_at')
            ->get();

        return view('posts.drafts', compact('posts'));
    }


    public function store(StorePostRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageService::compressAndStore($request->file('image'), 'post-images', maxWidth: 1200);
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

        // Jika tombol Publikasikan ditekan
        if ($request->input('action') === 'publish') {
            abort_unless(
                $post->status === 'draft'
                && $post->user_id === $request->user()->id,
                403
            );

            $data['status'] = 'published';
        }

        unset($data['image'], $data['remove_image'], $data['action']);

        if ($request->hasFile('image')) {
            ImageService::delete($post->image_path);

            $data['image_path'] = ImageService::compressAndStore(
                $request->file('image'),
                'post-images',
                maxWidth: 1200
            );
        } elseif ($request->boolean('remove_image')) {
            ImageService::delete($post->image_path);
            $data['image_path'] = null;
        }

        $post->update($data);
        if ($post->status === 'draft') {
            return redirect()
                ->route('posts.drafts')
                ->with('status', 'Draft berhasil diperbarui.');
        }

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Postingan berhasil diperbarui.');
    }
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        ImageService::delete($post->image_path);

        $post->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Postingan berhasil dihapus.');
    }
}
