@extends('layouts.app')

@section('title', 'Batch Evaluations')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Batch Evaluations</h1>
            <p class="text-sm text-gray-500 mt-0.5">Upload CSV to evaluate multiple candidates</p>
        </div>
        <a href="{{ route('batches.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Batch
        </a>
    </div>

    @if($batches->isEmpty())
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-16 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <p class="text-gray-500 text-sm">No batch evaluations yet.</p>
            <a href="{{ route('batches.create') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm text-accent hover:text-blue-700 font-medium">
                Upload your first batch
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @else
        <div class="bg-white rounded-card border border-gray-100 shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Name</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Status</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Progress</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($batches as $batch)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('batches.show', $batch) }}" class="font-medium text-gray-900 hover:text-accent transition-colors">{{ $batch->name }}</a>
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                                            'processing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                            'complete' => 'bg-green-50 text-green-700 ring-green-600/20',
                                            'partial_failure' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-badge text-xs font-medium ring-1 ring-inset {{ $statusColors[$batch->status] ?? '' }}">
                                        <span class="w-1 h-1 rounded-full bg-current"></span>
                                        {{ ucfirst(str_replace('_', ' ', $batch->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[100px]">
                                            @php
                                                $progress = $batch->total_candidates > 0 ? ($batch->processed_count / $batch->total_candidates) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600 font-mono">{{ $batch->processed_count }}/{{ $batch->total_candidates }}</span>
                                        @if($batch->failed_count > 0)
                                            <span class="text-xs text-red-500 font-mono">({{ $batch->failed_count }} failed)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-sm text-gray-400">{{ $batch->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
