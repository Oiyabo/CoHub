<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedPostController;
use App\Http\Controllers\VoteController;
use App\Models\Post;
use App\Support\PostFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;

Route::get('/', function (Request $request) {
    $tab = PostFeed::normalizeTab($request->query('tab'));
    $posts = PostFeed::get($tab);

    foreach ($posts as $post) {
        $post->buildCommentTree();
    }

    return view('welcome', compact('posts', 'tab'));
});

Route::get('/dashboard', function (Request $request) {
    $tab = PostFeed::normalizeTab($request->query('tab'));
    $posts = PostFeed::get($tab);

    foreach ($posts as $post) {
        $post->buildCommentTree();
    }

    return view('dashboard', compact('posts', 'tab'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/@{user:username}', [ProfileController::class, 'show'])
    ->name('profile.show');

Route::middleware('auth')->group(function () {
    Route::get('/search', [SearchController::class, 'index'])
    ->name('search.index');
    
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->name('comments.store');

    Route::post('/comments/{comment}/replies', [CommentController::class, 'reply'])
        ->name('comments.reply');

    Route::patch('/comments/{comment}', [CommentController::class, 'update'])
        ->name('comments.update');

    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy');

    Route::get('/saved', [SavedPostController::class, 'index'])
        ->name('saved.index');

    Route::post('/posts/{post}/save', [SavedPostController::class, 'store'])
        ->name('saved.store');

    Route::delete('/posts/{post}/save', [SavedPostController::class, 'destroy'])
        ->name('saved.destroy');

    Route::post('/reports', [AdminController::class, 'storeReport'])
        ->name('reports.store');
});

Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/posts/{post}/vote', [VoteController::class, 'votePost'])->name('api.posts.vote');
    Route::post('/comments/{comment}/vote', [VoteController::class, 'voteComment'])->name('api.comments.vote');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('index');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'manageUsers'])->name('users.index');
    Route::post('/users/{user}/toggle-block', [AdminController::class, 'toggleBlockUser'])->name('users.toggle-block');
    Route::post('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.update-role');
    Route::get('/communities', [AdminController::class, 'manageCommunities'])->name('communities.index');
    Route::post('/communities/{community}/toggle-close', [AdminController::class, 'toggleCloseCommunity'])->name('communities.toggle-close');
    Route::delete('/communities/{community}', [AdminController::class, 'deleteCommunity'])->name('communities.destroy');
    Route::get('/reports', [AdminController::class, 'manageReports'])->name('reports.index');
    Route::post('/reports/{report}/review', [AdminController::class, 'reviewReport'])->name('reports.review');
    Route::delete('/reports/{report}', [AdminController::class, 'deleteReport'])->name('reports.destroy');
    Route::get('/roles', [AdminController::class, 'manageRoles'])->name('roles.index');
});

Route::get('/communities', [CommunityController::class, 'index'])->name('communities.index');
Route::get('/explore', [CommunityController::class, 'index'])->name('communities.explore');
Route::get('/api/communities/check-name', [CommunityController::class, 'checkName'])->name('communities.check-name');

Route::middleware('auth')->group(function () {
    Route::get('/communities/create', [CommunityController::class, 'create'])->name('communities.create');
    Route::post('/communities', [CommunityController::class, 'store'])->name('communities.store');

    Route::get('/c/{community:name}/settings', [CommunityController::class, 'edit'])->name('communities.settings');
    Route::get('/c/{community:name}/edit', [CommunityController::class, 'edit'])->name('communities.edit');

    Route::put('/c/{community:name}', [CommunityController::class, 'update'])->name('communities.update');
    Route::delete('/c/{community:name}', [CommunityController::class, 'destroy'])->name('communities.destroy');

    Route::put('/c/{community:name}/about', [CommunityController::class, 'updateAbout'])->name('communities.about.update');

    Route::post('/c/{community:name}/rules', [CommunityController::class, 'addRule'])->name('communities.rules.add');
    Route::put('/c/{community:name}/rules/{ruleId}', [CommunityController::class, 'updateRule'])->name('communities.rules.update');
    Route::delete('/c/{community:name}/rules/{ruleId}', [CommunityController::class, 'deleteRule'])->name('communities.rules.delete');
    Route::post('/c/{community:name}/rules/reset-default', [CommunityController::class, 'resetDefaultRules'])->name('communities.rules.reset-default');

    Route::get('/c/{community:name}/moderators', [CommunityController::class, 'moderatorsIndex'])->name('communities.moderators.index');
    Route::get('/c/{community:name}/moderation', [ModerationController::class, 'index'])->name('communities.moderation.index');
    Route::post('/c/{community:name}/moderation/{report}/action', [ModerationController::class, 'handleAction'])->name('communities.moderation.action');
    Route::post('/c/{community:name}/moderators/lookup', [CommunityController::class, 'lookupModerator'])->name('communities.moderators.lookup');
    Route::post('/c/{community:name}/moderators', [CommunityController::class, 'addModerator'])->name('communities.moderators.add');
    Route::delete('/c/{community:name}/moderators/{user}', [CommunityController::class, 'removeModerator'])->name('communities.moderators.remove');

    Route::post('/c/{community:name}/join', [CommunityController::class, 'join'])->name('communities.join');
    Route::delete('/c/{community:name}/leave', [CommunityController::class, 'leave'])->name('communities.leave');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
});

require __DIR__ . '/posts.php';
Route::get('/c/{community:name}', [CommunityController::class, 'show'])->name('communities.show');

require __DIR__ . '/auth.php';
