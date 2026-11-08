<?php

namespace App\Policies;

use App\Models\CustomTone;
use App\Models\User;

class CustomTonePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CustomTone $customTone): bool
    {
        return $user->id === $customTone->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CustomTone $customTone): bool
    {
        return $user->id === $customTone->user_id;
    }

    public function delete(User $user, CustomTone $customTone): bool
    {
        return $user->id === $customTone->user_id;
    }
}
