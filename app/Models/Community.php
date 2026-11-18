<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'banner_path',
        'avatar_path',
        'created_by',
        'rules',
        'about',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'about' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_moderators')
            ->using(CommunityModerator::class)
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_members')
            ->using(CommunityMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isModerator(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->moderators()->where('users.id', $user->id)->exists();
    }

    public function isMember(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->members()->where('users.id', $user->id)->exists();
    }

    public function getBannerUrlAttribute(): ?string
    {
        if ($this->banner_path) {
            return Storage::disk('s3')->url($this->banner_path);
        }

        return null;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return Storage::disk('s3')->url($this->avatar_path);
        }

        return null;
    }
}
