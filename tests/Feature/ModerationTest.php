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

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createCommunityWithModerator(User $creator, ?User $moderator = null, string $name = 'komunitas-uji'): Community
    {
        $community = Community::create([
            'name' => $name,
            'description' => 'Deskripsi komunitas uji',
            'created_by' => $creator->id,
        ]);

        $community->members()->attach($creator->id, ['role' => 'admin']);

        if ($moderator) {
            $community->members()->attach($moderator->id, ['role' => 'moderator']);
        }

        return $community;
    }

    public function test_moderation_queue_access_is_isolated_to_community_moderators_creators_and_admins(): void
    {
        $creatorA = User::factory()->create();
        $moderatorA = User::factory()->create();
        $moderatorB = User::factory()->create();
        $memberA = User::factory()->create();
        $outsider = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $communityA = $this->createCommunityWithModerator($creatorA, $moderatorA, 'komunitas-alpha');
        $communityA->members()->attach($memberA->id, ['role' => 'member']);

        $this->createCommunityWithModerator(User::factory()->create(), $moderatorB, 'komunitas-beta');

        $this->get(route('communities.moderation.index', $communityA->name))
            ->assertRedirect();

        $this->actingAs($outsider)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertForbidden();

        $this->actingAs($memberA)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertForbidden();

        $this->actingAs($moderatorB)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertForbidden();

        $this->actingAs($moderatorA)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertOk()
            ->assertSee('Ruang Moderasi');

        $this->actingAs($creatorA)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertOk();
    }

    public function test_community_moderation_queue_shows_only_reported_posts_and_comments_of_that_community(): void
    {
        $creatorA = User::factory()->create();
        $moderatorA = User::factory()->create();
        $author = User::factory()->create(['name' => 'Penulis Konten', 'username' => 'penulis_a']);
        $reporterOne = User::factory()->create(['name' => 'Pelapor Satu', 'username' => 'pelapor_1']);
        $reporterTwo = User::factory()->create(['name' => 'Pelapor Dua', 'username' => 'pelapor_2']);

        $communityA = $this->createCommunityWithModerator($creatorA, $moderatorA, 'komunitas-mod-a');
        $communityB = $this->createCommunityWithModerator(User::factory()->create(), null, 'komunitas-mod-b');

        $postA = Post::create([
            'user_id' => $author->id,
            'community_id' => $communityA->id,
            'title' => 'Postingan Terlapor Komunitas A',
            'content' => 'Konten melanggar aturan A.',
            'status' => 'published',
        ]);

        $commentA = Comment::create([
            'user_id' => $author->id,
            'post_id' => $postA->id,
            'content' => 'Komentar kasar di komunitas A',
        ]);

        $postB = Post::create([
            'user_id' => $author->id,
            'community_id' => $communityB->id,
            'title' => 'Postingan Terlapor Komunitas B',
            'content' => 'Konten melanggar aturan B.',
            'status' => 'published',
        ]);

        Report::create([
            'reporter_id' => $reporterOne->id,
            'reportable_type' => 'post',
            'reportable_id' => $postA->id,
            'reason' => 'Spam berulang',
            'status' => 'new',
        ]);

        Report::create([
            'reporter_id' => $reporterTwo->id,
            'reportable_type' => 'post',
            'reportable_id' => $postA->id,
            'reason' => 'Promosi terlarang',
            'status' => 'new',
        ]);

        Report::create([
            'reporter_id' => $reporterOne->id,
            'reportable_type' => 'comment',
            'reportable_id' => $commentA->id,
            'reason' => 'Komentar tidak sopan',
            'status' => 'resolved',
        ]);

        Report::create([
            'reporter_id' => $reporterOne->id,
            'reportable_type' => 'post',
            'reportable_id' => $postB->id,
            'reason' => 'Spam komunitas B',
            'status' => 'new',
        ]);

        Report::create([
            'reporter_id' => $reporterOne->id,
            'reportable_type' => 'user',
            'reportable_id' => $author->id,
            'reason' => 'Akun bermasalah global',
            'status' => 'new',
        ]);

        $this->actingAs($moderatorA)
            ->get(route('communities.show', $communityA->name))
            ->assertOk()
            ->assertSee('Ruang Moderasi')
            ->assertSee(route('communities.moderation.index', $communityA->name));

        $this->actingAs($moderatorA)
            ->get(route('communities.moderation.index', $communityA->name))
            ->assertOk()
            ->assertSee('Postingan Terlapor Komunitas A')
            ->assertSee('2 laporan')
            ->assertDontSee('Postingan Terlapor Komunitas B')
            ->assertDontSee('Akun bermasalah global')
            ->assertDontSee('Komentar kasar di komunitas A');

        $this->actingAs($moderatorA)
            ->get(route('communities.moderation.index', [
                'community' => $communityA->name,
                'status' => 'resolved',
                'type' => 'comment',
            ]))
            ->assertOk()
            ->assertSee('Komentar kasar di komunitas A')
            ->assertDontSee('Spam berulang')
            ->assertDontSee('Promosi terlarang');

        $this->actingAs($moderatorA)
            ->get(route('communities.moderation.index', [
                'community' => $communityA->name,
                'status' => 'all',
                'search' => 'Pelapor Dua',
            ]))
            ->assertOk()
            ->assertSee('Postingan Terlapor Komunitas A')
            ->assertDontSee('Komentar kasar di komunitas A');
    }

    public function test_moderator_can_hide_approve_delete_and_dismiss_reported_post_in_own_community(): void
    {
        $creator = User::factory()->create();
        $moderator = User::factory()->create();
        $author = User::factory()->create(['username' => 'author_post_mod']);
        $reporter = User::factory()->create();
        $otherMember = User::factory()->create();

        $community = $this->createCommunityWithModerator($creator, $moderator, 'komunitas-aksi-post');
        $community->members()->attach($otherMember->id, ['role' => 'member']);

        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Postingan Diuji Moderasi',
            'content' => 'Konten postingan uji.',
            'status' => 'published',
        ]);

        $secondPost = Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => 'Postingan Tetap Aman',
            'content' => 'Konten aman.',
            'status' => 'published',
        ]);

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'Spam konten',
            'status' => 'new',
        ]);

        $secondReport = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => 'post',
            'reportable_id' => $secondPost->id,
            'reason' => 'Laporan keliru',
            'status' => 'new',
        ]);

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'hide',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'hidden',
                'report_status' => 'resolved',
            ]);

        $this->assertSame('hidden', $post->fresh()->status);
        $this->assertSame('resolved', $report->fresh()->status);

        $this->get('/')->assertOk()->assertDontSee('Postingan Diuji Moderasi');
        $this->actingAs($otherMember)->get(route('communities.show', $community->name))->assertOk()->assertDontSee('Postingan Diuji Moderasi');
        $this->actingAs($otherMember)->get(route('search.index', ['q' => 'Diuji']))->assertOk()->assertDontSee('Postingan Diuji Moderasi');
        $this->actingAs($otherMember)->get(route('profile.show', ['user' => $author->username]))->assertOk()->assertDontSee('Postingan Diuji Moderasi');
        $this->actingAs($otherMember)->get(route('posts.show', $post))->assertNotFound();

        $this->actingAs($moderator)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Postingan ini sedang disembunyikan oleh moderator');

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'approve',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'published',
                'report_status' => 'resolved',
            ]);

        $this->assertSame('published', $post->fresh()->status);
        $this->actingAs($otherMember)->get(route('posts.show', $post))->assertOk()->assertSee('Postingan Diuji Moderasi');

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $secondReport->id]), [
                'action' => 'dismiss',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'published',
                'report_status' => 'dismissed',
            ]);

        $this->assertSame('published', $secondPost->fresh()->status);
        $this->assertSame('dismissed', $secondReport->fresh()->status);

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'delete',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'deleted',
                'report_status' => 'resolved',
            ]);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_moderator_can_hide_restore_delete_and_dismiss_reported_comment_in_own_community(): void
    {
        $creator = User::factory()->create();
        $moderator = User::factory()->create();
        $author = User::factory()->create();
        $reporter = User::factory()->create();

        $community = $this->createCommunityWithModerator($creator, $moderator, 'komunitas-aksi-komentar');

        $post = Post::create([
            'user_id' => $creator->id,
            'community_id' => $community->id,
            'title' => 'Postingan Dengan Komentar',
            'content' => 'Isi postingan.',
            'status' => 'published',
        ]);

        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Komentar bermasalah asli',
        ]);

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => 'Bahasa kasar',
            'status' => 'new',
        ]);

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'hide',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'hidden',
                'report_status' => 'resolved',
            ]);

        $this->assertTrue($comment->fresh()->isHidden());

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('[komentar disembunyikan oleh moderator]')
            ->assertDontSee('Komentar bermasalah asli');

        $this->actingAs($reporter)
            ->postJson(route('comments.reply', $comment), ['content' => 'Balasan'])
            ->assertStatus(422);

        $this->actingAs($reporter)
            ->postJson(route('api.comments.vote', $comment), ['vote_type' => 'upvote'])
            ->assertStatus(403);

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'approve',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'published',
            ]);

        $this->assertFalse($comment->fresh()->isHidden());
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Komentar bermasalah asli');

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'delete',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'target_state' => 'deleted',
            ]);

        $this->assertNotNull($comment->fresh()->deleted_at);
        $this->assertSame('', $comment->fresh()->content);
    }

    public function test_moderation_actions_are_blocked_across_different_communities_and_for_regular_members(): void
    {
        $modA = User::factory()->create();
        $memberA = User::factory()->create();
        $authorB = User::factory()->create();

        $communityA = $this->createCommunityWithModerator(User::factory()->create(), $modA, 'mod-iso-alpha');
        $communityA->members()->attach($memberA->id, ['role' => 'member']);

        $communityB = $this->createCommunityWithModerator(User::factory()->create(), null, 'mod-iso-beta');

        $postA = Post::create([
            'user_id' => $memberA->id,
            'community_id' => $communityA->id,
            'title' => 'Post A',
            'content' => 'Isi A',
            'status' => 'published',
        ]);

        $postB = Post::create([
            'user_id' => $authorB->id,
            'community_id' => $communityB->id,
            'title' => 'Post B',
            'content' => 'Isi B',
            'status' => 'published',
        ]);

        $reportA = Report::create([
            'reporter_id' => $authorB->id,
            'reportable_type' => 'post',
            'reportable_id' => $postA->id,
            'reason' => 'Spam A',
            'status' => 'new',
        ]);

        $reportB = Report::create([
            'reporter_id' => $modA->id,
            'reportable_type' => 'post',
            'reportable_id' => $postB->id,
            'reason' => 'Spam B',
            'status' => 'new',
        ]);

        $userReport = Report::create([
            'reporter_id' => $modA->id,
            'reportable_type' => 'user',
            'reportable_id' => $authorB->id,
            'reason' => 'Akun B',
            'status' => 'new',
        ]);

        $this->actingAs($memberA)
            ->postJson(route('communities.moderation.action', ['community' => $communityA->name, 'report' => $reportA->id]), [
                'action' => 'hide',
            ])
            ->assertForbidden();

        $this->actingAs($modA)
            ->postJson(route('communities.moderation.action', ['community' => $communityA->name, 'report' => $reportB->id]), [
                'action' => 'delete',
            ])
            ->assertForbidden();

        $this->actingAs($modA)
            ->postJson(route('communities.moderation.action', ['community' => $communityA->name, 'report' => $userReport->id]), [
                'action' => 'dismiss',
            ])
            ->assertForbidden();

        $this->assertSame('published', $postA->fresh()->status);
        $this->assertSame('published', $postB->fresh()->status);
        $this->assertSame('new', $reportB->fresh()->status);
        $this->assertSame('new', $userReport->fresh()->status);
    }

    public function test_community_moderation_queue_does_not_crash_when_reports_table_is_missing(): void
    {
        $creator = User::factory()->create();
        $moderator = User::factory()->create();
        $community = $this->createCommunityWithModerator($creator, $moderator, 'komunitas-tanpa-tabel');

        Schema::dropIfExists('reports');
        SafeSchema::flush();

        $this->actingAs($moderator)
            ->get(route('communities.moderation.index', $community->name))
            ->assertOk();
    }

    public function test_comment_hide_degrades_gracefully_when_is_hidden_column_is_missing(): void
    {
        $creator = User::factory()->create();
        $moderator = User::factory()->create();
        $reporter = User::factory()->create();
        $community = $this->createCommunityWithModerator($creator, $moderator, 'komunitas-tanpa-kolom');

        $post = Post::create([
            'user_id' => $creator->id,
            'community_id' => $community->id,
            'title' => 'Postingan Uji Schema',
            'content' => 'Konten',
            'status' => 'published',
        ]);

        $comment = Comment::create([
            'user_id' => $reporter->id,
            'post_id' => $post->id,
            'content' => 'Komentar uji',
        ]);

        $report = Report::create([
            'reporter_id' => $creator->id,
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => 'Spam',
            'status' => 'new',
        ]);

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
        SafeSchema::flush();

        $this->actingAs($moderator)
            ->get(route('posts.show', $post))
            ->assertOk();

        $this->actingAs($moderator)
            ->get(route('communities.moderation.index', $community->name))
            ->assertOk();

        $this->actingAs($moderator)
            ->postJson(route('communities.moderation.action', ['community' => $community->name, 'report' => $report->id]), [
                'action' => 'hide',
            ])
            ->assertStatus(503);
    }
}
