<?php

use App\Http\Controllers\ProfileController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $posts = Post::with(['user', 'community', 'votes', 'comments.user'])->latest()->get();
    return view('welcome', compact('posts'));
});

Route::get('/dashboard', function () {
    $posts = Post::with(['user', 'community', 'votes', 'comments.user'])->latest()->get();
    return view('dashboard', compact('posts'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/posts.php';
require __DIR__.'/auth.php';
