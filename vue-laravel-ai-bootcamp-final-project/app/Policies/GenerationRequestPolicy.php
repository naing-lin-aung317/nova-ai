<?php

namespace App\Policies;

use App\Models\GenerationRequest;
use App\Models\User;

class GenerationRequestPolicy
{
    public function view(User $user, GenerationRequest $generationRequest): bool
    {
        return $user->id === $generationRequest->user_id;
    }

    public function stream(User $user, GenerationRequest $generationRequest): bool
    {
        return $user->id === $generationRequest->user_id;
    }

    public function cancel(User $user, GenerationRequest $generationRequest): bool
    {
        return $user->id === $generationRequest->user_id;
    }

    public function regenerate(User $user, GenerationRequest $generationRequest): bool
    {
        return $user->id === $generationRequest->user_id;
    }

    public function update(User $user, GenerationRequest $generationRequest): bool
    {
        return $user->id === $generationRequest->user_id;
    }

    public function delete(User $user, GenerationRequest $generationRequest): bool
    {
        return $user->id === $generationRequest->user_id;
    }
}
