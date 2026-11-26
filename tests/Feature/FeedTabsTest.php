<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R-08: tab "Untuk Anda" memprioritaskan postingan dari komunitas yang diikuti.
 * Juga memastikan tab "Terbaru" tetap murni urut waktu (konsisten dengan R-07).
 */
class FeedTabsTest extends TestCase
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

    private function makeCommunity(string $name, User $creator, bool $private = false): Community
    {
        $community = Community::create([
            'name' => $name,
            'description' => 'Komunitas uji',
            'is_private' => $private,
            'created_by' => $creator->id,
        ]);

        // Sama seperti saat komunitas dibuat lewat aplikasi: pembuat otomatis jadi anggota (admin).
        $community->members()->attach($creator->id, ['role' => 'admin']);

        return $community;
    }

    private function makePost(User $author, ?Community $community, string $title, int $daysAgo = 0): Post
    {
        $post = Post::create([
            'user_id' => $author->id,
            'community_id' => $community?->id,
            'title' => $title,
            'content' => 'Isi uji',
        ]);

        // Atur waktu pembuatan tanpa menyentuh timestamp otomatis.
        $time = now()->subDays($daysAgo);
        Post::whereKey($post->id)->update(['created_at' => $time, 'updated_at' => $time]);

        return $post->refresh();
    }

    /**
     * Urutan ID postingan yang dikembalikan endpoint tab (dipakai tombol tab di beranda).
     *
     * @return array<int, int>
     */
    private function feedIds(string $tab): array
    {
        $ids = $this->getJson(route('posts.feed.order', ['tab' => $tab]))
            ->assertOk()
            ->json('ids');

        return array_map('intval', $ids);
    }

    public function test_untuk_anda_shows_posts_from_joined_communities_first(): void
    {
        $me = User::factory()->create();
        $author = User::factory()->create();
        $joined = $this->makeCommunity('komunitas-diikuti', $author);
        $other = $this->makeCommunity('komunitas-lain', $author);
        $joined->members()->attach($me->id, ['role' => 'member']);

        $inJoined = $this->makePost($author, $joined, 'Postingan komunitas diikuti', daysAgo: 3);
        $inOther = $this->makePost($author, $other, 'Postingan komunitas lain', daysAgo: 0);

        $this->actingAs($me);

        // Meskipun lebih lama, postingan dari komunitas yang diikuti tampil lebih dulu.
        $this->assertSame([$inJoined->id, $inOther->id], $this->feedIds('untuk-anda'));
    }

    public function test_terbaru_ignores_membership_and_is_pure_chronological(): void
    {
        $me = User::factory()->create();
        $author = User::factory()->create();
        $joined = $this->makeCommunity('komunitas-diikuti', $author);
        $other = $this->makeCommunity('komunitas-lain', $author);
        $joined->members()->attach($me->id, ['role' => 'member']);

        $inJoined = $this->makePost($author, $joined, 'Postingan komunitas diikuti', daysAgo: 3);
        $inOther = $this->makePost($author, $other, 'Postingan komunitas lain', daysAgo: 0);

        $this->actingAs($me);

        $this->assertSame([$inOther->id, $inJoined->id], $this->feedIds('terbaru'));
    }

    public function test_posts_without_a_community_are_not_prioritized(): void
    {
        $me = User::factory()->create();
        $author = User::factory()->create();
        $joined = $this->makeCommunity('komunitas-diikuti', $author);
        $other = $this->makeCommunity('komunitas-lain', $author);
        $joined->members()->attach($me->id, ['role' => 'member']);

        $inJoined = $this->makePost($author, $joined, 'Di komunitas diikuti', daysAgo: 3);
        $general = $this->makePost($author, null, 'Postingan umum', daysAgo: 0);
        $inOther = $this->makePost($author, $other, 'Di komunitas lain', daysAgo: 1);

        $this->actingAs($me);

        $this->assertSame([$inJoined->id, $general->id, $inOther->id], $this->feedIds('untuk-anda'));
        $this->assertSame([$general->id, $inOther->id, $inJoined->id], $this->feedIds('terbaru'));
    }

    public function test_user_without_joined_communities_gets_the_same_order_as_terbaru(): void
    {
        $me = User::factory()->create();
        $author = User::factory()->create();
        $community = $this->makeCommunity('komunitas-lain', $author);

        $this->makePost($author, $community, 'Lama', daysAgo: 5);
        $this->makePost($author, $community, 'Baru', daysAgo: 0);
        $this->makePost($author, null, 'Tengah', daysAgo: 2);

        $this->actingAs($me);

        $this->assertSame($this->feedIds('terbaru'), $this->feedIds('untuk-anda'));
    }

    public function test_guest_can_load_untuk_anda(): void
    {
        $author = User::factory()->create();
        $community = $this->makeCommunity('komunitas-umum', $author);
        $post = $this->makePost($author, $community, 'Bisa dibaca guest');

        $this->assertContains($post->id, $this->feedIds('untuk-anda'));
    }

    public function test_leaving_a_community_removes_its_priority(): void
    {
        $me = User::factory()->create();
        $author = User::factory()->create();
        $joined = $this->makeCommunity('komunitas-diikuti', $author);
        $other = $this->makeCommunity('komunitas-lain', $author);

        $inJoined = $this->makePost($author, $joined, 'Postingan komunitas diikuti', daysAgo: 3);
        $inOther = $this->makePost($author, $other, 'Postingan komunitas lain', daysAgo: 0);

        $this->actingAs($me);

        // Sebelum gabung: urut waktu biasa.
        $this->assertSame([$inOther->id, $inJoined->id], $this->feedIds('untuk-anda'));

        // Setelah gabung: komunitas yang diikuti naik ke atas.
        $this->post(route('communities.join', $joined->name));
        $this->assertSame([$inJoined->id, $inOther->id], $this->feedIds('untuk-anda'));

        // Setelah keluar: kembali urut waktu biasa.
        $this->delete(route('communities.leave', $joined->name));
        $this->assertSame([$inOther->id, $inJoined->id], $this->feedIds('untuk-anda'));
    }

    public function test_private_community_posts_stay_hidden_from_non_members_in_untuk_anda(): void
    {
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $author = User::factory()->create();
        $private = $this->makeCommunity('komunitas-privat', $author, private: true);
        $private->members()->attach($member->id, ['role' => 'member']);

        $secret = $this->makePost($author, $private, 'Rahasia komunitas privat');

        $this->actingAs($outsider);
        $this->assertNotContains($secret->id, $this->feedIds('untuk-anda'));

        $this->actingAs($member);
        $this->assertContains($secret->id, $this->feedIds('untuk-anda'));
    }

    public function test_dashboard_renders_posts_in_untuk_anda_order_by_default(): void
    {
        $me = User::factory()->create();
        $author = User::factory()->create();
        $joined = $this->makeCommunity('komunitas-diikuti', $author);
        $other = $this->makeCommunity('komunitas-lain', $author);
        $joined->members()->attach($me->id, ['role' => 'member']);

        $this->makePost($author, $joined, 'Judul dari komunitas diikuti', daysAgo: 3);
        $this->makePost($author, $other, 'Judul dari komunitas lain', daysAgo: 0);

        $this->actingAs($me)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Judul dari komunitas diikuti', 'Judul dari komunitas lain']);
    }
}
