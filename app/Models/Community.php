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
        'category',
        'is_private',
        'banner_path',
        'avatar_path',
        'created_by',
        'rules',
        'about',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'about' => 'array',
            'is_private' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function isClosed(): bool
    {
        return (bool) $this->is_closed;
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
        return $this->members()->wherePivotIn('role', ['admin', 'moderator']);
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
        if (!$user)
            return false;
        return $this->members()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['admin', 'moderator'])
            ->exists();
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
        if (! $this->banner_path) {
            return null;
        }

        if (filled(config('filesystems.disks.s3.bucket'))) {
            return Storage::disk('s3')->url($this->banner_path);
        }

        return Storage::disk('public')->url($this->banner_path);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (filled(config('filesystems.disks.s3.bucket'))) {
            return Storage::disk('s3')->url($this->avatar_path);
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function addRule(array $data): array
    {
        $rules = $this->rules ?? [];
        $newRule = [
            'id' => (string) (time() . rand(100, 999)),
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
        ];
        $rules[] = $newRule;
        $this->update(['rules' => $rules]);

        return $newRule;
    }

    public function updateRule(string $ruleId, array $data): bool
    {
        $rules = $this->rules ?? [];
        $updated = false;

        foreach ($rules as &$rule) {
            if (isset($rule['id']) && (string) $rule['id'] === (string) $ruleId) {
                $rule['title'] = trim($data['title']);
                $rule['description'] = !empty($data['description']) ? trim($data['description']) : null;
                $updated = true;
                break;
            }
        }
        unset($rule);

        if (!$updated && isset($rules[$ruleId])) {
            $rules[$ruleId]['title'] = trim($data['title']);
            $rules[$ruleId]['description'] = !empty($data['description']) ? trim($data['description']) : null;
            $updated = true;
        }

        if ($updated) {
            $this->rules = array_values($rules);
            return $this->save();
        }

        return false;
    }

    public function deleteRule(string $ruleId): bool
    {
        $rules = $this->rules ?? [];
        $newRules = [];

        foreach ($rules as $idx => $rule) {
            $matched = false;
            if (isset($rule['id']) && (string) $rule['id'] === (string) $ruleId) {
                $matched = true;
            } elseif ((string) $idx === (string) $ruleId) {
                $matched = true;
            }
            if (!$matched) {
                $newRules[] = $rule;
            }
        }

        $this->rules = array_values($newRules);
        return $this->save();
    }

    public function resetDefaultRules(): array
    {
        $defaultRules = [
            [
                'id' => (string) (time() . '1'),
                'title' => 'Tetap sesuai topik',
                'description' => 'Diskusi harus berkaitan dengan topik dan ekosistem terkait.',
            ],
            [
                'id' => (string) (time() . '2'),
                'title' => 'Hormati sesama anggota',
                'description' => 'Gunakan bahasa yang santun, dilarang menyerang pribadi atau melecehkan.',
            ],
            [
                'id' => (string) (time() . '3'),
                'title' => 'Dilarang spam & iklan',
                'description' => 'Dilarang mempromosikan produk/jasa komersial tanpa izin moderator.',
            ],
        ];

        $this->rules = $defaultRules;
        $this->save();

        return $defaultRules;
    }
}
