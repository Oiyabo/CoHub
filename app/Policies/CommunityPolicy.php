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
        if (!$community->is_private) {
            return true;
        }
        if (!$user) {
            return false;
        }
        return $user->isAdmin() || $user->id === $community->created_by || $community->isMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Community $community): bool
    {
        return $user->id === $community->created_by || $user->isAdminOf($community->id);
    }

    public function delete(User $user, Community $community): bool
    {
        return $user->id === $community->created_by;
    }

    public function assignModerator(User $user, Community $community): bool
    {
        return $user->id === $community->created_by || $user->isAdminOf($community->id);
    }
    public function manageRules(User $user, Community $community): bool
    {
        return $user->isAuthorizedOf($community->id);
    }

    public function moderate(User $user, Community $community): bool
    {
        return $user->isAuthorizedOf($community->id);
    }
}
