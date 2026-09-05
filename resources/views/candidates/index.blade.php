@extends('layouts.app')

@section('title', 'Candidates')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Candidates</h1>
            <p class="text-xs text-gray-400 mt-0.5">{{ $candidates->total() }} total</p>
        </div>
        <a href="{{ route('candidates.create') }}" class="px-3 py-1.5 bg-gray-900 text-white text-xs font-medium rounded hover:bg-gray-800 transition-colors">
            + Add
        </a>
    </div>

    <form method="GET" action="{{ route('candidates.index') }}" class="bg-white rounded-lg border border-gray-100 p-3">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                class="px-2.5 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-gray-900 focus:border-gray-900 w-48">
            <select name="status" class="px-2.5 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                <option value="">All Status</option>
                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="analyzing" {{ request('status') === 'analyzing' ? 'selected' : '' }}>Analyzing</option>
                <option value="evaluated" {{ request('status') === 'evaluated' ? 'selected' : '' }}>Evaluated</option>
                <option value="shortlisted" {{ request('status') === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <select name="verdict" class="px-2.5 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                <option value="all">All Verdicts</option>
                <option value="strong_hire" {{ request('verdict') === 'strong_hire' ? 'selected' : '' }}>Strong Hire</option>
                <option value="hire" {{ request('verdict') === 'hire' ? 'selected' : '' }}>Hire</option>
                <option value="maybe" {{ request('verdict') === 'maybe' ? 'selected' : '' }}>Maybe</option>
                <option value="no_hire" {{ request('verdict') === 'no_hire' ? 'selected' : '' }}>No Hire</option>
                <option value="strong_no_hire" {{ request('verdict') === 'strong_no_hire' ? 'selected' : '' }}>Strong No Hire</option>
            </select>
            <div class="flex items-center gap-1">
                <input type="number" name="min_score" value="{{ request('min_score') }}" placeholder="Min" step="0.5" min="0" max="10"
                    class="w-16 px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                <span class="text-gray-300 text-xs">–</span>
                <input type="number" name="max_score" value="{{ request('max_score') }}" placeholder="Max" step="0.5" min="0" max="10"
                    class="w-16 px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                Filter
            </button>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
        @if($candidates->isEmpty())
            <div class="p-12 text-center">
                <p class="text-gray-400 text-sm">No candidates found.</p>
                <a href="{{ route('candidates.create') }}" class="text-xs text-gray-900 underline mt-1 inline-block">Add the first one</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50">
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Name</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">GitHub</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Status</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Score</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Verdict</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Repos</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($candidates as $candidate)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:text-gray-600">{{ $candidate->name }}</a>
                                    @if($candidate->email)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $candidate->email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($candidate->github_username)
                                        <a href="https://github.com/{{ $candidate->github_username }}" target="_blank" class="text-gray-500 hover:text-gray-700 text-xs">
                                            {{ $candidate->github_username }}
                                        </a>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @php
                                        $statusColors = [
                                            'submitted' => 'bg-amber-50 text-amber-600',
                                            'analyzing' => 'bg-blue-50 text-blue-600',
                                            'evaluated' => 'bg-purple-50 text-purple-600',
                                            'shortlisted' => 'bg-green-50 text-green-600',
                                            'rejected' => 'bg-red-50 text-red-600',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColors[$candidate->status] ?? 'bg-gray-50 text-gray-600' }}">
                                        {{ ucfirst($candidate->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                <td class="px-4 py-2.5">
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
                                <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $candidate->repositories->count() }}</td>
                                <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $candidate->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 border-t border-gray-50">
                {{ $candidates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
