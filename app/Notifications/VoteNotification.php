<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class VoteNotification extends Notification
{
    use Queueable;

    protected User $actor;
    protected string $voteType;
    protected Post $post;
    protected ?Comment $comment;

    public function __construct(
        User $actor,
        string $voteType,
        Post $post,
        ?Comment $comment = null
    ) {
        $this->actor = $actor;
        $this->voteType = $voteType;
        $this->post = $post;
        $this->comment = $comment;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $action = $this->voteType === 'upvote'
            ? 'upvote'
            : 'downvote';

        $message = $this->comment
            ? "{$this->actor->name} memberi {$action} pada komentarmu."
            : "{$this->actor->name} memberi {$action} pada postinganmu.";

        return [
            'category' => 'vote',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_username' => $this->actor->username,
            'vote_type' => $this->voteType,
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'comment_id' => $this->comment?->id,
            'comment_content' => $this->comment ? Str::limit($this->comment->content, 120) : null,
            'message' => $message,
            'url' => route('posts.show', $this->post->id)
                . ($this->comment ? '#comment-' . $this->comment->id : ''),
        ];
    }
}
