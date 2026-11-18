<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        Gate::authorize('create', [Comment::class, $post]);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => null,
            'content' => $validated['content'],
        ]);

        $comment->load(['user', 'post']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Komentar berhasil ditambahkan.',
                'html' => $this->renderComment($comment),
                'comment_id' => $comment->id,
                'parent_id' => null,
            ], 201);
        }

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function reply(Request $request, Comment $comment)
    {
        Gate::authorize('create', [Comment::class, $comment->post]);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $reply = $comment->replies()->create([
            'user_id' => $request->user()->id,
            'post_id' => $comment->post_id,
            'content' => $validated['content'],
        ]);

        $reply->load(['user', 'post']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Balasan berhasil ditambahkan.',
                'html' => $this->renderComment($reply),
                'comment_id' => $reply->id,
                'parent_id' => $comment->id,
            ], 201);
        }

        return back()->with('success', 'Balasan berhasil ditambahkan.');
    }

    public function update(Request $request, Comment $comment)
    {
        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        if ($request->expectsJson()) {
            $comment->load(['user', 'post']);

            return response()->json([
                'message' => 'Komentar berhasil diperbarui.',
                'html' => $this->renderComment($comment),
                'comment_id' => $comment->id,
            ]);
        }

        return back()->with('success', 'Komentar berhasil diperbarui.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $comment->content = '';
        $comment->deleted_at = now();
        $comment->save();

        if ($request->expectsJson()) {
            $comment->load(['user', 'post']);

            return response()->json([
                'message' => 'Komentar berhasil dihapus.',
                'html' => $this->renderComment($comment),
                'comment_id' => $comment->id,
            ]);
        }

        return back()->with('success', 'Komentar berhasil dihapus.');
    }

    private function renderComment(Comment $comment)
    {
        $depth = 0;
        $parent = $comment->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        $comment->setRelation('replies', collect());

        return view('components.comment-item', [
            'comment' => $comment,
            'depth' => $depth,
        ])->render();
    }
}
