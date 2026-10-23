@extends('layouts.app')

@section('title', 'New Batch Evaluation')

@section('content')
<div class="max-w-xl space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <a href="{{ route('batches.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors focus-visible-ring">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <h1 class="text-xl font-semibold text-gray-900">New Batch Evaluation</h1>
    </div>

    <form method="POST" action="{{ route('batches.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <x-card variant="default" class="space-y-5">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Batch Details</h2>
                </div>
            </x-slot:header>

            <x-form-input type="text" name="name" label="Batch Name" placeholder="e.g., September 2026 Round 1" required :value="old('name')" :error="$errors->first('name')" />
            
            <x-form-input type="file" name="csv_file" label="CSV File" accept=".csv,.txt" required :error="$errors->first('csv_file')" />
            <p class="text-xs text-gray-400 mt-1.5">Max 50 candidates. Columns: name, email, github_username, repos</p>

            <div class="bg-gray-50 rounded-card p-4 ring-1 ring-gray-200">
                <p class="text-xs font-semibold text-gray-700 mb-2">Expected CSV Format:</p>
                <code class="text-xs text-gray-600 block whitespace-pre font-mono bg-white p-3 rounded-button ring-1 ring-gray-100">name,email,github_username,repos
John Doe,john@example.com,john-dev,"https://github.com/john-dev/repo1,https://github.com/john-dev/repo2"</code>
            </div>
        </x-card>

        <button type="submit" class="w-full px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm focus-visible-ring">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Start Batch Evaluation
        </button>
    </form>
</div>
@endsection
