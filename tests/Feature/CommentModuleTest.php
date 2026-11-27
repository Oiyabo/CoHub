<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentModuleTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function createPost(User $user, ?Community $community = null): Post
    {
        return Post::create([
            'user_id' => $user->id,
            'community_id' => $community?->id,
            'title' => 'Test Post',
            'content' => 'Test Content',
            'status' => 'published',
        ]);
    }

    public function test_post_detail_displays_max_10_initial_comments_and_shows_see_more_when_more_exist(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        // Create 15 top-level comments
        for ($i = 1; $i <= 15; $i++) {
            Comment::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'parent_id' => null,
                'content' => "Top level comment {$i}",
            ]);
        }

        $response = $this->get(route('posts.show', $post));
        $response->assertOk();

        // 1st to 10th comments should be visible
        $response->assertSee('Top level comment 1');
        $response->assertSee('Top level comment 10');

        // 11th comment should not be on initial view
        $response->assertDontSee('Top level comment 11');

        // "See more" button should be present
        $response->assertSee('See more');
        $response->assertSee('data-load-more-comments', false);
    }

    public function test_post_detail_hides_see_more_when_10_or_fewer_comments(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        // Create 5 top-level comments
        for ($i = 1; $i <= 5; $i++) {
            Comment::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'parent_id' => null,
                'content' => "Comment {$i}",
            ]);
        }

        $response = $this->get(route('posts.show', $post));
        $response->assertOk();

        $response->assertSee('Comment 1');
        $response->assertSee('Comment 5');
        $response->assertDontSee('data-load-more-comments', false);
    }

    public function test_ajax_comments_endpoint_paginates_next_comments_and_retains_replies(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        // Create 25 top-level comments
        for ($i = 1; $i <= 25; $i++) {
            $root = Comment::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'parent_id' => null,
                'content' => "Root comment {$i}",
            ]);

            if ($i === 12) {
                // Add reply to root 12
                Comment::create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'parent_id' => $root->id,
                    'content' => "Reply to Root 12",
                ]);
            }
        }

        // Fetch page 2 (comments 11..20)
        $response = $this->getJson(route('comments.index', ['post' => $post, 'page' => 2]));

        $response->assertOk();
        $response->assertJsonStructure([
            'html',
            'has_more',
            'next_page',
            'total',
            'count',
        ]);

        $this->assertTrue($response->json('has_more'));
        $this->assertSame(3, $response->json('next_page'));
        $this->assertSame(25, $response->json('total'));
        $this->assertSame(10, $response->json('count'));

        $html = $response->json('html');
        $this->assertStringContainsString('Root comment 11', $html);
        $this->assertStringContainsString('Root comment 12', $html);
        $this->assertStringContainsString('Reply to Root 12', $html);
        $this->assertStringContainsString('Root comment 20', $html);
        $this->assertStringNotContainsString('Root comment 10', $html);
        $this->assertStringNotContainsString('Root comment 21', $html);

        // Fetch page 3 (comments 21..25)
        $responsePage3 = $this->getJson(route('comments.index', ['post' => $post, 'page' => 3]));
        $responsePage3->assertOk();
        $this->assertFalse($responsePage3->json('has_more'));
        $this->assertSame(5, $responsePage3->json('count'));
    }

    public function test_nested_replies_are_closed_by_default_with_toggle_and_count(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        $root = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => null,
            'content' => 'Root parent comment',
        ]);

        // Add 3 replies
        for ($i = 1; $i <= 3; $i++) {
            Comment::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'parent_id' => $root->id,
                'content' => "Reply {$i}",
            ]);
        }

        $response = $this->get(route('posts.show', $post));
        $response->assertOk();

        // Must have x-data="{ open: false, count: 3 }"
        $response->assertSee('x-data="{ open: false, count: 3 }"', false);
        $response->assertSee('data-replies-group', false);
        $response->assertSee('Lihat 3 balasan');
    }

    public function test_cancel_buttons_are_present_with_type_button_on_all_forms(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => null,
            'content' => 'Sample comment for edit and reply',
        ]);

        $response = $this->actingAs($user)->get(route('posts.show', $post));
        $response->assertOk();

        // Main comment cancel button
        $response->assertSee('data-comment-cancel="store"', false);

        // Reply cancel button
        $response->assertSee('data-comment-cancel="reply"', false);

        // Edit cancel button
        $response->assertSee('data-comment-cancel="edit"', false);

        // All cancel buttons must be type="button"
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="button"[^>]*data-comment-cancel="store"[^>]*>[\s\S]*?Batal[\s\S]*?<\/button>/i',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="button"[^>]*data-comment-cancel="reply"[^>]*>[\s\S]*?Batal[\s\S]*?<\/button>/i',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="button"[^>]*data-comment-cancel="edit"[^>]*>[\s\S]*?Batal[\s\S]*?<\/button>/i',
            $response->getContent()
        );
    }

    public function test_soft_deleted_comment_tombstone_is_preserved_in_pagination(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        $deletedComment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => null,
            'content' => '',
        ]);
        $deletedComment->deleted_at = now();
        $deletedComment->save();

        $response = $this->get(route('posts.show', $post));
        $response->assertOk();
        $response->assertSee('[komentar telah dihapus]');

        $ajaxResponse = $this->getJson(route('comments.index', ['post' => $post, 'page' => 1]));
        $ajaxResponse->assertOk();
        $this->assertStringContainsString('[komentar telah dihapus]', $ajaxResponse->json('html'));
    }

    public function test_home_and_dashboard_only_display_posts_without_comment_list_or_form_and_card_is_clickable(): void
    {
        $user = $this->createUser();
        $post = $this->createPost($user);

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => null,
            'content' => 'Komentar Rahasia Beranda',
        ]);

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => $comment->id,
            'content' => 'Balasan Rahasia Beranda',
        ]);

        // 1. Welcome page (guest)
        $welcomeRes = $this->get('/');
        $welcomeRes->assertOk();
        $welcomeRes->assertSee($post->title);
        $welcomeRes->assertSee('2 Komentar');
        $welcomeRes->assertDontSee('Komentar Rahasia Beranda');
        $welcomeRes->assertDontSee('Balasan Rahasia Beranda');
        $welcomeRes->assertDontSee('Tulis komentarmu...');
        $welcomeRes->assertDontSee('data-comment-ajax="store"', false);
        $welcomeRes->assertSee('cursor-pointer');
        $welcomeRes->assertSee(route('posts.show', $post));

        // 2. Dashboard page (authenticated)
        $dashboardRes = $this->actingAs($user)->get(route('dashboard'));
        $dashboardRes->assertOk();
        $dashboardRes->assertSee($post->title);
        $dashboardRes->assertSee('2 Komentar');
        $dashboardRes->assertDontSee('Komentar Rahasia Beranda');
        $dashboardRes->assertDontSee('Balasan Rahasia Beranda');
        $dashboardRes->assertDontSee('Tulis komentarmu...');
        $dashboardRes->assertDontSee('data-comment-ajax="store"', false);
        $dashboardRes->assertSee('cursor-pointer');
        $dashboardRes->assertSee(route('posts.show', $post));

        // 3. Post Detail page (comments and form must be present)
        $detailRes = $this->actingAs($user)->get(route('posts.show', $post));
        $detailRes->assertOk();
        $detailRes->assertSee($post->title);
        $detailRes->assertSee('Komentar Rahasia Beranda');
        $detailRes->assertSee('Balasan Rahasia Beranda');
        $detailRes->assertSee('Tulis komentarmu...');
        $detailRes->assertSee('data-comment-ajax="store"', false);
    }
}
