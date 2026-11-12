<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Aturan akses postingan (sesuai tabel "Peta Peran"):
 * - Guest      : hanya baca (tidak lewat policy, route show/feed terbuka)
 * - User       : buat post, edit/hapus milik sendiri
 * - Moderator  : hapus post orang lain, tapi hanya di komunitas yang dimoderasinya
 * - Admin      : hapus post siapa pun
 */
class PostPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            || $user->isAdmin()
            || $user->isModeratorOf($post->community_id);
    }
}
