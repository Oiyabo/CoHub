<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingAndAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createCommunity(User $creator, string $name = 'teknologi', bool $isClosed = false): Community
    {
        $community = Community::create([
            'name' => $name,
            'description' => 'Komunitas uji coba',
            'created_by' => $creator->id,
            'is_closed' => $isClosed,
        ]);

        $community->members()->attach($creator->id, ['role' => 'admin']);

        return $community;
    }

    public function test_user_can_upvote_toggle_switch_and_cancel_post_vote(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Postingan Uji Voting',
            'content' => 'Konten uji voting.',
        ]);

        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertOk()
            ->assertJson(['success' => true, 'score' => 1, 'user_vote' => 'upvote']);

        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'downvote'])
            ->assertOk()
            ->assertJson(['success' => true, 'score' => -1, 'user_vote' => 'downvote']);

        $this->actingAs($voter)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'downvote'])
            ->assertOk()
            ->assertJson(['success' => true, 'score' => 0, 'user_vote' => null]);
    }

    public function test_user_cannot_self_vote_post_or_comment(): void
    {
        $author = User::factory()->create();
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Post Sendiri',
            'content' => 'Konten sendiri.',
        ]);

        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Komentar sendiri.',
        ]);

        $this->actingAs($author)
            ->postJson(route('api.posts.vote', $post), ['vote_type' => 'upvote'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->actingAs($author)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'upvote'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_vote_comment_and_toggle(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $community = $this->createCommunity($author);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Postingan Diskusi',
            'content' => 'Konten diskusi.',
        ]);

        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Komentar bagus.',
        ]);

        $this->actingAs($voter)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'upvote'])
            ->assertOk()
            ->assertJson(['success' => true, 'score' => 1, 'user_vote' => 'upvote']);

        $this->actingAs($voter)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'downvote'])
            ->assertOk()
            ->assertJson(['success' => true, 'score' => -1, 'user_vote' => 'downvote']);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->postJson(route('admin.users.update-role', $other), ['role' => 'admin'])->assertForbidden();
        $this->actingAs($user)->postJson(route('admin.users.toggle-block', $other))->assertForbidden();
    }

    public function test_admin_login_redirects_to_admin_dashboard_and_renders_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Administrator Control Panel')
            ->assertSee('Panel Kontrol Admin');
    }

    public function test_admin_can_block_restore_user_and_blocked_user_cannot_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->postJson(route('admin.users.toggle-block', $user))
            ->assertOk()
            ->assertJson(['success' => true, 'is_blocked' => true]);

        $this->post('/logout');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($admin)
            ->postJson(route('admin.users.toggle-block', $user))
            ->assertOk()
            ->assertJson(['success' => true, 'is_blocked' => false]);
    }

    public function test_role_change_respects_authority_boundaries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $blockedUser = User::factory()->create(['role' => 'user', 'is_blocked' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.users.update-role', $admin), ['role' => 'user'])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson(route('admin.users.update-role', $blockedUser), ['role' => 'admin'])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson(route('admin.users.update-role', $user), ['role' => 'admin'])
            ->assertOk()
            ->assertJson(['success' => true, 'role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.users.update-role', $user), ['role' => 'user'])
            ->assertOk()
            ->assertJson(['success' => true, 'role' => 'user']);
    }

    public function test_admin_can_close_reopen_and_delete_community_and_closed_blocks_posts_and_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'user']);
        $community = $this->createCommunity($member, 'diskusi-uji');

        $post = Post::create([
            'user_id' => $member->id,
            'community_id' => $community->id,
            'title' => 'Post Sebelum Tutup',
            'content' => 'Konten awal.',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.communities.toggle-close', $community))
            ->assertOk()
            ->assertJson(['success' => true, 'is_closed' => true]);

        $this->actingAs($member)->post(route('posts.store'), [
            'community_id' => $community->id,
            'title' => 'Post Setelah Tutup',
            'content' => 'Tidak boleh.',
        ])->assertSessionHasErrors('community_id');

        $this->actingAs($member)->postJson(route('comments.store', $post), [
            'content' => 'Komentar saat ditutup.',
        ])->assertStatus(403);

        $this->actingAs($admin)
            ->postJson(route('admin.communities.toggle-close', $community))
            ->assertOk()
            ->assertJson(['success' => true, 'is_closed' => false]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.communities.destroy', $community))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('communities', ['id' => $community->id]);
    }
}
