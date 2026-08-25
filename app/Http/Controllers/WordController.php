<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageToWordRequest;
use App\Services\WordGeneratorService;
use App\Services\HistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WordController extends Controller
{
    public function index(Request $request, HistoryService $historyService)
    {
        $ownerToken = $request->cookie('owner_token');
        $histories = $ownerToken ? $historyService->getHistoryForOwner($ownerToken) : [];

        return view('pages.home', compact('histories'));
    }

    public function generate(ImageToWordRequest $request, WordGeneratorService $wordService, HistoryService $historyService)
    {
        try {
            $files = $request->file('images');
            $descriptions = $request->input('descriptions', []);
            $generatedFilePath = $wordService->generate($files, $descriptions);

            // Ambil atau buat owner_token dari cookie
            $ownerToken = $request->cookie('owner_token');
            if (!$ownerToken) {
                $ownerToken = (string) \Illuminate\Support\Str::uuid();
                cookie()->queue('owner_token', $ownerToken, 525600);
            }

            // Catat ke database history
            $historyService->record($ownerToken, $generatedFilePath, count($files));

            return response()->download(
                Storage::path($generatedFilePath),
                'Corporate_Report_' . now()->format('Ymd_His') . '.docx'
            );
        } catch (\Exception $e) {
            Log::error('Word Generation Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan sistem saat memproses dokumen.'], 500);
        }
    }

    public function downloadHistory(Request $request, int $id, HistoryService $historyService)
    {
        $ownerToken = $request->cookie('owner_token');
        $history = $historyService->findOwnedHistory($id, $ownerToken);

        if (!$history || !Storage::exists($history->file_path)) {
            abort(403, 'Akses ditolak atau dokumen tidak ditemukan.');
        }

        return response()->download(Storage::path($history->file_path));
    }
}
