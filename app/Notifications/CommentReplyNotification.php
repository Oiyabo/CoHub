<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommentReplyNotification extends Notification
{
    use Queueable;

    protected Comment $reply;

    public function __construct(Comment $reply)
    {
        $this->reply = $reply;
    }

    // Notifikasi disimpan ke database
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Data yang disimpan ke tabel notifications
    public function toArray(object $notifiable): array
    {
        $actor = $this->reply->user;
        $post = $this->reply->post;

        return [
            'category' => 'reply',

            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_username' => $actor->username,

            'post_id' => $this->reply->post_id,
            'post_title' => $post?->title,

            'comment_id' => $this->reply->id,
            'parent_comment_id' => $this->reply->parent_id,

            'message' => $actor->name . ' membalas komentarmu.',

            'comment_content' => Str::limit(
                $this->reply->content,
                120
            ),

            'url' => route('posts.show', $this->reply->post_id)
                . '#comment-' . $this->reply->id,
        ];
    }
}