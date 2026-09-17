@extends('layouts.app')

@section('title', 'Candidates')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Candidates</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $candidates->total() }} total</p>
        </div>
        <a href="{{ route('candidates.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Candidate
        </a>
    </div>

    <form method="GET" action="{{ route('candidates.index') }}" class="bg-white rounded-card border border-gray-100 shadow-card p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search candidates..."
                    class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
            </div>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                <option value="">All Status</option>
                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="analyzing" {{ request('status') === 'analyzing' ? 'selected' : '' }}>Analyzing</option>
                <option value="evaluated" {{ request('status') === 'evaluated' ? 'selected' : '' }}>Evaluated</option>
                <option value="shortlisted" {{ request('status') === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <select name="verdict" class="px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                <option value="all">All Verdicts</option>
                <option value="strong_hire" {{ request('verdict') === 'strong_hire' ? 'selected' : '' }}>Strong Hire</option>
                <option value="hire" {{ request('verdict') === 'hire' ? 'selected' : '' }}>Hire</option>
                <option value="maybe" {{ request('verdict') === 'maybe' ? 'selected' : '' }}>Maybe</option>
                <option value="no_hire" {{ request('verdict') === 'no_hire' ? 'selected' : '' }}>No Hire</option>
                <option value="strong_no_hire" {{ request('verdict') === 'strong_no_hire' ? 'selected' : '' }}>Strong No Hire</option>
            </select>
            <div class="flex items-center gap-1.5">
                <input type="number" name="min_score" value="{{ request('min_score') }}" placeholder="Min" step="0.5" min="0" max="10"
                    class="w-20 px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                <span class="text-gray-300 text-sm">–</span>
                <input type="number" name="max_score" value="{{ request('max_score') }}" placeholder="Max" step="0.5" min="0" max="10"
                    class="w-20 px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-button hover:bg-gray-200 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'verdict', 'min_score', 'max_score']))
                <a href="{{ route('candidates.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear filters</a>
            @endif
        </div>
    </form>

    <div class="bg-white rounded-card border border-gray-100 shadow-card overflow-hidden">
        @if($candidates->isEmpty())
            <div class="p-16 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <p class="text-gray-500 text-sm">No candidates found.</p>
                <a href="{{ route('candidates.create') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm text-accent hover:text-blue-700 font-medium">
                    Add the first candidate
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Name</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">GitHub</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Status</th>
                            <th class="text-right px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Score</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Verdict</th>
                            <th class="text-right px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Repos</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($candidates as $candidate)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600 group-hover:bg-gray-200 transition-colors">
                                            {{ strtoupper(substr($candidate->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:text-accent transition-colors">{{ $candidate->name }}</a>
                                            @if($candidate->email)
                                                <div class="text-xs text-gray-400 mt-0.5">{{ $candidate->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($candidate->github_username)
                                        <a href="https://github.com/{{ $candidate->github_username }}" target="_blank" class="text-gray-500 hover:text-gray-700 text-xs inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                            {{ $candidate->github_username }}
                                        </a>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $statusColors = [
                                            'submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                            'analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                            'evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                            'shortlisted' => 'bg-green-50 text-green-700 ring-green-600/20',
                                            'rejected' => 'bg-red-50 text-red-700 ring-red-600/20',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-badge text-xs font-medium ring-1 ring-inset {{ $statusColors[$candidate->status->value] ?? 'bg-gray-50 text-gray-600 ring-gray-500/20' }}">
                                        <span class="w-1 h-1 rounded-full bg-current"></span>
                                        {{ $candidate->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-sm text-gray-700">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    @if($candidate->evaluation)
                                        @php
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
                                        @endphp
                                        <span class="text-xs font-medium {{ $verdictColors[$candidate->evaluation->verdict] ?? '' }}">
                                            {{ $verdictLabels[$candidate->evaluation->verdict] ?? '' }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right text-gray-400 text-xs">{{ $candidate->repositories->count() }}</td>
                                <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $candidate->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $candidates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
