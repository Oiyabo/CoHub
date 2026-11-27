<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolveTarget(): ?Model
    {
        return match ($this->reportable_type) {
            'community' => Community::with('creator')->find($this->reportable_id),
            'post' => Post::with(['user', 'community'])->find($this->reportable_id),
            'comment' => Comment::with(['user', 'post.community'])->find($this->reportable_id),
            'user' => User::find($this->reportable_id),
            default => null,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->reportable_type) {
            'community' => 'Komunitas',
            'post' => 'Postingan',
            'comment' => 'Komentar',
            'user' => 'Pengguna',
            default => 'Konten',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'Baru',
            'reviewed' => 'Ditinjau',
            'resolved' => 'Selesai',
            'dismissed' => 'Diabaikan',
            default => ucfirst($this->status),
        };
    }
}
