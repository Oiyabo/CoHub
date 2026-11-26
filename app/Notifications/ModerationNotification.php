<?php

namespace App\Notifications;

use App\Models\Community;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModerationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $actor,
        public string $actionType,
        public ?User $targetUser = null,
        public ?Community $community = null,
        public array $extra = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->formatMessage();
        $url = $this->resolveUrl();

        return [
            'category' => 'moderation',
            'action_type' => $this->actionType,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_username' => $this->actor->username,
            'target_user_id' => $this->targetUser?->id,
            'target_user_name' => $this->targetUser?->name,
            'target_username' => $this->targetUser?->username,
            'community_id' => $this->community?->id,
            'community_name' => $this->community?->name,
            'extra' => $this->extra,
            'message' => $message,
            'url' => $url,
            'created_at' => now()->toIso8601String(),
        ];
    }

    protected function formatMessage(): string
    {
        $actorName = $this->actor->name;
        $targetName = $this->targetUser?->name ?? 'Pengguna';
        $communityName = $this->community ? "c/{$this->community->name}" : '';

        return match ($this->actionType) {
            'block_user' => "{$actorName} memblokir akun {$targetName}.",
            'unblock_user' => "{$actorName} membuka blokir akun {$targetName}.",
            'update_role' => "{$actorName} mengubah peran {$targetName} menjadi " . (($this->extra['new_role'] ?? '') === 'admin' ? 'Admin' : 'User') . '.',
            'close_community' => "{$actorName} menutup komunitas {$communityName}.",
            'reopen_community' => "{$actorName} membuka kembali komunitas {$communityName}.",
            'add_moderator' => "{$actorName} menambahkan {$targetName} sebagai moderator {$communityName}.",
            'remove_moderator' => "{$actorName} mencopot status moderator {$targetName} di {$communityName}.",
            default => "{$actorName} melakukan tindakan moderasi.",
        };
    }

    protected function resolveUrl(): string
    {
        if ($this->community) {
            return route('communities.show', $this->community->name);
        }

        return route('admin.users.index');
    }

    /**
     * Dispatch moderation notification to authorized recipients only.
     */
    public static function send(
        User $actor,
        string $actionType,
        ?User $targetUser = null,
        ?Community $community = null,
        array $extra = []
    ): void {
        // Platform actions go to global admins; community actions go to admins + that community's moderators.
        if ($community) {
            $admins = User::where('role', 'admin')->get();
            $moderators = $community->members()
                ->wherePivotIn('role', ['admin', 'moderator'])
                ->get();

            if ($community->created_by) {
                $creator = User::find($community->created_by);
                if ($creator) {
                    $moderators->push($creator);
                }
            }

            $recipients = $admins->concat($moderators)->unique('id');
            $moderatorIds = $moderators->pluck('id');
        } else {
            $recipients = User::where('role', 'admin')->get();
            $moderatorIds = collect();
        }

        foreach ($recipients as $recipient) {
            // Do not notify the actor who triggered the action
            if ($recipient->id === $actor->id) {
                continue;
            }

            // Do not notify the target user of the moderation action
            if ($targetUser && $recipient->id === $targetUser->id) {
                continue;
            }

            // Recipient must be an admin or a moderator/owner of the involved community
            $isAuthorized = $recipient->isAdmin() || ($community && (
                $recipient->id === $community->created_by ||
                $moderatorIds->contains($recipient->id)
            ));
            if (!$isAuthorized) {
                continue;
            }

            // Do not notify blocked users
            if ($recipient->isBlocked()) {
                continue;
            }

            $recipient->notify(new self(
                actor: $actor,
                actionType: $actionType,
                targetUser: $targetUser,
                community: $community,
                extra: $extra
            ));
        }
    }
}
