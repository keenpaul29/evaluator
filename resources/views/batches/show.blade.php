@extends('layouts.app')

@section('title', $batch->name . ' - Batch')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <a href="{{ route('batches.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors focus-visible-ring">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 w-full">
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-semibold text-gray-900">{{ $batch->name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    @php
                        $statusColors = [
                            'pending' => 'text-gray-600',
                            'processing' => 'text-blue-600',
                            'complete' => 'text-green-600',
                            'partial_failure' => 'text-amber-600',
                        ];
                        $statusBgColors = [
                            'pending' => 'bg-gray-50 text-gray-600 ring-gray-500/20',
                            'processing' => 'bg-blue-50 text-blue-600 ring-blue-600/20',
                            'complete' => 'bg-green-50 text-green-600 ring-green-600/20',
                            'partial_failure' => 'bg-amber-50 text-amber-600 ring-amber-600/20',
                        ];
                    @endphp
                    <x-badge label="{{ ucfirst(str_replace('_', ' ', $batch->status)) }}" variant="status-{{ $batch->status }}" size="sm" />
                    · {{ $batch->processed_count }}/{{ $batch->total_candidates }} processed
                    @if($batch->failed_count > 0)
                        · <x-badge label="{{ $batch->failed_count }} failed" variant="danger" size="sm" dot="false" />
                    @endif
                </p>
            </div>
            <a href="{{ route('batches.export', $batch) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm focus-visible-ring">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    @if($batch->status === 'processing')
        <x-card variant="default" padding="md" class="bg-blue-50 border-blue-100" x-data="pollBatch()" x-init="startPolling()">
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
        </x-card>
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

    @php
        $candidateRows = $batch->candidates->map(function($candidate) {
            $sColors = [
                'submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                'analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                'evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
            ];
            $verdictLabels = [
                'strong_hire' => 'Strong Hire',
                'hire' => 'Hire',
                'maybe' => 'Maybe',
                'no_hire' => 'No Hire',
                'strong_no_hire' => 'Strong No Hire',
            ];
            $verdictColors = [
                'strong_hire' => 'text-green-600',
                'hire' => 'text-emerald-600',
                'maybe' => 'text-amber-600',
                'no_hire' => 'text-orange-600',
                'strong_no_hire' => 'text-red-600',
            ];
            $nameHtml = '<a href="' . route('candidates.show', $candidate) . '" class="font-medium text-gray-900 hover:text-accent transition-colors">' . $candidate->name . '</a>' .
                '<div class="text-xs text-gray-400">' . $candidate->github_username . '</div>';
            $sColors = [
                'submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                'analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                'evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
            ];
            $statusHtml = '<span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-badge text-xs font-medium ring-1 ring-inset ' . ($sColors[$candidate->status->value] ?? 'bg-gray-50 text-gray-600 ring-gray-500/20') . '"><span class="w-1 h-1 rounded-full bg-current"></span>' . $candidate->status->label() . '</span>';
            $scoreHtml = '<span class="font-mono text-sm text-gray-700">' . ($candidate->evaluation?->overall_score ?? '—') . '</span>';
            $verdictLabels = [
                'strong_hire' => 'Strong Hire',
                'hire' => 'Hire',
                'maybe' => 'Maybe',
                'no_hire' => 'No Hire',
                'strong_no_hire' => 'Strong No Hire',
            ];
            $verdictColors = [
                'strong_hire' => 'text-green-600',
                'hire' => 'text-emerald-600',
                'maybe' => 'text-amber-600',
                'no_hire' => 'text-orange-600',
                'strong_no_hire' => 'text-red-600',
            ];
            $verdictHtml = $candidate->evaluation ? '<span class="text-xs font-medium ' . ($verdictColors[$candidate->evaluation->verdict] ?? '') . '">' . ($verdictLabels[$candidate->evaluation->verdict] ?? '') . '</span>' : '<span class="text-gray-300">—</span>';
            return [
                'name' => $nameHtml,
                'status' => $statusHtml,
                'score' => $scoreHtml,
                'verdict' => $verdictHtml,
            ];
        });
    @endphp

    <div class="bg-white rounded-card border border-gray-100 shadow-card overflow-hidden">
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
                        @foreach($candidateRows as $row)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5">{!! $row['name'] !!}</td>
                                <td class="px-5 py-3.5">{!! $row['status'] !!}</td>
                                <td class="px-5 py-3.5 text-right">{!! $row['score'] !!}</td>
                                <td class="px-5 py-3.5">{!! $row['verdict'] !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-card variant="default" x-data="{ query: '', selected: [] }">
        <x-slot:header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xs font-semibold text-gray-900 uppercase tracking-wide">Add Existing Candidates</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Attach candidates not yet in this batch. Unevaluated ones are queued automatically.</p>
                </div>
                <span class="text-xs text-gray-400">showing up to 100</span>
            </div>
        </x-slot:header>

        <div class="px-5 py-3 border-b border-gray-100">
            <x-form-input type="text" name="query" label="Search" placeholder="Search by name, email, or GitHub..." :value="null" :error="null" />
        </div>

        <form method="POST" action="{{ route('batches.add-candidates', $batch) }}" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
            @csrf
            @foreach($availableCandidates as $candidate)
                <label class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/50 transition-colors cursor-pointer"
                    x-show="!query || '{{ strtolower($candidate->name) }} {{ strtolower($candidate->email ?? '') }} {{ strtolower($candidate->github_username ?? '') }}'.includes(query.toLowerCase())">
                    <input type="checkbox" name="candidate_ids[]" value="{{ $candidate->id }}" x-model="selected"
                        class="rounded border-gray-300 text-accent focus:ring-accent">
                    <span class="text-sm font-medium text-gray-900">{{ $candidate->name }}</span>
                    <span class="text-xs text-gray-400">{{ $candidate->github_username }} · {{ $candidate->email }}</span>
                    @if($candidate->evaluation)
                        <span class="ml-auto text-xs font-mono text-emerald-600">{{ $candidate->evaluation->overall_score }} · {{ $candidate->evaluation->verdict }}</span>
                    @else
                        <span class="ml-auto text-xs text-gray-400">not evaluated</span>
                    @endif
                </label>
            @endforeach
            @if($availableCandidates->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-gray-400 text-sm">No candidates available to add.</p>
                </div>
            @endif
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div class="text-sm text-gray-400">
                    You selected <span class="font-mono font-semibold text-gray-700" x-text="selected.length">0</span> candidate(s)
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm focus-visible-ring">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add to Batch
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection