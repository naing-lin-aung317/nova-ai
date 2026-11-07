<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->authUser($request->user()),
            ],
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function authUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'preferred_language' => $user->preferred_language,
            'theme' => $user->theme,
        ];
    }
}
