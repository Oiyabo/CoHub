<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ProfileController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $posts = Post::with([
        'user',
        'community',
        'votes',
        'comments.user',
    ])->latest()->get();

    foreach ($posts as $post) {
        $post->buildCommentTree();
    }

    return view('welcome', compact('posts'));
});

Route::get('/dashboard', function () {
    $posts = Post::with([
        'user',
        'community',
        'votes',
        'comments.user',
    ])->latest()->get();

    foreach ($posts as $post) {
        $post->buildCommentTree();
    }

    return view('dashboard', compact('posts'));
})->middleware(['auth', 'verified'])->name('dashboard');

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

    Route::get('/c/{community:name}/moderators', [CommunityController::class, 'moderatorsIndex'])->name('communities.moderators.index');
    Route::post('/c/{community:name}/moderators', [CommunityController::class, 'addModerator'])->name('communities.moderators.add');
    Route::delete('/c/{community:name}/moderators/{user}', [CommunityController::class, 'removeModerator'])->name('communities.moderators.remove');
});

require __DIR__.'/posts.php';
Route::get('/c/{community:name}', [CommunityController::class, 'show'])->name('communities.show');

require __DIR__.'/auth.php';
