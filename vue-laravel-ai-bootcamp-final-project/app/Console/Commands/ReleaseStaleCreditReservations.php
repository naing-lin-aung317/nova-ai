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
        $stuck = $generations->releaseStuckGenerations();

        $this->info("Released {$count} stale generation reservation(s).");
        $this->info("Failed {$stuck} stuck generation(s).");

        return self::SUCCESS;
    }
}
