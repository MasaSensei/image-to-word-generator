<?php

namespace App\Services;

use App\Models\History;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class HistoryService
{
    private const DOWNLOADABLE_LIMIT = 3;
    private const PAGINATION_LIMIT = 10;

    public function record(
        string $ownerToken,
        string $filePath,
        int $imageCount
    ): History {
        $history = History::create([
            'owner_token' => $ownerToken,
            'file_name'   => basename($filePath),
            'file_path'   => $filePath,
            'image_count' => $imageCount,
        ]);

        $this->pruneExpiredFiles($ownerToken);

        return $history;
    }

    public function getHistoryForOwner(string $ownerToken)
    {
        $downloadableIds = $this->downloadableIdsFor($ownerToken);

        $histories = History::where('owner_token', $ownerToken)
            ->latest()
            ->paginate(self::PAGINATION_LIMIT);

        $histories->getCollection()->transform(
            function (History $history) use ($downloadableIds) {
                $history->is_downloadable =
                    $downloadableIds->contains($history->id);

                return $history;
            }
        );

        return $histories;
    }

    public function findOwnedHistory(
        int $id,
        string $ownerToken
    ): ?History {
        return History::where('id', $id)
            ->where('owner_token', $ownerToken)
            ->first();
    }

    public function findDownloadableHistory(
        int $id,
        string $ownerToken
    ): ?History {
        $history = $this->findOwnedHistory($id, $ownerToken);

        if (!$history) {
            return null;
        }

        if (!$this->downloadableIdsFor($ownerToken)->contains($history->id)) {
            return null;
        }

        return $history;
    }

    private function downloadableIdsFor(string $ownerToken): Collection
    {
        return History::where('owner_token', $ownerToken)
            ->latest()
            ->limit(self::DOWNLOADABLE_LIMIT)
            ->pluck('id');
    }

    private function pruneExpiredFiles(string $ownerToken): void
    {
        $expired = History::where('owner_token', $ownerToken)
            ->latest()
            ->skip(self::DOWNLOADABLE_LIMIT)
            ->get();

        foreach ($expired as $history) {
            if (
                $history->file_path &&
                Storage::exists($history->file_path)
            ) {
                Storage::delete($history->file_path);
            }
        }
    }
}