<?php

namespace App\Console\Commands;

use App\Services\WalletService;
use Illuminate\Console\Command;

class ReconcileWalletsCommand extends Command
{
    protected $signature = 'nova:reconcile-wallets';

    protected $description = 'Report wallets whose balance does not match the credit ledger';

    public function handle(WalletService $wallets): int
    {
        $mismatchIds = $wallets->walletIdsWithLedgerMismatch();

        if ($mismatchIds === []) {
            $this->info('All wallets match their ledger.');

            return self::SUCCESS;
        }

        $this->warn('Found '.count($mismatchIds).' wallet(s) with ledger mismatches:');
        $this->line(implode(', ', $mismatchIds));

        return self::FAILURE;
    }
}
