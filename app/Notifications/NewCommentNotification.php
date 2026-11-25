<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewCommentNotification extends Notification
{
    use Queueable;

    protected Comment $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->comment->user;
        $post = $this->comment->post;

        return [
            'category' => 'comment',
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_username' => $actor->username,
            'post_id' => $this->comment->post_id,
            'post_title' => $post?->title,
            'comment_id' => $this->comment->id,
            'parent_comment_id' => null,
            'message' => $actor->name . ' mengomentari postingan Anda.',
            'comment_content' => Str::limit($this->comment->content, 120),
            'url' => route('posts.show', $this->comment->post_id)
                . '#comment-' . $this->comment->id,
        ];
    }
}
