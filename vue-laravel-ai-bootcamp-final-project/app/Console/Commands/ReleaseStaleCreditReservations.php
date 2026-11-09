<?php

namespace App\Console\Commands;

use App\Services\GenerationService;
use Illuminate\Console\Command;

class ReleaseStaleCreditReservations extends Command
{
    protected $signature = 'nova:release-stale-reservations';

    protected $description = 'Release credit reservations that expired without capture';

    public function handle(GenerationService $generations): int
    {
        $count = $generations->releaseStale();

        $this->info("Released {$count} stale generation reservation(s).");

        return self::SUCCESS;
    }
}
