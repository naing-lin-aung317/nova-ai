<?php

namespace App\Services;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\CreditWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public const SIGNUP_BONUS_CREDITS = 20;

    public function ensureWallet(User $user): CreditWallet
    {
        return CreditWallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'available_balance' => 0,
                'reserved_balance' => 0,
            ],
        );
    }

    public function grantSignupBonus(User $user): bool
    {
        if ($user->email_verified_at === null) {
            return false;
        }

        $idempotencyKey = $this->signupBonusIdempotencyKey($user);

        if (CreditTransaction::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            return false;
        }

        return DB::transaction(function () use ($user, $idempotencyKey): bool {
            $wallet = CreditWallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($user);
                $wallet = CreditWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            if (CreditTransaction::query()
                ->where('credit_wallet_id', $wallet->id)
                ->where('type', CreditTransactionType::SignupBonus)
                ->exists()) {
                return false;
            }

            $wallet->available_balance += self::SIGNUP_BONUS_CREDITS;
            $wallet->save();

            CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => CreditTransactionType::SignupBonus,
                'amount' => self::SIGNUP_BONUS_CREDITS,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Signup bonus',
            ]);

            return true;
        });
    }

    /**
     * @return array{available: int, reserved: int}|null
     */
    public function balancesForUser(User $user): ?array
    {
        $wallet = CreditWallet::query()->where('user_id', $user->id)->first();

        if ($wallet === null) {
            return [
                'available' => 0,
                'reserved' => 0,
            ];
        }

        return [
            'available' => $wallet->available_balance,
            'reserved' => $wallet->reserved_balance,
        ];
    }

    public function anonymiseForDeletedUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $wallet = CreditWallet::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($wallet === null) {
                return;
            }

            $now = now();

            CreditTransaction::query()
                ->where('credit_wallet_id', $wallet->id)
                ->update([
                    'user_id' => null,
                    'metadata' => null,
                    'anonymised_at' => $now,
                ]);

            $wallet->forceFill([
                'user_id' => null,
                'anonymised_at' => $now,
            ])->save();
        });
    }

    public function ledgerMatchesWallet(CreditWallet $wallet): bool
    {
        $ledgerSum = (int) CreditTransaction::query()
            ->where('credit_wallet_id', $wallet->id)
            ->sum('amount');

        return $ledgerSum === $wallet->available_balance;
    }

    public function signupBonusIdempotencyKey(User $user): string
    {
        return 'signup_bonus:'.$user->id;
    }
}
