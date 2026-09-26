@extends('layouts.app')

@section('title', 'Take-Home Assignment')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <div>
        <a href="https://coloredcow.com" target="_blank" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            coloredcow.com
        </a>
        <h1 class="text-xl font-semibold text-gray-900 mt-1">{{ $assignment->brief['title'] ?? 'Take-Home Assignment' }}</h1>
        <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
            <span>{{ $assignment->candidate->name }}</span>
            @if($assignment->due_at)
                <span class="text-gray-400">·</span>
                <span>Due {{ $assignment->due_at->format('M d, Y') }}</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-button px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-button px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-card border border-gray-100 shadow-card p-5 space-y-4">
        <div>
            <div class="text-xs text-gray-400 font-medium mb-2">Objective</div>
            <p class="text-sm text-gray-700 leading-relaxed">{{ $assignment->brief['objective'] }}</p>
        </div>

        @if($assignment->brief['context'] ?? null)
            <div>
                <div class="text-xs text-gray-400 font-medium mb-2">Context</div>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $assignment->brief['context'] }}</p>
            </div>
        @endif

        @if(is_array($assignment->brief['deliverables'] ?? null))
            <div>
                <div class="text-xs text-gray-400 font-medium mb-2">Deliverables</div>
                <ul class="list-disc pl-5 space-y-1.5 text-sm text-gray-700">
                    @foreach($assignment->brief['deliverables'] as $deliverable)
                        <li>{{ $deliverable }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(is_array($assignment->brief['review_criteria'] ?? null))
            <div>
                <div class="text-xs text-gray-400 font-medium mb-2">What we will look at</div>
                <ul class="list-disc pl-5 space-y-1.5 text-sm text-gray-700">
                    @foreach($assignment->brief['review_criteria'] as $criterion)
                        <li>{{ $criterion }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($assignment->brief['timebox'] ?? null)
            <div>
                <div class="text-xs text-gray-400 font-medium mb-2">Timebox</div>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $assignment->brief['timebox'] }}</p>
            </div>
        @endif

        @if($assignment->brief['ai_use_note'] ?? null)
            <div class="bg-gray-50 rounded-button p-3">
                <div class="text-xs text-gray-400 font-medium mb-2">Using AI tools</div>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $assignment->brief['ai_use_note'] }}</p>
            </div>
        @endif
    </div>

    @if(in_array($assignment->status->value, ['dispatched', 'submitted'], true))
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
            <div class="text-xs text-gray-400 font-medium mb-3">Submit your work</div>
            @if($assignment->status->value === 'submitted')
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-button px-4 py-3 mb-4">
                    Submitted on {{ $assignment->submitted_at?->format('M d, Y') }}. Thank you — we will review it.
                </div>
            @endif
            <form method="POST" action="{{ route('assignments.submit', $assignment->token) }}" class="space-y-3">
                @csrf
                <div>
                    <label for="submitted_repo_url" class="block text-xs text-gray-500 font-medium mb-1">Repository URL</label>
                    <input type="url" name="submitted_repo_url" id="submitted_repo_url" value="{{ $assignment->submitted_repo_url ?? '' }}" required placeholder="https://github.com/you/assignment"
                        class="w-full px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                    @error('submitted_repo_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reflection" class="block text-xs text-gray-500 font-medium mb-1">Reflection</label>
                    <textarea name="reflection" id="reflection" rows="4" required placeholder="Your approach, the decisions you made, what stood in the way, and what you learned."
                        class="w-full px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">{{ $assignment->reflection ?? '' }}</textarea>
                    @error('reflection') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
                    Submit assignment
                </button>
            </form>
        </div>
    @elseif($assignment->status->value !== 'complete')
        <div class="bg-gray-50 border border-gray-100 text-gray-400 text-sm rounded-button px-4 py-3">
            This assignment link is no longer open for submission.
        </div>
    @endif
</div>
@endsection