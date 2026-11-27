<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'bio',
        'avatar_path',
        'banner_path',
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

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'http://') || str_starts_with($this->avatar_path, 'https://')) {
            return $this->avatar_path;
        }

        if (filled(config('filesystems.disks.s3.bucket')) && Storage::disk('s3')->exists($this->avatar_path)) {
            return Storage::disk('s3')->url($this->avatar_path);
        }

        $cleanPath = ltrim($this->avatar_path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        return asset('storage/' . $cleanPath);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->banner_path) {
            return null;
        }

        if (str_starts_with($this->banner_path, 'http://') || str_starts_with($this->banner_path, 'https://')) {
            return $this->banner_path;
        }

        if (filled(config('filesystems.disks.s3.bucket')) && Storage::disk('s3')->exists($this->banner_path)) {
            return Storage::disk('s3')->url($this->banner_path);
        }

        $cleanPath = ltrim($this->banner_path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        return asset('storage/' . $cleanPath);
    }
}
