<?php

namespace App\Console\Commands;

use App\Models\GenerationJob;
use Illuminate\Console\Command;

class CleanupStaleGenerationJobs extends Command
{
    protected $signature = 'app:cleanup-stale-jobs';
    protected $description = 'Delete old failed/abandoned generation_jobs records';

    public function handle(): void
    {
        $deleted = GenerationJob::where('created_at', '<', now()->subDay())->delete();
        $this->info("Cleaned up {$deleted} stale generation_jobs records.");
    }
}