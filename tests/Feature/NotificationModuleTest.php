<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationModuleTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(User $user, bool $isRead = false, string $category = 'comment'): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewCommentNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'actor_name' => 'Test Actor',
                'category' => $category,
                'message' => 'Seseorang mengomentari postinganmu.',
                'url' => '/posts/1',
            ],
            'read_at' => $isRead ? now() : null,
        ]);
    }

    public function test_user_can_delete_own_read_notification(): void
    {
        $user = User::factory()->create();
        $readNotification = $this->createNotification($user, isRead: true);

        $response = $this->actingAs($user)->delete(route('notifications.destroy', $readNotification->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Notifikasi berhasil dihapus.');

        $this->assertDatabaseMissing('notifications', [
            'id' => $readNotification->id,
        ]);
    }

    public function test_user_cannot_delete_unread_notification(): void
    {
        $user = User::factory()->create();
        $unreadNotification = $this->createNotification($user, isRead: false);

        $response = $this->actingAs($user)->delete(route('notifications.destroy', $unreadNotification->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Hanya notifikasi yang sudah dibaca yang dapat dihapus.');

        $this->assertDatabaseHas('notifications', [
            'id' => $unreadNotification->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $notificationOfUser2 = $this->createNotification($user2, isRead: true);

        $response = $this->actingAs($user1)->delete(route('notifications.destroy', $notificationOfUser2->id));

        $response->assertNotFound();

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationOfUser2->id,
        ]);
    }

    public function test_user_can_bulk_delete_all_read_notifications(): void
    {
        $user = User::factory()->create();

        // 3 read notifications and 2 unread notifications
        $read1 = $this->createNotification($user, isRead: true);
        $read2 = $this->createNotification($user, isRead: true);
        $read3 = $this->createNotification($user, isRead: true);

        $unread1 = $this->createNotification($user, isRead: false);
        $unread2 = $this->createNotification($user, isRead: false);

        $response = $this->actingAs($user)->delete(route('notifications.destroy-all-read'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Semua notifikasi yang sudah dibaca berhasil dihapus.');

        // Read notifications are deleted
        $this->assertDatabaseMissing('notifications', ['id' => $read1->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $read2->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $read3->id]);

        // Unread notifications are preserved
        $this->assertDatabaseHas('notifications', ['id' => $unread1->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unread2->id]);
    }

    public function test_notification_index_shows_delete_button_for_read_notifications(): void
    {
        $user = User::factory()->create();
        $readNotification = $this->createNotification($user, isRead: true);
        $unreadNotification = $this->createNotification($user, isRead: false);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();

        // Should see delete action for read notification
        $response->assertSee(route('notifications.destroy', $readNotification->id));

        // Should see bulk delete button when read notifications exist
        $response->assertSee('Hapus yang sudah dibaca');
        $response->assertSee(route('notifications.destroy-all-read'));
    }
}
