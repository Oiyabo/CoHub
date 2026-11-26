<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'bio',
        'email',
        'password',
        'role',
        'is_blocked',
        'blocked_reason',
        'posts_hidden',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_blocked'        => 'boolean',
            'posts_hidden'      => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function commentVotes(): HasMany
    {
        return $this->hasMany(CommentVote::class);
    }

    public function createdCommunities(): HasMany
    {
        return $this->hasMany(Community::class, 'created_by');
    }

    public function joinedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_members')
            ->using(CommunityMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'saved_posts')
            ->withTimestamps();
    }

    public function moderatedCommunities(): BelongsToMany
    {
        return $this->joinedCommunities()->wherePivotIn('role', ['admin', 'moderator']);
    }

    public function isAdminOf(?int $communityId): bool
    {
        if ($communityId === null)
            return false;

        if ($this->createdCommunities()->where('id', $communityId)->exists()) {
            return true;
        }

        return $this->joinedCommunities()
            ->where('community_id', $communityId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function isModeratorOf(?int $communityId): bool
    {
        if ($communityId === null) {
            return false;
        }

        if ($this->createdCommunities()->where('id', $communityId)->exists()) {
            return true;
        }

        return $this->moderatedCommunities()
            ->where('community_id', $communityId)
            ->exists();
    }

    public function isAuthorizedOf(?int $communityId): bool
    {
        return $this->isAdminOf($communityId) || $this->isModeratorOf($communityId);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBlocked(): bool
    {
        return (bool) ($this->is_blocked ?? false);
    }
}
