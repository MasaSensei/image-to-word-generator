<?php

namespace App\Console\Commands;

use App\Models\GenerationJob;
use Illuminate\Console\Command;

class PruneGenerationJobs extends Command
{
    protected $signature = 'generation-jobs:prune {--minutes=60}';

    protected $description = 'Delete completed/failed GenerationJob rows older than the given number of minutes.';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $deleted = GenerationJob::whereIn('status', ['completed', 'failed'])
            ->where('updated_at', '<=', now()->subMinutes($minutes))
            ->delete();

        $this->info("Pruned {$deleted} generation job(s).");

        return self::SUCCESS;
    }
}