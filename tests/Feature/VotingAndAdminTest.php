<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\SafeSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

        $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($user)->postJson(route('admin.users.update-role', $other), ['role' => 'admin'])->assertForbidden();
        $this->actingAs($user)->postJson(route('admin.users.toggle-block', $other))->assertForbidden();
    }

    public function test_admin_login_redirects_to_admin_dashboard_and_renders_all_tabs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Kewenangan Admin')
            ->assertSee('Aktivitas 7 hari terakhir');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Cari pengguna');
        $this->actingAs($admin)->get(route('admin.communities.index'))->assertOk()->assertSee('Komunitas');
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Laporan');
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->assertSee('1. ADMIN')->assertSee('2. MODERATOR');
    }

    public function test_admin_can_block_with_reason_hide_posts_restore_and_blocked_user_cannot_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $post = Post::create([
            'user_id' => $user->id,
            'title' => 'Postingan Pelanggaran',
            'content' => 'Spam konten.',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.users.toggle-block', $user), [
                'reason' => 'Menyebarkan spam',
                'hide_posts' => true,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'is_blocked' => true, 'posts_hidden' => true]);

        $this->assertSame('Menyebarkan spam', $user->fresh()->blocked_reason);

        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee($post->title);

        $this->post('/logout');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($admin)
            ->postJson(route('admin.users.toggle-block', $user))
            ->assertOk()
            ->assertJson(['success' => true, 'is_blocked' => false, 'posts_hidden' => false]);

        $this->actingAs($admin)->get(route('dashboard'))->assertSee($post->title);
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

    public function test_reports_can_be_created_reviewed_and_deleted_by_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $community = $this->createCommunity($author, 'komunitas-lapor');

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Postingan Spam',
            'content' => 'Konten spam.',
            'status' => 'published',
        ]);

        $this->actingAs($reporter)
            ->postJson(route('reports.store'), [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'Spam promosi',
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $report = Report::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Postingan Spam');

        $this->actingAs($admin)
            ->postJson(route('admin.reports.review', $report), ['status' => 'reviewed'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'reviewed']);

        $this->actingAs($admin)
            ->deleteJson(route('admin.reports.destroy', $report), ['delete_target' => true])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_app_and_admin_pages_do_not_crash_when_new_admin_schema_is_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'user']);
        $community = $this->createCommunity($member, 'komunitas-aman');

        Post::create([
            'user_id' => $member->id,
            'community_id' => $community->id,
            'title' => 'Postingan Aman',
            'content' => 'Tetap aman walau migrasi admin belum dijalankan.',
            'status' => 'published',
        ]);

        Schema::dropIfExists('reports');
        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn('is_closed');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['blocked_reason', 'posts_hidden', 'is_blocked']);
        });
        SafeSchema::flush();

        $this->actingAs($member)->get('/')->assertOk();
        $this->actingAs($member)->get(route('dashboard'))->assertOk();
        $this->actingAs($member)->get(route('search.index', ['q' => 'Aman']))->assertOk();
        $this->actingAs($member)->get(route('communities.index'))->assertOk();
        $this->actingAs($member)->get(route('communities.show', $community->name))->assertOk();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.communities.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
    }
}
