<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ModerationNotification;
use App\Notifications\VoteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createCommunity(User $creator, string $name = 'laravel-id'): Community
    {
        $community = Community::create([
            'name' => $name,
            'description' => 'Komunitas uji coba',
            'created_by' => $creator->id,
        ]);

        $community->members()->attach($creator->id, ['role' => 'admin']);

        return $community;
    }

    // =========================================================================
    // 1. NOTIFIKASI VOTE
    // =========================================================================

    public function test_upvote_post_sends_notification_to_author(): void
    {
        $author = User::factory()->create(['name' => 'Author Post']);
        $voter = User::factory()->create(['name' => 'Voter Satu']);
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Judul Postingan Keren',
            'content' => 'Konten postingan keren untuk pengujian.',
            'status' => 'published',
        ]);

        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertOk();

        $this->assertDatabaseHas('votes', [
            'user_id' => $voter->id,
            'post_id' => $post->id,
            'vote_type' => 'upvote',
        ]);

        $this->assertCount(1, $author->notifications);
        $notification = $author->notifications->first();

        $this->assertSame(VoteNotification::class, $notification->type);
        $this->assertSame('vote', $notification->data['category']);
        $this->assertSame('upvote', $notification->data['vote_type']);
        $this->assertSame($voter->id, $notification->data['actor_id']);
        $this->assertSame($voter->name, $notification->data['actor_name']);
        $this->assertSame($post->id, $notification->data['post_id']);
        $this->assertStringContainsString('upvote', $notification->data['message']);
        $this->assertSame(route('posts.show', $post->id), $notification->data['url']);
    }

    public function test_downvote_post_sends_notification_to_author(): void
    {
        $author = User::factory()->create(['name' => 'Author Post']);
        $voter = User::factory()->create(['name' => 'Voter Dua']);
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Post Downvote Test',
            'content' => 'Konten untuk downvote test.',
            'status' => 'published',
        ]);

        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'downvote'])
            ->assertOk();

        $this->assertCount(1, $author->notifications);
        $notification = $author->notifications->first();

        $this->assertSame('vote', $notification->data['category']);
        $this->assertSame('downvote', $notification->data['vote_type']);
        $this->assertStringContainsString('downvote', $notification->data['message']);
    }

    public function test_upvote_comment_sends_notification_to_comment_owner(): void
    {
        $postAuthor = User::factory()->create();
        $commentAuthor = User::factory()->create(['name' => 'Comment Author']);
        $voter = User::factory()->create(['name' => 'Comment Voter']);
        $community = $this->createCommunity($postAuthor);

        $post = Post::create([
            'user_id' => $postAuthor->id,
            'community_id' => $community->id,
            'title' => 'Post dengan komentar',
            'content' => 'Konten post.',
            'status' => 'published',
        ]);

        $comment = Comment::create([
            'user_id' => $commentAuthor->id,
            'post_id' => $post->id,
            'content' => 'Ini komentar pertama yang bagus.',
        ]);

        $this->actingAs($voter)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'upvote'])
            ->assertOk();

        $this->assertCount(1, $commentAuthor->notifications);
        $notification = $commentAuthor->notifications->first();

        $this->assertSame('vote', $notification->data['category']);
        $this->assertSame('upvote', $notification->data['vote_type']);
        $this->assertSame($comment->id, $notification->data['comment_id']);
        $this->assertStringContainsString('komentarmu', $notification->data['message']);
        $this->assertSame(route('posts.show', $post->id) . '#comment-' . $comment->id, $notification->data['url']);
    }

    public function test_downvote_comment_sends_notification_to_comment_owner(): void
    {
        $postAuthor = User::factory()->create();
        $commentAuthor = User::factory()->create(['name' => 'Comment Author']);
        $voter = User::factory()->create(['name' => 'Comment Downvoter']);
        $community = $this->createCommunity($postAuthor);

        $post = Post::create([
            'user_id' => $postAuthor->id,
            'community_id' => $community->id,
            'title' => 'Post Downvote Comment',
            'content' => 'Konten.',
            'status' => 'published',
        ]);

        $comment = Comment::create([
            'user_id' => $commentAuthor->id,
            'post_id' => $post->id,
            'content' => 'Komentar tidak disukai.',
        ]);

        $this->actingAs($voter)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'downvote'])
            ->assertOk();

        $this->assertCount(1, $commentAuthor->notifications);
        $notification = $commentAuthor->notifications->first();

        $this->assertSame('vote', $notification->data['category']);
        $this->assertSame('downvote', $notification->data['vote_type']);
        $this->assertStringContainsString('downvote', $notification->data['message']);
    }

    public function test_changing_vote_sends_updated_notification(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Vote Change Post',
            'content' => 'Konten.',
            'status' => 'published',
        ]);

        // 1. Upvote awal
        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertOk();

        $this->assertCount(1, $author->fresh()->notifications);
        $this->assertSame('upvote', $author->fresh()->notifications->first()->data['vote_type']);

        // 2. Berubah ke downvote
        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'downvote'])
            ->assertOk();

        $this->assertCount(2, $author->fresh()->notifications);
        $latestNotification = $author->fresh()->notifications->last();
        $this->assertSame('downvote', $latestNotification->data['vote_type']);
    }

    public function test_cancelling_vote_does_not_send_notification(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Vote Cancel Post',
            'content' => 'Konten.',
            'status' => 'published',
        ]);

        // Upvote awal
        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertOk();

        $this->assertCount(1, $author->fresh()->notifications);

        // Membatalkan upvote dengan menekan upvote lagi
        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertOk();

        // Jumlah notifikasi tetap 1 (tidak ada notifikasi pembatalan)
        $this->assertCount(1, $author->fresh()->notifications);
    }

    public function test_self_voting_is_prevented_and_sends_no_notification(): void
    {
        $author = User::factory()->create();
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Self Vote Post',
            'content' => 'Konten.',
            'status' => 'published',
        ]);

        $this->actingAs($author)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertForbidden();

        $this->assertCount(0, $author->fresh()->notifications);

        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Self vote comment.',
        ]);

        $this->actingAs($author)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'upvote'])
            ->assertForbidden();

        $this->assertCount(0, $author->fresh()->notifications);
    }

    // =========================================================================
    // 2. NOTIFIKASI MODERASI
    // =========================================================================

    public function test_block_and_unblock_user_sends_moderation_notification_to_admins(): void
    {
        $actorAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Pelaku']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Penerima']);
        $targetUser = User::factory()->create(['role' => 'user', 'name' => 'Target User']);
        $regularUser = User::factory()->create(['role' => 'user', 'name' => 'User Biasa']);

        // 1. Blokir akun
        $this->actingAs($actorAdmin)
            ->postJson(route('admin.users.toggle-block', $targetUser))
            ->assertOk();

        // Admin penerima menerima notifikasi
        $this->assertCount(1, $otherAdmin->fresh()->notifications);
        $notifBlock = $otherAdmin->fresh()->notifications->first();
        $this->assertSame('moderation', $notifBlock->data['category']);
        $this->assertSame('block_user', $notifBlock->data['action_type']);
        $this->assertSame($actorAdmin->name, $notifBlock->data['actor_name']);
        $this->assertSame($targetUser->name, $notifBlock->data['target_user_name']);
        $this->assertStringContainsString('memblokir', $notifBlock->data['message']);

        // Pelaku TIDAK menerima notifikasi
        $this->assertCount(0, $actorAdmin->fresh()->notifications);

        // Target user (user biasa) TIDAK menerima notifikasi moderasi
        $this->assertCount(0, $targetUser->fresh()->notifications);

        // Pengguna biasa lainnya TIDAK menerima notifikasi moderasi
        $this->assertCount(0, $regularUser->fresh()->notifications);

        // 2. Buka blokir akun
        $this->actingAs($actorAdmin)
            ->postJson(route('admin.users.toggle-block', $targetUser))
            ->assertOk();

        $this->assertCount(2, $otherAdmin->fresh()->notifications);
        $notifUnblock = $otherAdmin->fresh()->notifications->last();
        $this->assertSame('unblock_user', $notifUnblock->data['action_type']);
        $this->assertStringContainsString('membuka blokir', $notifUnblock->data['message']);
        $this->assertCount(0, $targetUser->fresh()->notifications);
    }

    public function test_update_role_sends_moderation_notification_to_admins(): void
    {
        $actorAdmin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user', 'name' => 'Calon Admin']);

        $this->actingAs($actorAdmin)
            ->postJson(route('admin.users.update-role', $targetUser), ['role' => 'admin'])
            ->assertOk();

        $this->assertCount(1, $otherAdmin->fresh()->notifications);
        $notif = $otherAdmin->fresh()->notifications->first();

        $this->assertSame('moderation', $notif->data['category']);
        $this->assertSame('update_role', $notif->data['action_type']);
        $this->assertSame($targetUser->name, $notif->data['target_user_name']);
        $this->assertCount(0, $actorAdmin->fresh()->notifications);
        $this->assertCount(0, $targetUser->fresh()->notifications);
    }

    public function test_toggle_close_community_sends_notification_to_admins_and_community_moderators(): void
    {
        $actorAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Utama']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Lain']);
        $modCommunityA = User::factory()->create(['name' => 'Mod Komunitas A']);
        $modCommunityB = User::factory()->create(['name' => 'Mod Komunitas B']);
        $regularMember = User::factory()->create(['name' => 'Member Biasa']);

        $communityA = $this->createCommunity($actorAdmin, 'komunitas-a');
        $communityB = $this->createCommunity($actorAdmin, 'komunitas-b');

        // Mod Komunitas A terdaftar sebagai moderator di Komunitas A
        $communityA->members()->attach($modCommunityA->id, ['role' => 'moderator']);
        $communityA->members()->attach($regularMember->id, ['role' => 'member']);

        // Mod Komunitas B terdaftar sebagai moderator hanya di Komunitas B
        $communityB->members()->attach($modCommunityB->id, ['role' => 'moderator']);

        // Tutup Komunitas A
        $this->actingAs($actorAdmin)
            ->postJson(route('admin.communities.toggle-close', $communityA))
            ->assertOk();

        // 1. Admin lain menerima notifikasi
        $this->assertCount(1, $otherAdmin->fresh()->notifications);
        $this->assertSame('close_community', $otherAdmin->fresh()->notifications->first()->data['action_type']);

        // 2. Moderator Komunitas A menerima notifikasi
        $this->assertCount(1, $modCommunityA->fresh()->notifications);
        $modNotif = $modCommunityA->fresh()->notifications->first();
        $this->assertSame('moderation', $modNotif->data['category']);
        $this->assertSame('close_community', $modNotif->data['action_type']);
        $this->assertSame('komunitas-a', $modNotif->data['community_name']);

        // 3. Pelaku aksi TIDAK menerima notifikasi
        $this->assertCount(0, $actorAdmin->fresh()->notifications);

        // 4. Moderator Komunitas B TIDAK menerima notifikasi (karena bukan ranahnya)
        $this->assertCount(0, $modCommunityB->fresh()->notifications);

        // 5. Member biasa Komunitas A TIDAK menerima notifikasi
        $this->assertCount(0, $regularMember->fresh()->notifications);

        // Buka kembali Komunitas A
        $this->actingAs($actorAdmin)
            ->postJson(route('admin.communities.toggle-close', $communityA))
            ->assertOk();

        $this->assertCount(2, $modCommunityA->fresh()->notifications);
        $this->assertSame('reopen_community', $modCommunityA->fresh()->notifications->last()->data['action_type']);
    }

    public function test_add_and_remove_moderator_sends_notification_to_community_moderators_and_admins(): void
    {
        $creator = User::factory()->create(['name' => 'Owner Komunitas']);
        $existingMod = User::factory()->create(['name' => 'Mod Lama']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Sistem']);
        $newModCandidate = User::factory()->create(['email' => 'calon@mod.com', 'name' => 'Calon Mod']);
        $otherCommunityMod = User::factory()->create(['name' => 'Mod Komunitas Lain']);

        $community = $this->createCommunity($creator, 'komunitas-kreatif');
        $community->members()->attach($existingMod->id, ['role' => 'moderator']);
        $community->members()->attach($newModCandidate->id, ['role' => 'member']);

        $otherCommunity = $this->createCommunity($creator, 'komunitas-lain');
        $otherCommunity->members()->attach($otherCommunityMod->id, ['role' => 'moderator']);

        // 1. Tambah moderator baru
        $this->actingAs($creator)
            ->postJson(route('communities.moderators.add', $community->name), [
                'email' => 'calon@mod.com',
            ])->assertOk();

        // Mod lama dan admin menerima notifikasi
        $this->assertCount(1, $existingMod->fresh()->notifications);
        $this->assertSame('add_moderator', $existingMod->fresh()->notifications->first()->data['action_type']);

        $this->assertCount(1, $otherAdmin->fresh()->notifications);
        $this->assertSame('add_moderator', $otherAdmin->fresh()->notifications->first()->data['action_type']);

        // Pelaku tidak menerima notifikasi
        $this->assertCount(0, $creator->fresh()->notifications);

        // Moderator komunitas lain tidak menerima
        $this->assertCount(0, $otherCommunityMod->fresh()->notifications);

        // Pengguna target tidak menerima notifikasi moderasi
        $this->assertCount(0, $newModCandidate->fresh()->notifications);

        // 2. Hapus / copot moderator
        $this->actingAs($creator)
            ->deleteJson(route('communities.moderators.remove', [$community->name, $newModCandidate->id]))
            ->assertOk();

        $this->assertCount(2, $existingMod->fresh()->notifications);
        $this->assertSame('remove_moderator', $existingMod->fresh()->notifications->last()->data['action_type']);

        // Calon mod yang baru saja dicopot (kini member biasa) tidak menerima notifikasi moderasi
        $this->assertCount(0, $newModCandidate->fresh()->notifications);
    }

    public function test_regular_user_cannot_view_moderation_notifications_via_filter_parameter(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);

        // Simulasikan jika secara tidak sengaja ada record notifikasi kategori moderasi untuk pengguna ini
        $regularUser->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => ModerationNotification::class,
            'data' => [
                'category' => 'moderation',
                'action_type' => 'block_user',
                'actor_name' => 'Admin',
                'message' => 'Notifikasi tersembunyi',
                'url' => route('dashboard'),
            ],
            'read_at' => null,
        ]);

        // Coba akses URL filter moderasi
        $response = $this->actingAs($regularUser)
            ->get(route('notifications.index', ['filter' => 'moderation']));

        $response->assertOk();
        // Filter 'moderation' harus dinormalkan kembali dan notifikasi tidak tampil
        $response->assertDontSee('Notifikasi tersembunyi');
        $response->assertDontSee('Tandai semua dibaca'); // Karena unreadCount moderasi tidak terhitung untuk user biasa
    }

    public function test_notification_category_filters_work_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userB = User::factory()->create();

        // 1. Notifikasi komentar
        $admin->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\NewCommentNotification',
            'data' => [
                'category' => 'comment',
                'actor_name' => 'User Komentar',
                'message' => 'Pesan komentar unik',
                'url' => route('dashboard'),
            ],
            'read_at' => null,
        ]);

        // 2. Notifikasi vote
        $admin->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\VoteNotification',
            'data' => [
                'category' => 'vote',
                'actor_name' => 'User Vote',
                'message' => 'Pesan vote unik',
                'url' => route('dashboard'),
            ],
            'read_at' => null,
        ]);

        // 3. Notifikasi moderasi
        $admin->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => ModerationNotification::class,
            'data' => [
                'category' => 'moderation',
                'actor_name' => 'Admin Eksternal',
                'message' => 'Pesan moderasi unik',
                'url' => route('dashboard'),
            ],
            'read_at' => null,
        ]);

        // Filter vote
        $this->actingAs($admin)->get(route('notifications.index', ['filter' => 'vote']))
            ->assertOk()
            ->assertSee('Pesan vote unik')
            ->assertDontSee('Pesan komentar unik')
            ->assertDontSee('Pesan moderasi unik');

        // Filter comment
        $this->actingAs($admin)->get(route('notifications.index', ['filter' => 'comment']))
            ->assertOk()
            ->assertSee('Pesan komentar unik')
            ->assertDontSee('Pesan vote unik')
            ->assertDontSee('Pesan moderasi unik');

        // Filter moderation (bisa diakses admin)
        $this->actingAs($admin)->get(route('notifications.index', ['filter' => 'moderation']))
            ->assertOk()
            ->assertSee('Pesan moderasi unik')
            ->assertDontSee('Pesan vote unik')
            ->assertDontSee('Pesan komentar unik');
    }

    public function test_mark_as_read_and_mark_all_as_read(): void
    {
        $user = User::factory()->create();

        $notif1 = $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\VoteNotification',
            'data' => [
                'category' => 'vote',
                'actor_name' => 'User A',
                'message' => 'Notif Satu',
                'url' => route('dashboard'),
            ],
            'read_at' => null,
        ]);

        $notif2 = $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\VoteNotification',
            'data' => [
                'category' => 'vote',
                'actor_name' => 'User B',
                'message' => 'Notif Dua',
                'url' => route('dashboard'),
            ],
            'read_at' => null,
        ]);

        $this->assertSame(2, $user->fresh()->unreadNotifications()->count());

        // Mark satu as read
        $this->actingAs($user)
            ->post(route('notifications.read', $notif1->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($notif1->fresh()->read_at);
        $this->assertNull($notif2->fresh()->read_at);
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        // Mark semua as read
        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_community_update_with_moderators_sends_moderation_notifications(): void
    {
        $creator = User::factory()->create(['name' => 'Owner']);
        $existingMod = User::factory()->create(['name' => 'Mod A']);
        $removedMod = User::factory()->create(['name' => 'Mod B']);
        $newMod = User::factory()->create(['name' => 'Mod C']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Sistem']);

        $community = $this->createCommunity($creator, 'komunitas-update');
        $community->members()->attach($existingMod->id, ['role' => 'moderator']);
        $community->members()->attach($removedMod->id, ['role' => 'moderator']);
        $community->members()->attach($newMod->id, ['role' => 'member']);

        // Update komunitas: hapus removedMod, pertahankan existingMod, tambahkan newMod
        $this->actingAs($creator)->put(route('communities.update', $community->name), [
            'name' => $community->name,
            'description' => 'Deskripsi baru',
            'category' => 'Teknologi',
            'moderators' => [$creator->id, $existingMod->id, $newMod->id],
        ])->assertRedirect(route('communities.show', $community->name));

        // 1. existingMod menerima notifikasi penghapusan removedMod dan penambahan newMod
        $modNotifs = $existingMod->fresh()->notifications;
        $this->assertCount(2, $modNotifs);
        $actionTypes = $modNotifs->pluck('data.action_type')->all();
        $this->assertContains('remove_moderator', $actionTypes);
        $this->assertContains('add_moderator', $actionTypes);

        // 2. otherAdmin menerima kedua notifikasi
        $adminNotifs = $otherAdmin->fresh()->notifications;
        $this->assertCount(2, $adminNotifs);

        // 3. Pelaku (creator) tidak menerima notifikasi
        $this->assertCount(0, $creator->fresh()->notifications);

        // 4. newMod (target add) tidak menerima notifikasi add_moderator untuk dirinya
        $this->assertCount(0, $newMod->fresh()->notifications);

        // 5. removedMod (target remove) tidak menerima notifikasi remove_moderator untuk dirinya
        $this->assertCount(0, $removedMod->fresh()->notifications);
    }

    public function test_user_promoted_to_admin_does_not_receive_moderation_notification_for_own_promotion(): void
    {
        $actorAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin A']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin B']);
        $targetUser = User::factory()->create(['role' => 'user', 'name' => 'User C']);

        $this->actingAs($actorAdmin)->postJson(route('admin.users.update-role', $targetUser), [
            'role' => 'admin',
        ])->assertOk();

        // otherAdmin menerima notifikasi
        $this->assertCount(1, $otherAdmin->fresh()->notifications);
        $this->assertSame('update_role', $otherAdmin->fresh()->notifications->first()->data['action_type']);

        // targetUser (yang baru jadi admin) TIDAK boleh menerima notifikasi atas perubahan role dirinya sendiri
        $this->assertCount(0, $targetUser->fresh()->notifications);

        // actorAdmin tidak menerima notifikasi
        $this->assertCount(0, $actorAdmin->fresh()->notifications);
    }

    public function test_no_duplicate_notification_when_role_unchanged(): void
    {
        $actorAdmin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user']);

        // Kirim perubahan role ke role yang sama persis
        $this->actingAs($actorAdmin)->postJson(route('admin.users.update-role', $targetUser), [
            'role' => 'user',
        ])->assertOk();

        // Tidak boleh ada notifikasi yang terkirim
        $this->assertCount(0, $otherAdmin->fresh()->notifications);
    }

    public function test_no_duplicate_notification_when_removing_non_moderator(): void
    {
        $creator = User::factory()->create();
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create();

        $community = $this->createCommunity($creator, 'komunitas-tes');
        $community->members()->attach($regularUser->id, ['role' => 'member']);

        // Coba copot regularUser yang sebenarnya bukan moderator
        $this->actingAs($creator)->deleteJson(route('communities.moderators.remove', [
            $community->name,
            $regularUser->id,
        ]))->assertOk();

        // Tidak boleh ada notifikasi remove_moderator yang terkirim
        $this->assertCount(0, $otherAdmin->fresh()->notifications);
    }

    public function test_community_owner_receives_close_community_notification_and_can_filter(): void
    {
        $owner = User::factory()->create(['name' => 'Pemilik Komunitas', 'role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Sistem']);
        $community = $this->createCommunity($owner, 'komunitas-owner');

        $this->actingAs($admin)
            ->postJson(route('admin.communities.toggle-close', $community))
            ->assertOk();

        // Pemilik komunitas harus menerima notifikasi moderasi
        $this->assertCount(1, $owner->fresh()->notifications);
        $notif = $owner->fresh()->notifications->first();
        $this->assertSame('moderation', $notif->data['category']);
        $this->assertSame('close_community', $notif->data['action_type']);

        // Pemilik komunitas berhak melihat tab filter moderasi
        $this->actingAs($owner)->get(route('notifications.index', ['filter' => 'moderation']))
            ->assertOk()
            ->assertSee($admin->name);
    }
}
