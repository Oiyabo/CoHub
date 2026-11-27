<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\SafeSchema;
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
}
