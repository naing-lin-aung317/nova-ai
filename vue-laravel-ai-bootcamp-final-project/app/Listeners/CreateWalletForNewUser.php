<?php

namespace App\Listeners;

use App\Services\WalletService;
use Illuminate\Auth\Events\Registered;

class CreateWalletForNewUser
{
    public function __construct(public WalletService $wallets) {}

    public function handle(Registered $event): void
    {
        $this->wallets->ensureWallet($event->user);
    }
}
