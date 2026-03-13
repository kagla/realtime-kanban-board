<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Board $board): bool
    {
        return $board->hasMember($user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Board $board): bool
    {
        $role = $board->getMemberRole($user->id);

        return in_array($role, ['owner', 'editor']);
    }

    public function delete(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }
}
