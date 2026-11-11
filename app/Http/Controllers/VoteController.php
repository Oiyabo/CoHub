<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    /**
     * Toggle upvote/downvote pada sebuah postingan.
     * - Jika user belum vote → buat vote baru
     * - Jika user vote yang sama → batalkan (hapus)
     * - Jika user ganti jenis vote → perbarui
     */
    public function votePost(Request $request, Post $post)
    {
        $user = Auth::user();

        // Cegah self-voting
        if ($post->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak dapat memvote postinganmu sendiri.',
            ], 403);
        }

        $request->validate(['vote_type' => 'required|in:upvote,downvote']);
        $voteType = $request->vote_type;

        $existingVote = Vote::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                // Vote yang sama → batalkan
                $existingVote->delete();
                $userVote = null;
            } else {
                // Ganti jenis vote
                $existingVote->update(['vote_type' => $voteType]);
                $userVote = $voteType;
            }
        } else {
            // Vote baru
            Vote::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'vote_type' => $voteType,
            ]);
            $userVote = $voteType;
        }

        // Hitung skor terbaru
        $post->load('votes');
        $upvotes   = $post->votes->where('vote_type', 'upvote')->count();
        $downvotes = $post->votes->where('vote_type', 'downvote')->count();
        $score     = $upvotes - $downvotes;

        return response()->json([
            'success'   => true,
            'score'     => $score,
            'user_vote' => $userVote,
        ]);
    }

    /**
     * Toggle upvote/downvote pada sebuah komentar.
     */
    public function voteComment(Request $request, Comment $comment)
    {
        $user = Auth::user();

        // Cegah self-voting
        if ($comment->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak dapat memvote komentarmu sendiri.',
            ], 403);
        }

        $request->validate(['vote_type' => 'required|in:upvote,downvote']);
        $voteType = $request->vote_type;

        $existingVote = CommentVote::where('user_id', $user->id)
            ->where('comment_id', $comment->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                $existingVote->delete();
                $userVote = null;
            } else {
                $existingVote->update(['vote_type' => $voteType]);
                $userVote = $voteType;
            }
        } else {
            CommentVote::create([
                'user_id'    => $user->id,
                'comment_id' => $comment->id,
                'vote_type'  => $voteType,
            ]);
            $userVote = $voteType;
        }

        $comment->load('votes');
        $upvotes   = $comment->votes->where('vote_type', 'upvote')->count();
        $downvotes = $comment->votes->where('vote_type', 'downvote')->count();
        $score     = $upvotes - $downvotes;

        return response()->json([
            'success'   => true,
            'score'     => $score,
            'user_vote' => $userVote,
        ]);
    }
}
