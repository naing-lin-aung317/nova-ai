<?php

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $wallets = app(WalletService::class);

        User::query()->orderBy('id')->each(function (User $user) use ($wallets): void {
            $wallets->ensureWallet($user);

            if ($user->email_verified_at !== null) {
                $wallets->grantSignupBonus($user);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
