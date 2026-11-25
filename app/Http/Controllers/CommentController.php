<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\CommentReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Notifications\NewCommentNotification;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        Gate::authorize('create', [Comment::class, $post]);

        if ($post->community && $post->community->isClosed()) {
            $msg = 'Komunitas ini telah ditutup oleh Administrator.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 403)
                : back()->with('error', $msg);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => null,
            'content' => $validated['content'],
        ]);

        $comment->load(['user', 'post.user', 'votes']);

        $recipient = $comment->post->user;

        if ($recipient && $recipient->id !== $request->user()->id) {
            $recipient->notify(
                new NewCommentNotification($comment)
            );
        }

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

        if ($comment->post->community && $comment->post->community->isClosed()) {
            $msg = 'Komunitas ini telah ditutup oleh Administrator.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 403)
                : back()->with('error', $msg);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $reply = $comment->replies()->create([
            'user_id' => $request->user()->id,
            'post_id' => $comment->post_id,
            'content' => $validated['content'],
        ]);

        $reply->load(['user', 'post', 'votes']);

        $recipient = $comment->user;

        if ($recipient && $recipient->id !== $request->user()->id) {
            $recipient->notify(
                new CommentReplyNotification($reply)
            );
        }

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
            $comment->load(['user', 'post', 'votes']);

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
            $comment->load(['user', 'post', 'votes']);

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
