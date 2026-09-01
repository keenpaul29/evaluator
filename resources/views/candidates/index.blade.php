@extends('layouts.app')

@section('title', 'Candidates')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Candidates</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $candidates->total() }} total candidates</p>
        </div>
        <a href="{{ route('candidates.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            + Add Candidate
        </a>
    </div>

    <form method="GET" action="{{ route('candidates.index') }}" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, GitHub..."
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                <option value="">All Status</option>
                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="analyzing" {{ request('status') === 'analyzing' ? 'selected' : '' }}>Analyzing</option>
                <option value="evaluated" {{ request('status') === 'evaluated' ? 'selected' : '' }}>Evaluated</option>
                <option value="shortlisted" {{ request('status') === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <select name="verdict" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                <option value="all">All Verdicts</option>
                <option value="strong_hire" {{ request('verdict') === 'strong_hire' ? 'selected' : '' }}>Strong Hire</option>
                <option value="hire" {{ request('verdict') === 'hire' ? 'selected' : '' }}>Hire</option>
                <option value="maybe" {{ request('verdict') === 'maybe' ? 'selected' : '' }}>Maybe</option>
                <option value="no_hire" {{ request('verdict') === 'no_hire' ? 'selected' : '' }}>No Hire</option>
                <option value="strong_no_hire" {{ request('verdict') === 'strong_no_hire' ? 'selected' : '' }}>Strong No Hire</option>
            </select>
            <div class="flex gap-2">
                <input type="number" name="min_score" value="{{ request('min_score') }}" placeholder="Min score" step="0.5" min="0" max="10"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                <input type="number" name="max_score" value="{{ request('max_score') }}" placeholder="Max score" step="0.5" min="0" max="10"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Filter
            </button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($candidates->isEmpty())
            <div class="py-16 text-center">
                <p class="text-gray-400 text-sm">No candidates found.</p>
                <a href="{{ route('candidates.create') }}" class="mt-2 inline-block text-sm text-gray-900 underline">Add the first one</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Name</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">GitHub</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Score</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Verdict</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Repos</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($candidates as $candidate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:underline">{{ $candidate->name }}</a>
                                    @if($candidate->email)
                                        <div class="text-xs text-gray-400">{{ $candidate->email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($candidate->github_username)
                                        <a href="https://github.com/{{ $candidate->github_username }}" target="_blank" class="text-gray-600 hover:underline">
                                            {{ $candidate->github_username }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'submitted' => 'bg-amber-100 text-amber-700',
                                            'analyzing' => 'bg-blue-100 text-blue-700',
                                            'evaluated' => 'bg-purple-100 text-purple-700',
                                            'shortlisted' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$candidate->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($candidate->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                <td class="px-4 py-3">
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
                                                'strong_hire' => 'text-green-700',
                                                'hire' => 'text-emerald-600',
                                                'maybe' => 'text-amber-600',
                                                'no_hire' => 'text-orange-600',
                                                'strong_no_hire' => 'text-red-600',
                                            ];
                                        @endphp
                                        <span class="font-medium {{ $verdictColors[$candidate->evaluation->verdict] ?? '' }}">
                                            {{ $verdictLabels[$candidate->evaluation->verdict] ?? '' }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $candidate->repositories->count() }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $candidate->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $candidates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
