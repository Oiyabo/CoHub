<?php

use App\Http\Controllers\CommentController;
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

require __DIR__.'/auth.php';