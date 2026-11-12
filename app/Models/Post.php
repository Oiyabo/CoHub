<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'community_id',
        'title',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function getScoreAttribute(): int
    {
        $up = $this->votes()->where('vote_type', 'upvote')->count();
        $down = $this->votes()->where('vote_type', 'downvote')->count();

        return $up - $down;
    }

    public function buildCommentTree(): Collection
    {
        if (!$this->relationLoaded('comments')) {
            $this->load('comments.user');
        }

        $comments = $this->comments;

        $commentsByParent = $comments->groupBy(function ($comment) {
            return $comment->parent_id ?? 'root';
        });

        foreach ($comments as $comment) {
            $comment->setRelation(
                'replies',
                $commentsByParent->get($comment->id, collect())
            );

            $comment->setRelation('post', $this);
        }

        $tree = $commentsByParent->get('root', collect());

        $this->setRelation('commentTree', $tree);

        return $tree;
    }
}