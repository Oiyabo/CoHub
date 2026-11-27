<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
    'community_id',
    'title',
    'content',
    'image_path',
    'status',
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
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_posts')
            ->withTimestamps();
    }

    public function isHidden(): bool
    {
        return ($this->status ?? 'published') === 'hidden';
    }

    public function getScoreAttribute(): int
    {
        if (array_key_exists('upvotes_count', $this->attributes) || isset($this->upvotes_count)) {
            return (int) ($this->upvotes_count ?? 0) - (int) ($this->downvotes_count ?? 0);
        }

        if ($this->relationLoaded('votes')) {
            $up = $this->votes->where('vote_type', 'upvote')->count();
            $down = $this->votes->where('vote_type', 'downvote')->count();

            return $up - $down;
        }

        $up = $this->votes()->where('vote_type', 'upvote')->count();
        $down = $this->votes()->where('vote_type', 'downvote')->count();

        return $up - $down;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (filled(config('filesystems.disks.s3.bucket'))) {
            return Storage::disk('s3')->url($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function buildCommentTree(): Collection
    {
        if (! $this->relationLoaded('comments')) {
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
