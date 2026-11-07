<?php

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms' => ['required', 'accepted'],
            'preferred_language' => ['sometimes', Rule::in(['en', 'my'])],
            'theme' => ['sometimes', Rule::in(['light', 'dark'])],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => UserRole::User,
            'status' => UserStatus::PendingVerification,
            'preferred_language' => $input['preferred_language'] ?? 'en',
            'theme' => $input['theme'] ?? 'light',
        ]);
    }
}
