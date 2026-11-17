<?php

namespace App\Services;

use App\Enums\CreditReservationStatus;
use App\Enums\CreditTransactionType;
use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditReservation;
use App\Models\CreditTransaction;
use App\Models\CreditWallet;
use App\Models\GenerationRequest;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function reserveForGeneration(
        User $user,
        GenerationRequest $generation,
        string $idempotencyKey,
    ): CreditReservation {
        return DB::transaction(function () use ($user, $generation, $idempotencyKey): CreditReservation {
            $existing = CreditReservation::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $wallet = CreditWallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($user);
                $wallet = CreditWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            if ($wallet->available_balance < 1) {
                throw new InsufficientCreditsException;
            }

            $wallet->available_balance -= 1;
            $wallet->reserved_balance += 1;
            $wallet->save();

            CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => CreditTransactionType::Reserve,
                'amount' => 0,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Reserved for generation',
                'metadata' => ['generation_request_id' => $generation->id],
            ]);

            $timeoutMinutes = (int) config('nova.gemini.reservation_timeout_minutes', 5);

            return CreditReservation::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'generation_request_id' => $generation->id,
                'amount' => 1,
                'status' => CreditReservationStatus::Pending,
                'idempotency_key' => $idempotencyKey,
                'expires_at' => now()->addMinutes($timeoutMinutes),
            ]);
        });
    }

    public function captureReservation(CreditReservation $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            $reservation = CreditReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->status !== CreditReservationStatus::Pending) {
                return;
            }

            $wallet = CreditWallet::query()
                ->whereKey($reservation->credit_wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->reserved_balance < 1) {
                return;
            }

            $wallet->reserved_balance -= 1;
            $wallet->save();

            $reservation->forceFill([
                'status' => CreditReservationStatus::Captured,
                'captured_at' => now(),
            ])->save();

            CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $reservation->user_id,
                'type' => CreditTransactionType::Capture,
                'amount' => -1,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => $reservation->idempotency_key.':capture',
                'description' => 'Generation completed',
                'metadata' => ['generation_request_id' => $reservation->generation_request_id],
            ]);
        });
    }

    public function adminAdjustment(User $user, int $delta, string $reason): CreditTransaction
    {
        $max = (int) config('nova.admin.max_credit_adjustment', 500);

        if (abs($delta) > $max) {
            throw ValidationException::withMessages([
                'delta' => ["Adjustment cannot exceed ±{$max} credits."],
            ]);
        }

        if ($delta === 0) {
            throw ValidationException::withMessages([
                'delta' => ['Adjustment must not be zero.'],
            ]);
        }

        return DB::transaction(function () use ($user, $delta, $reason): CreditTransaction {
            $wallet = CreditWallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($user);
                $wallet = CreditWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $wallet->available_balance += $delta;
            $wallet->save();

            return CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => CreditTransactionType::AdminAdjustment,
                'amount' => $delta,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => 'admin_adjustment:'.Str::uuid()->toString(),
                'description' => $reason,
                'metadata' => ['reason' => $reason],
            ]);
        });
    }

    /**
     * @return list<int>
     */
    public function walletIdsWithLedgerMismatch(): array
    {
        $mismatchIds = [];

        CreditWallet::query()
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($wallets) use (&$mismatchIds): void {
                foreach ($wallets as $wallet) {
                    if (! $this->ledgerMatchesWallet($wallet)) {
                        $mismatchIds[] = $wallet->id;
                    }
                }
            });

        return $mismatchIds;
    }

    public function grantPurchase(PaymentOrder $order): CreditTransaction
    {
        return DB::transaction(function () use ($order): CreditTransaction {
            $order = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $idempotencyKey = 'purchase:order:'.$order->id;

            $existing = CreditTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $wallet = CreditWallet::query()
                ->where('user_id', $order->user_id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($order->user);
                $wallet = CreditWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $wallet->available_balance += $order->credits_to_issue;
            $wallet->save();

            return CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $order->user_id,
                'type' => CreditTransactionType::Purchase,
                'amount' => $order->credits_to_issue,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Credit package purchase',
                'metadata' => [
                    'payment_order_id' => $order->id,
                    'credit_package_id' => $order->credit_package_id,
                ],
            ]);
        });
    }

    public function clawBackPurchase(
        PaymentOrder $order,
        CreditTransactionType $type,
        string $description,
    ): ?CreditTransaction {
        if ($type !== CreditTransactionType::Refund && $type !== CreditTransactionType::Chargeback) {
            throw new \InvalidArgumentException('Clawback type must be refund or chargeback.');
        }

        $suffix = $type === CreditTransactionType::Refund ? 'refund' : 'chargeback';
        $idempotencyKey = 'purchase:order:'.$order->id.':'.$suffix;

        if (CreditTransaction::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            return CreditTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
        }

        return DB::transaction(function () use ($order, $type, $description, $idempotencyKey): CreditTransaction {
            $wallet = CreditWallet::query()
                ->where('user_id', $order->user_id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($order->user);
                $wallet = CreditWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $wallet->available_balance -= $order->credits_to_issue;
            $wallet->save();

            return CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $order->user_id,
                'type' => $type,
                'amount' => -$order->credits_to_issue,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'metadata' => [
                    'payment_order_id' => $order->id,
                    'credit_package_id' => $order->credit_package_id,
                ],
            ]);
        });
    }

    public function releaseReservation(CreditReservation $reservation, string $description = 'Released · no charge'): void
    {
        DB::transaction(function () use ($reservation, $description): void {
            $reservation = CreditReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->status !== CreditReservationStatus::Pending) {
                return;
            }

            $wallet = CreditWallet::query()
                ->whereKey($reservation->credit_wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->available_balance += 1;
            $wallet->reserved_balance = max(0, $wallet->reserved_balance - 1);
            $wallet->save();

            $reservation->forceFill([
                'status' => CreditReservationStatus::Released,
                'released_at' => now(),
            ])->save();

            CreditTransaction::query()->create([
                'credit_wallet_id' => $wallet->id,
                'user_id' => $reservation->user_id,
                'type' => CreditTransactionType::Release,
                'amount' => 0,
                'available_balance_after' => $wallet->available_balance,
                'reserved_balance_after' => $wallet->reserved_balance,
                'idempotency_key' => $reservation->idempotency_key.':release',
                'description' => $description,
                'metadata' => ['generation_request_id' => $reservation->generation_request_id],
            ]);
        });
    }
}
