@if (isset($histories) && count($histories) > 0)
    <table class="w-full text-left border-collapse text-sm">
        <thead>
            <tr class="border-b border-ink/20 text-ink-muted text-xs uppercase tracking-wider">
                <th class="py-2.5 font-medium">File Name</th>
                <th class="py-2.5 font-medium">Image Count</th>
                <th class="py-2.5 font-medium">Created At</th>
                <th class="py-2.5 font-medium text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-paper-line text-ink-soft">
            @foreach ($histories as $history)
                <tr class="hover:bg-paper transition">
                    <td class="py-3 font-medium text-ink">{{ $history->file_name }}</td>
                    <td class="py-3">{{ $history->image_count }} Images</td>
                    <td class="py-3 text-ink-muted">
                        {{ $history->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</td>
                    <td class="py-3 text-right">
                        @if ($history->is_downloadable)
                            <button
                                @click="downloadHistory('{{ route('history.download', $history->id) }}', {{ $history->id }}, @js($history->file_name))"
                                :disabled="downloadingHistoryId === {{ $history->id }}"
                                class="inline-flex items-center text-xs font-medium text-brand-600 hover:text-brand-700 border border-paper-line hover:border-brand-600 px-3 py-1.5 rounded-sm transition disabled:opacity-50">
                                <span x-show="downloadingHistoryId !== {{ $history->id }}">Re-download</span>
                                <span x-show="downloadingHistoryId === {{ $history->id }}">Downloading...</span>
                            </button>
                        @else
                            <span
                                class="inline-flex items-center text-xs font-medium text-ink-muted border border-paper-line px-3 py-1.5 rounded-sm">
                                Expired
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pt-4">
        {{ $histories->links() }}
    </div>
@else
    <div class="py-10 text-center text-ink-muted text-sm">
        No documents have been generated from this device yet.
    </div>
@endif