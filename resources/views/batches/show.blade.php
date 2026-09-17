@extends('layouts.app')

@section('title', $batch->name . ' - Batch')

@section('content')
<div class="space-y-4">
    <div>
        <a href="{{ route('batches.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <h1 class="text-xl font-semibold text-gray-900 mt-1">{{ $batch->name }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            @php
                $statusColors = [
                    'pending' => 'text-gray-600',
                    'processing' => 'text-blue-600',
                    'complete' => 'text-green-600',
                    'partial_failure' => 'text-amber-600',
                ];
            @endphp
            <span class="{{ $statusColors[$batch->status] ?? '' }}">{{ ucfirst(str_replace('_', ' ', $batch->status)) }}</span>
            · {{ $batch->processed_count }}/{{ $batch->total_candidates }} processed
            @if($batch->failed_count > 0)
                · <span class="text-red-500">{{ $batch->failed_count }} failed</span>
            @endif
        </p>
    </div>

    @if($batch->status === 'processing')
        <div class="bg-blue-50 rounded-card border border-blue-100 p-5" x-data="pollBatch()" x-init="startPolling()">
            <div class="flex items-center gap-3">
                <div class="animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
                <p class="text-blue-700 text-sm font-medium">Processing...</p>
            </div>
            @php
                $progressPercent = $batch->total_candidates > 0
                    ? round(($batch->processed_count / $batch->total_candidates) * 100)
                    : 0;
            @endphp
            <div class="mt-3 h-2 bg-blue-100 rounded-full overflow-hidden">
                <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
            </div>
            <p class="text-xs text-blue-500 mt-2">Page refreshes automatically.</p>
        </div>
        @push('scripts')
        <script>
        function pollBatch() {
            return {
                polling: false,
                startPolling() {
                    this.polling = true;
                    this.poll();
                },
                async poll() {
                    if (!this.polling) return;
                    try {
                        const res = await fetch('{{ route("batches.show", $batch) }}');
                        const html = await res.text();
                        if (!html.includes('Processing...')) {
                            window.location.reload();
                        } else {
                            setTimeout(() => this.poll(), 3000);
                        }
                    } catch (e) {
                        setTimeout(() => this.poll(), 5000);
                    }
                }
            };
        }
        </script>
        @endpush
    @endif

    <div class="bg-white rounded-card border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Candidates ({{ $batch->candidates->count() }})</h2>
        </div>
        @if($batch->candidates->isEmpty())
            <div class="p-12 text-center">
                <p class="text-gray-400 text-sm">No candidates in this batch.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Name</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Status</th>
                            <th class="text-right px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Score</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Verdict</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($batch->candidates as $candidate)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600">
                                            {{ strtoupper(substr($candidate->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:text-accent transition-colors">{{ $candidate->name }}</a>
                                            <div class="text-xs text-gray-400">{{ $candidate->github_username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $sColors = [
                                            'submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                            'analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                            'evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-badge text-xs font-medium ring-1 ring-inset {{ $sColors[$candidate->status->value] ?? 'bg-gray-50 text-gray-600 ring-gray-500/20' }}">
                                        <span class="w-1 h-1 rounded-full bg-current"></span>
                                        {{ $candidate->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-sm text-gray-700">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-sm text-gray-600">{{ $candidate->evaluation?->verdict ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
