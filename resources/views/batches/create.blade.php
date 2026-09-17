@extends('layouts.app')

@section('title', 'New Batch Evaluation')

@section('content')
<div class="max-w-xl space-y-4">
    <div>
        <a href="{{ route('batches.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <h1 class="text-xl font-semibold text-gray-900 mt-1">New Batch Evaluation</h1>
    </div>

    <form method="POST" action="{{ route('batches.store') }}" enctype="multipart/form-data" class="bg-white rounded-card border border-gray-100 shadow-card p-5 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Batch Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full px-3 py-2.5 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
                placeholder="e.g., September 2026 Round 1">
            @error('name') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">CSV File</label>
            <input type="file" name="csv_file" accept=".csv,.txt" required
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-button file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:transition-colors">
            @error('csv_file') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-1.5">Max 50 candidates. Columns: name, email, github_username, repos</p>
        </div>

        <div class="bg-gray-50 rounded-card p-4 ring-1 ring-gray-200">
            <p class="text-xs font-semibold text-gray-700 mb-2">Expected CSV Format:</p>
            <code class="text-xs text-gray-600 block whitespace-pre font-mono bg-white p-3 rounded-button ring-1 ring-gray-100">name,email,github_username,repos
John Doe,john@example.com,john-dev,"https://github.com/john-dev/repo1,https://github.com/john-dev/repo2"</code>
        </div>

        <button type="submit" class="w-full px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
            Start Batch Evaluation
        </button>
    </form>
</div>
@endsection
