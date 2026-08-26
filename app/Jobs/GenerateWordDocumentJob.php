<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Services\WordGeneratorService;
use App\Services\HistoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateWordDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;
    public $tries = 1;

    public function __construct(
        private string $jobId,
        private array $storedPaths,
        private array $descriptions,
        private string $ownerToken
    ) {}

    public function handle(
        WordGeneratorService $wordService,
        HistoryService $historyService
    ): void {
        $job = GenerationJob::find($this->jobId);

        if (!$job) {
            return;
        }

        $job->update(['status' => 'processing']);

        try {
            $generatedFilePath = $wordService->generateFromPaths(
                $this->storedPaths,
                $this->descriptions
            );

            $job->update([
                'status' => 'completed',
                'file_path' => $generatedFilePath,
            ]);

            $historyService->record(
                $this->ownerToken,
                $generatedFilePath,
                count($this->storedPaths)
            );
        } catch (\Throwable $e) {
            Log::error(
                "GenerateWordDocumentJob failed [{$this->jobId}]: "
                . $e->getMessage()
            );

            $job->update([
                'status' => 'failed',
                'error_message' => 'Failed to process the document. Please try again.',
            ]);
        } finally {
            foreach ($this->storedPaths as $path) {
                Storage::delete($path);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $job = GenerationJob::find($this->jobId);

        $job?->update([
            'status' => 'failed',
            'error_message' => 'The process exceeded the time limit or failed completely.',
        ]);

        foreach ($this->storedPaths as $path) {
            Storage::delete($path);
        }
    }
}