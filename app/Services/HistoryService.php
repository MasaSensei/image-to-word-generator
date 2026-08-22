<?php

namespace App\Services;

use App\Models\History;
use Illuminate\Support\Facades\Storage;

class HistoryService
{
    public function record(string $ownerToken, string $filePath, int $imageCount): History
    {
        return History::create([
            'owner_token' => $ownerToken,
            'file_name'   => basename($filePath),
            'file_path'   => $filePath,
            'image_count' => $imageCount,
        ]);
    }

    public function getHistoryForOwner(string $ownerToken)
    {
        return History::where('owner_token', $ownerToken)
            ->latest()
            ->paginate(10);
    }

    public function findOwnedHistory(int $id, string $ownerToken): ?History
    {
        return History::where('id', $id)
            ->where('owner_token', $ownerToken)
            ->first();
    }
}
