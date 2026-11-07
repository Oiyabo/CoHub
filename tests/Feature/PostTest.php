<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as private refreshDatabaseUnsafe;
    }

    /**
     * PENGAMAN: RefreshDatabase menjalankan migrate:fresh (menghapus semua tabel).
     * Tes hanya boleh jalan di SQLite, jangan pernah di database Supabase bersama.
     */
    public function refreshDatabase()
    {
        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('Tes dilewati: database aktif bukan SQLite (jangan jalankan di Supabase). Pastikan file .env.testing ada.');
        }

        $this->refreshDatabaseUnsafe();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Layout memakai @vite; di tes tidak perlu build asset.
        $this->withoutVite();
    }

    private function makeCommunity(?User $creator = null, string $name = 'laravel-indonesia'): Community
    {
        return Community::create([
            'name' => $name,
            'description' => 'Komunitas uji',
            'created_by' => ($creator ?? User::factory()->create())->id,
        ]);
    }

    private function makePost(User $author, Community $community, string $title = 'Judul uji'): Post
    {
        return Post::create([
            'user_id' => $author->id,
            'community_id' => $community->id,
            'title' => $title,
            'content' => 'Isi uji',
        ]);
    }

    // ---------------------------------------------------------------- Guest

    public function test_guest_can_read_a_post(): void
    {
        $author = User::factory()->create();
        $post = $this->makePost($author, $this->makeCommunity($author));

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Judul uji');
    }

    public function test_guest_cannot_create_post(): void
    {
        $community = $this->makeCommunity();

        $this->post(route('posts.store'), [
            'community_id' => $community->id,
            'title' => 'Judul',
            'content' => 'Isi',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('posts', 0);
    }

    // ----------------------------------------------------------------- User

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();
        $community = $this->makeCommunity($user);

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'community_id' => $community->id,
            'title' => 'Cara membuat relasi self-referencing',
            'content' => 'Bagaimana cara yang tepat?',
        ]);

        $post = Post::firstOrFail();
        $response->assertRedirect(route('posts.show', $post));
        $this->assertSame($user->id, $post->user_id);
    }

    public function test_post_content_is_limited_to_500_characters(): void
    {
        $user = User::factory()->create();
        $community = $this->makeCommunity($user);

        $this->actingAs($user)->post(route('posts.store'), [
            'community_id' => $community->id,
            'title' => 'Judul',
            'content' => str_repeat('a', 501),
        ])->assertSessionHasErrors('content');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_requires_an_existing_community(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'community_id' => 999,
            'title' => 'Judul',
            'content' => 'Isi',
        ])->assertSessionHasErrors('community_id');
    }

    public function test_author_can_update_own_post(): void
    {
        $author = User::factory()->create();
        $post = $this->makePost($author, $this->makeCommunity($author));

        $this->actingAs($author)->patch(route('posts.update', $post), [
            'title' => 'Judul baru',
            'content' => 'Isi baru',
        ])->assertRedirect(route('posts.show', $post));

        $this->assertSame('Judul baru', $post->fresh()->title);
    }

    public function test_user_cannot_edit_or_update_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->makePost($author, $this->makeCommunity($author));

        $this->actingAs($other)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($other)->patch(route('posts.update', $post), [
            'title' => 'Dibajak',
            'content' => 'Dibajak',
        ])->assertForbidden();

        $this->assertSame('Judul uji', $post->fresh()->title);
    }

    public function test_author_can_delete_own_post(): void
    {
        $author = User::factory()->create();
        $post = $this->makePost($author, $this->makeCommunity($author));

        $this->actingAs($author)->delete(route('posts.destroy', $post))
            ->assertRedirect(route('dashboard'));

        $this->assertModelMissing($post);
    }

    public function test_user_cannot_delete_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->makePost($author, $this->makeCommunity($author));

        $this->actingAs($other)->delete(route('posts.destroy', $post))->assertForbidden();

        $this->assertModelExists($post);
    }

    // ------------------------------------------------------------ Moderator

    public function test_moderator_can_delete_posts_only_in_their_own_community(): void
    {
        $author = User::factory()->create();
        $moderator = User::factory()->create();

        $own = $this->makeCommunity($author, 'komunitas-a');
        $other = $this->makeCommunity($author, 'komunitas-b');
        $moderator->moderatedCommunities()->attach($own->id);

        $postInOwn = $this->makePost($author, $own, 'Post di komunitas A');
        $postInOther = $this->makePost($author, $other, 'Post di komunitas B');

        $this->actingAs($moderator)->delete(route('posts.destroy', $postInOwn))
            ->assertRedirect(route('dashboard'));
        $this->assertModelMissing($postInOwn);

        $this->actingAs($moderator)->delete(route('posts.destroy', $postInOther))->assertForbidden();
        $this->assertModelExists($postInOther);
    }

    public function test_moderator_cannot_edit_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $moderator = User::factory()->create();
        $community = $this->makeCommunity($author);
        $moderator->moderatedCommunities()->attach($community->id);
        $post = $this->makePost($author, $community);

        $this->actingAs($moderator)->get(route('posts.edit', $post))->assertForbidden();
    }

    // ----------------------------------------------------------------- Admin

    public function test_admin_can_delete_any_post(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $post = $this->makePost($author, $this->makeCommunity($author));

        $this->actingAs($admin)->delete(route('posts.destroy', $post))
            ->assertRedirect(route('dashboard'));

        $this->assertModelMissing($post);
    }

    // ------------------------------------------------------------------ Feed

    public function test_feed_endpoint_is_public_and_rejects_unknown_tab_gracefully(): void
    {
        $author = User::factory()->create();
        $this->makePost($author, $this->makeCommunity($author), 'Post terbuka');

        $this->get(route('posts.feed', ['tab' => 'ngawur']))
            ->assertOk()
            ->assertSee('Post terbuka');
    }

    public function test_top_tab_orders_posts_by_score(): void
    {
        $author = User::factory()->create();
        $voters = User::factory()->count(3)->create();
        $community = $this->makeCommunity($author);

        $low = $this->makePost($author, $community, 'Post skor rendah');
        $high = $this->makePost($author, $community, 'Post skor tinggi');

        foreach ($voters as $voter) {
            Vote::create(['user_id' => $voter->id, 'post_id' => $high->id, 'vote_type' => 'upvote']);
        }
        Vote::create(['user_id' => $voters[0]->id, 'post_id' => $low->id, 'vote_type' => 'downvote']);

        $this->get(route('posts.feed', ['tab' => 'teratas']))
            ->assertOk()
            ->assertSeeInOrder(['Post skor tinggi', 'Post skor rendah']);
    }
}
