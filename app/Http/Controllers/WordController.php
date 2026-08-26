<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageToWordRequest;
use App\Jobs\GenerateWordDocumentJob;
use App\Models\GenerationJob;
use App\Services\HistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordController extends Controller
{
    public function index(Request $request, HistoryService $historyService)
    {
        $ownerToken = $request->cookie('owner_token');

        $histories = $ownerToken
            ? $historyService->getHistoryForOwner($ownerToken)
            : [];

        return view('pages.home', compact('histories'));
    }

    public function generate(ImageToWordRequest $request)
    {
        $files = $request->file('images');
        $descriptions = $request->input('descriptions', []);
        $storedPaths = [];

        foreach ($files as $file) {
            $storedPaths[] = $file->store('pending_uploads');
        }

        $ownerToken = $request->cookie('owner_token');

        if (!$ownerToken) {
            $ownerToken = (string) Str::uuid();

            cookie()->queue(
                'owner_token',
                $ownerToken,
                525600
            );
        }

        $job = GenerationJob::create([
            'id' => (string) Str::uuid(),
            'owner_token' => $ownerToken,
            'status' => 'pending',
            'image_count' => count($storedPaths),
        ]);

        GenerateWordDocumentJob::dispatch(
            $job->id,
            $storedPaths,
            $descriptions,
            $ownerToken
        );

        return response()->json([
            'job_id' => $job->id,
        ]);
    }

    public function status(Request $request, string $jobId)
    {
        $ownerToken = $request->cookie('owner_token');

        $job = GenerationJob::where('id', $jobId)
            ->where('owner_token', $ownerToken)
            ->first();

        if (!$job) {
            abort(404);
        }

        return response()->json([
            'status' => $job->status,
            'download_url' => $job->status === 'completed'
                ? route('word.job.download', $job->id)
                : null,
            'error_message' => $job->error_message,
        ]);
    }

    public function jobDownload(Request $request, string $jobId)
    {
        $ownerToken = $request->cookie('owner_token');

        $job = GenerationJob::where('id', $jobId)
            ->where('owner_token', $ownerToken)
            ->first();

        if (
            !$job ||
            $job->status !== 'completed' ||
            !$job->file_path ||
            !Storage::exists($job->file_path)
        ) {
            abort(
                404,
                'The document was not found or has not finished processing.'
            );
        }

        return response()->download(
            Storage::path($job->file_path),
            'Corporate_Report_' . now()->format('Ymd_His') . '.docx'
        );
    }

    public function downloadHistory(
        Request $request,
        int $id,
        HistoryService $historyService
    ) {
        $ownerToken = $request->cookie('owner_token');

        $history = $historyService->findOwnedHistory(
            $id,
            $ownerToken
        );

        if (!$history || !Storage::exists($history->file_path)) {
            abort(403, 'Access denied or document not found.');
        }

        return response()->download(
            Storage::path($history->file_path)
        );
    }
}