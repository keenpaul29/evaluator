@extends('layouts.app')

@section('title', 'Batch Evaluations')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Batch Evaluations</h1>
            <p class="text-sm text-gray-500 mt-0.5">Upload CSV to evaluate multiple candidates</p>
        </div>
        <a href="{{ route('batches.create') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm focus-visible-ring">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Batch
        </a>
    </div>

    @if($batches->isEmpty())
        <x-card variant="default" padding="xl" class="text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <p class="text-gray-500 text-sm">No batch evaluations yet.</p>
            <a href="{{ route('batches.create') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm text-accent hover:text-blue-700 font-medium">
                Upload your first batch
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </x-card>
    @else
        <x-card variant="default">
            <x-table :headers="[
                ['label' => 'Name', 'key' => 'name'],
                ['label' => 'Status', 'key' => 'status'],
                ['label' => 'Progress', 'key' => 'progress'],
                ['label' => 'Created', 'key' => 'created_at'],
            ]" :rows="{{ $batches->map(function($batch) {
                @php
                    $statusColors = [
                        'pending' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                        'processing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                        'complete' => 'bg-green-50 text-green-700 ring-green-600/20',
                        'partial_failure' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                    ];
                @endphp
                return [
                    'name' => '<a href=\"' . route('batches.show', $batch) . '\" class=\"font-medium text-gray-900 hover:text-accent transition-colors\">' . $batch->name . '</a>' .
                        '<a href=\"' . route('batches.export', $batch) . '\" title=\"Export results CSV\" class=\"text-gray-300 hover:text-accent transition-colors ml-2\" aria-label=\"Export results CSV\">
                            <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3\"/></svg>
                        </a>',
                    'status' => '<x-badge label=\"' . ucfirst(str_replace('_', ' ', $batch->status)) . '\" variant=\"status-' . $batch->status . '\" size=\"sm\" />',
                    'progress' => '<div class=\"flex items-center gap-3\">
                        <div class=\"flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[100px]\">
                            @php
                                $progress = $batch->total_candidates > 0 ? ($batch->processed_count / $batch->total_candidates) * 100 : 0;
                            @endphp
                            <div class=\"h-full bg-green-500 rounded-full transition-all duration-500\" style=\"width: {{ $progress }}%\"></div>
                        </div>
                        <span class=\"text-xs text-gray-600 font-mono\">' . $batch->processed_count . '/' . $batch->total_candidates . '</span>'
                        . ($batch->failed_count > 0 ? '<span class=\"text-xs text-red-500 font-mono\">(' . $batch->failed_count . ' failed)</span>' : ''),
                    'created_at' => $batch->created_at->diffForHumans(),
                ];
            }) }}" emptyMessage="No batches found." />
        </x-card>
    @endif
</div>
@endsection
