<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedPostController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    $posts = Post::where('status', 'published')
        ->with([
            'user',
            'community',
            'votes',
            'comments.user',
        ])
        ->latest()
        ->get();

    foreach ($posts as $post) {
        $post->buildCommentTree();
    }

    return view('welcome', compact('posts'));
});

Route::get('/dashboard', function () {
    $posts = Post::where('status', 'published')
        ->with([
            'user',
            'community',
            'votes',
            'comments.user',
        ])
        ->latest()
        ->get();

    foreach ($posts as $post) {
        $post->buildCommentTree();
    }

    return view('dashboard', compact('posts'));
})->middleware(['auth', 'verified'])->name('dashboard');

// Public User Profile
Route::get('/@{user:username}', [ProfileController::class, 'show'])
    ->name('profile.show');

Route::middleware('auth')->group(function () {
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
    // Saved Posts
    Route::get('/saved', [SavedPostController::class, 'index'])
        ->name('saved.index');

    Route::post('/posts/{post}/save', [SavedPostController::class, 'store'])
        ->name('saved.store');

    Route::delete('/posts/{post}/save', [SavedPostController::class, 'destroy'])
        ->name('saved.destroy');
});

// Community Management
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
    Route::post('/c/{community:name}/moderators', [CommunityController::class, 'addModerator'])->name('communities.moderators.add');
    Route::delete('/c/{community:name}/moderators/{user}', [CommunityController::class, 'removeModerator'])->name('communities.moderators.remove');

    Route::post('/c/{community:name}/join', [CommunityController::class, 'join'])->name('communities.join');
    Route::delete('/c/{community:name}/leave', [CommunityController::class, 'leave'])->name('communities.leave');

    // Notifications
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
