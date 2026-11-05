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
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $parentId = null;

        if (!empty($validated['parent_id'])) {
            $parent = Comment::where('id', $validated['parent_id'])
                ->where('post_id', $post->id)
                ->firstOrFail();

            $parentId = $parent->id;
        }

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'content' => $validated['content'],
        ]);

        $comment->load('user');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Komentar berhasil ditambahkan.',
                'comment' => $comment,
            ], 201);
        }

        return back()->with('success', 'Komentar berhasil ditambahkan.');
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
            return response()->json([
                'message' => 'Komentar berhasil diperbarui.',
                'comment' => $comment->load('user'),
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
            return response()->json([
                'message' => 'Komentar berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Komentar berhasil dihapus.');
    }
}
