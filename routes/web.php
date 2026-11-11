<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoteController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — CoHub
|--------------------------------------------------------------------------
*/

// ─── Halaman Utama ───────────────────────────────────────────────────────────
Route::get('/', function () {
    $posts = Post::with(['user', 'community', 'votes', 'comments.user'])->latest()->get();
    return view('welcome', compact('posts'));
});

Route::get('/dashboard', function () {
    $posts = Post::with(['user', 'community', 'votes', 'comments.user'])->latest()->get();
    return view('dashboard', compact('posts'));
})->middleware(['auth', 'verified'])->name('dashboard');

// ─── Profil ──────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── API Voting (memerlukan autentikasi) ─────────────────────────────────────
Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/posts/{post}/vote', [VoteController::class, 'votePost'])->name('api.posts.vote');
    Route::post('/comments/{comment}/vote', [VoteController::class, 'voteComment'])->name('api.comments.vote');
});

// ─── Dashboard Admin ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Manajemen Pengguna
    Route::get('/users', [AdminController::class, 'manageUsers'])->name('users.index');
    Route::post('/users/{user}/toggle-block', [AdminController::class, 'toggleBlockUser'])->name('users.toggle-block');

    // Manajemen Komunitas
    Route::get('/communities', [AdminController::class, 'manageCommunities'])->name('communities.index');
    Route::delete('/communities/{community}', [AdminController::class, 'deleteCommunity'])->name('communities.destroy');
});

require __DIR__.'/auth.php';

