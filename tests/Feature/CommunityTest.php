<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as private refreshDatabaseUnsafe;
    }

    public function refreshDatabase()
    {
        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('Tes dilewati: database aktif bukan SQLite.');
        }

        $this->refreshDatabaseUnsafe();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_user_can_create_public_and_private_community(): void
    {
        $user = User::factory()->create();

        // 1. Publik
        $this->actingAs($user)->post(route('communities.store'), [
            'name' => 'komunitas-publik',
            'description' => 'Komunitas publik',
            'category' => 'Kampus',
            'is_private' => 0,
        ])->assertRedirect(route('communities.show', 'komunitas-publik'));

        $publicComm = Community::where('name', 'komunitas-publik')->first();
        $this->assertNotNull($publicComm);
        $this->assertFalse($publicComm->is_private);

        // 2. Privat
        $this->actingAs($user)->post(route('communities.store'), [
            'name' => 'komunitas-privat',
            'description' => 'Komunitas privat',
            'category' => 'Kampus',
            'is_private' => 1,
        ])->assertRedirect(route('communities.show', 'komunitas-privat'));

        $privateComm = Community::where('name', 'komunitas-privat')->first();
        $this->assertNotNull($privateComm);
        $this->assertTrue($privateComm->is_private);
    }

    public function test_guest_can_view_public_community_content(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'publik-open',
            'created_by' => $creator->id,
            'is_private' => false,
        ]);

        $response = $this->get(route('communities.show', $comm->name));
        $response->assertOk();
        $response->assertViewHas('canViewContent', true);
    }

    public function test_guest_and_non_member_see_locked_content_for_private_community(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'privat-rahasia',
            'created_by' => $creator->id,
            'is_private' => true,
        ]);

        // Guest
        $resGuest = $this->get(route('communities.show', $comm->name));
        $resGuest->assertOk();
        $resGuest->assertViewHas('canViewContent', false);
        $resGuest->assertSee('Komunitas Ini Bersifat Privat');

        // Non-member
        $nonMember = User::factory()->create();
        $resUser = $this->actingAs($nonMember)->get(route('communities.show', $comm->name));
        $resUser->assertOk();
        $resUser->assertViewHas('canViewContent', false);
        $resUser->assertSee('Komunitas Ini Bersifat Privat');
    }

    public function test_member_and_admin_can_view_private_community_content(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'privat-member',
            'created_by' => $creator->id,
            'is_private' => true,
        ]);

        $member = User::factory()->create();
        $comm->members()->attach($member->id, ['role' => 'member']);

        // Member
        $resMember = $this->actingAs($member)->get(route('communities.show', $comm->name));
        $resMember->assertOk();
        $resMember->assertViewHas('canViewContent', true);

        // Global Admin
        $admin = User::factory()->create(['role' => 'admin']);
        $resAdmin = $this->actingAs($admin)->get(route('communities.show', $comm->name));
        $resAdmin->assertOk();
        $resAdmin->assertViewHas('canViewContent', true);
    }

    public function test_post_in_private_community_cannot_be_viewed_by_non_member(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'privat-post-comm',
            'created_by' => $creator->id,
            'is_private' => true,
        ]);

        $post = Post::create([
            'user_id' => $creator->id,
            'community_id' => $comm->id,
            'title' => 'Post Rahasia',
            'content' => 'Isi rahasia',
            'status' => 'published',
        ]);

        // Non-member gets 403
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('posts.show', $post))->assertForbidden();

        // Member can view
        $member = User::factory()->create();
        $comm->members()->attach($member->id, ['role' => 'member']);
        $this->actingAs($member)->get(route('posts.show', $post))->assertOk();
    }

    public function test_creator_can_update_community_privacy(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'comm-update-privacy',
            'created_by' => $creator->id,
            'is_private' => false,
        ]);

        $this->actingAs($creator)->put(route('communities.update', $comm->name), [
            'name' => 'comm-update-privacy',
            'description' => 'Updated desc',
            'category' => 'Kampus',
            'is_private' => 1,
        ])->assertRedirect(route('communities.show', $comm->name));

        $this->assertTrue($comm->fresh()->is_private);
    }

    public function test_creator_can_update_community_with_rules_and_moderators_together(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'comm-multi-update',
            'created_by' => $creator->id,
            'is_private' => false,
        ]);
        $comm->members()->attach($creator->id, ['role' => 'admin']);

        $modUser = User::factory()->create();
        $rules = [
            ['id' => '1', 'title' => 'Aturan Pertama', 'description' => 'Jangan berisik'],
            ['id' => '2', 'title' => 'Aturan Kedua', 'description' => 'Tetap sopan'],
        ];

        $response = $this->actingAs($creator)->put(route('communities.update', $comm->name), [
            'name' => 'comm-multi-update',
            'description' => 'Deskripsi baru',
            'category' => 'Teknologi',
            'is_private' => 0,
            'content' => 'Tentang komunitas kami',
            'rules' => json_encode($rules),
            'moderators' => [$modUser->id],
        ]);

        $response->assertRedirect(route('communities.show', 'comm-multi-update'));

        $commFresh = $comm->fresh();
        $this->assertCount(2, $commFresh->rules);
        $this->assertEquals('Aturan Pertama', $commFresh->rules[0]['title']);
        $this->assertEquals('Tentang komunitas kami', $commFresh->about['content']);
        $this->assertTrue($commFresh->isModerator($modUser));
    }

    public function test_lookup_moderator_endpoint(): void
    {
        $creator = User::factory()->create();
        $comm = Community::create([
            'name' => 'comm-lookup-test',
            'created_by' => $creator->id,
            'is_private' => false,
        ]);
        $targetUser = User::factory()->create(['email' => 'target@example.com']);

        $res = $this->actingAs($creator)->postJson(route('communities.moderators.lookup', $comm->name), [
            'email' => 'target@example.com',
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('user.email', 'target@example.com');
    }
}
