<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class CommunityPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Community $community): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Community $community): bool
    {
        return $user->id === $community->created_by;
    }

    public function delete(User $user, Community $community): bool
    {
        return $user->isAdmin();
    }

    public function assignModerator(User $user, Community $community): bool
    {
        return $user->id === $community->created_by;
    }
}
