<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Post;
use App\Models\Vote;
use App\Notifications\VoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    public function votePost(Request $request, Post $post)
    {
        $user = Auth::user();

        if ($post->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak dapat memvote postinganmu sendiri.',
            ], 403);
        }

        $request->validate([
            'vote_type' => 'required|in:upvote,downvote',
        ]);

        $voteType = $request->vote_type;

        $existingVote = Vote::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        $shouldNotify = false;

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                $existingVote->delete();
                $userVote = null;
            } else {
                $existingVote->update([
                    'vote_type' => $voteType,
                ]);

                $userVote = $voteType;
                $shouldNotify = true;
            }
        } else {
            Vote::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'vote_type' => $voteType,
            ]);

            $userVote = $voteType;
            $shouldNotify = true;
        }

        if ($shouldNotify) {
            $post->load('user');

            if ($post->user && $post->user->isNot($user)) {
                $post->user->notify(
                    new VoteNotification(
                        $user,
                        $voteType,
                        $post
                    )
                );
            }
        }

        $post->load('votes');

        $upvotes = $post->votes
            ->where('vote_type', 'upvote')
            ->count();

        $downvotes = $post->votes
            ->where('vote_type', 'downvote')
            ->count();

        $score = $upvotes - $downvotes;

        return response()->json([
            'success' => true,
            'score' => $score,
            'user_vote' => $userVote,
        ]);
    }

    public function voteComment(Request $request, Comment $comment)
    {
        $user = Auth::user();

        if ($comment->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak dapat memvote komentarmu sendiri.',
            ], 403);
        }

        $request->validate([
            'vote_type' => 'required|in:upvote,downvote',
        ]);

        $voteType = $request->vote_type;

        $existingVote = CommentVote::where('user_id', $user->id)
            ->where('comment_id', $comment->id)
            ->first();

        $shouldNotify = false;

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                $existingVote->delete();
                $userVote = null;
            } else {
                $existingVote->update([
                    'vote_type' => $voteType,
                ]);

                $userVote = $voteType;
                $shouldNotify = true;
            }
        } else {
            CommentVote::create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
                'vote_type' => $voteType,
            ]);

            $userVote = $voteType;
            $shouldNotify = true;
        }

        if ($shouldNotify) {
            $comment->load(['user', 'post']);

            if ($comment->user && $comment->user->isNot($user) && $comment->post) {
                $comment->user->notify(
                    new VoteNotification(
                        $user,
                        $voteType,
                        $comment->post,
                        $comment
                    )
                );
            }
        }

        $comment->load('votes');

        $upvotes = $comment->votes
            ->where('vote_type', 'upvote')
            ->count();

        $downvotes = $comment->votes
            ->where('vote_type', 'downvote')
            ->count();

        $score = $upvotes - $downvotes;

        return response()->json([
            'success' => true,
            'score' => $score,
            'user_vote' => $userVote,
        ]);
    }
}
