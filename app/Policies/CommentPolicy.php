<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Post $post): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            && $comment->deleted_at === null;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->deleted_at !== null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($comment->user_id === $user->id) {
            return true;
        }

        return $user->isModeratorOf(
            $comment->post->community_id
        );
    }
}
