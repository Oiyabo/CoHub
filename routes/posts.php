<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route modul Postingan & Feed (Rizka)
|--------------------------------------------------------------------------
| Dimuat dari routes/web.php lewat satu baris:
|     require __DIR__.'/posts.php';
*/

// Publik (Guest boleh baca)
Route::get('/feed', [PostController::class, 'feed'])->name('posts.feed');
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->whereNumber('post')
    ->name('posts.show');

// Wajib login
Route::middleware('auth')->group(function () {
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])
        ->whereNumber('post')
        ->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])
        ->whereNumber('post')
        ->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])
        ->whereNumber('post')
        ->name('posts.destroy');
});
