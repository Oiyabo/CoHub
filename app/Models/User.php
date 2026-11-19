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
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'bio',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Postingan yang dibuat pengguna
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // Komentar dan balasan yang dibuat pengguna
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Vote pada postingan
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    // Vote pada komentar
    public function commentVotes(): HasMany
    {
        return $this->hasMany(CommentVote::class);
    }

    // Komunitas yang dibuat pengguna
    public function createdCommunities(): HasMany
    {
        return $this->hasMany(Community::class, 'created_by');
    }

    // Komunitas yang diikuti pengguna (anggota)
    public function joinedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_members')
            ->using(CommunityMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    // Komunitas tempat pengguna menjadi moderator
    public function moderatedCommunities(): BelongsToMany
    {
        return $this->joinedCommunities()->wherePivotIn('role', ['admin', 'moderator']);
    }

    public function isModeratorOf(?int $communityId): bool
    {
        if ($communityId === null) {
            return false;
        }

        return $this->moderatedCommunities()
            ->where('community_id', $communityId)
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
