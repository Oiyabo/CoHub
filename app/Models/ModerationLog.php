<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'moderator_id',
        'report_id',
        'target_type',
        'target_id',
        'target_snapshot',
        'target_author_id',
        'action',
        'note',
        'reports_affected',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function targetAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_author_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'approve' => 'Disetujui',
            'hide'    => 'Disembunyikan',
            'delete'  => 'Dihapus',
            'dismiss' => 'Diabaikan',
            default   => ucfirst($this->action),
        };
    }

    public function getTargetTypeLabelAttribute(): string
    {
        return match ($this->target_type) {
            'post'    => 'Postingan',
            'comment' => 'Komentar',
            default   => 'Konten',
        };
    }
}
