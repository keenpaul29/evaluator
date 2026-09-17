@extends('layouts.app')

@section('title', 'Comparisons')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Comparisons</h1>
            <p class="text-sm text-gray-500 mt-0.5">Side-by-side candidate evaluation</p>
        </div>
        <a href="{{ route('candidates.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Comparison
        </a>
    </div>

    @if($comparisons->isEmpty())
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-16 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <p class="text-gray-500 text-sm">No comparisons yet.</p>
            <p class="text-xs text-gray-400 mt-1">Create one from the candidates list.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($comparisons as $comparison)
                <a href="{{ route('comparisons.show', $comparison) }}" class="bg-white rounded-card border border-gray-100 shadow-card p-5 hover:shadow-elevated hover:border-gray-200 transition-all duration-150 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 group-hover:text-accent transition-colors">{{ $comparison->name }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $comparison->candidates->count() }} candidates</p>
                        </div>
                        <form method="POST" action="{{ route('comparisons.destroy', $comparison) }}" onclick="event.stopPropagation();">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-button transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach($comparison->candidates as $c)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-badge text-xs bg-gray-50 text-gray-600 ring-1 ring-gray-200">
                                {{ $c->name }}
                                @if($c->evaluation)
                                    <span class="font-mono font-semibold text-gray-900">{{ $c->evaluation->overall_score }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
