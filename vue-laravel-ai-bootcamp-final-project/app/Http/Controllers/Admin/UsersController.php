<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function __construct(
        public WalletService $wallets,
        public AuditLogService $audit,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        $users = User::query()
            ->with('creditWallet:id,user_id,available_balance,reserved_balance')
            ->withCount('generationRequests')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'email_verified' => $user->email_verified_at !== null,
                'available_credits' => $user->creditWallet?->available_balance ?? 0,
                'generations_count' => (int) $user->generation_requests_count,
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        $activeCount = User::query()->where('status', UserStatus::Active)->count();

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => ['q' => $search],
            'maxAdjustment' => (int) config('nova.admin.max_credit_adjustment', 500),
            'summary' => [
                'total' => User::query()->count(),
                'active' => $activeCount,
            ],
        ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                UserStatus::Active->value,
                UserStatus::Suspended->value,
            ])],
        ]);

        $previous = $user->status->value;

        if ($previous === $data['status']) {
            return back();
        }

        $user->forceFill(['status' => UserStatus::from($data['status'])])->save();

        $action = $data['status'] === UserStatus::Suspended->value
            ? 'user.suspended'
            : 'user.unsuspended';

        $this->audit->record(
            actor: $request->user(),
            action: $action,
            subject: $user,
            reason: null,
            metadata: ['previous_status' => $previous, 'new_status' => $data['status']],
            request: $request,
        );

        return back();
    }

    public function verifyEmail(Request $request, User $user): RedirectResponse
    {
        if ($user->status !== UserStatus::PendingVerification) {
            return back()->withErrors([
                'user' => 'Only users pending email verification can be verified this way.',
            ]);
        }

        if ($user->email_verified_at !== null) {
            return back()->withErrors([
                'user' => 'This user is already verified.',
            ]);
        }

        $previousStatus = $user->status->value;

        $user->forceFill([
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
        ])->save();

        $this->audit->record(
            actor: $request->user(),
            action: 'user.email_verified',
            subject: $user,
            reason: 'Manual admin verification',
            metadata: [
                'previous_status' => $previousStatus,
                'email' => $user->email,
            ],
            request: $request,
        );

        return back()->with('status', 'admin.verifyEmailSuccess');
    }

    public function adjustCredits(Request $request, User $user): RedirectResponse
    {
        $max = (int) config('nova.admin.max_credit_adjustment', 500);

        $data = $request->validate([
            'delta' => ['required', 'integer', "min:-{$max}", "max:{$max}", 'not_in:0'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->wallets->adminAdjustment($user, (int) $data['delta'], $data['reason']);

        $this->audit->record(
            actor: $request->user(),
            action: 'wallet.adjusted',
            subject: $user,
            reason: $data['reason'],
            metadata: ['delta' => (int) $data['delta']],
            request: $request,
        );

        return back();
    }
}
