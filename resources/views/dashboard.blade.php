@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pipeline overview</p>
        </div>
        <div class="text-xs text-gray-400">{{ now()->format('M j, Y') }}</div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-4">
            <div class="text-xs text-gray-500 font-medium">Total</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-4">
            <div class="text-xs text-gray-500 font-medium">Submitted</div>
            <div class="text-2xl font-semibold text-amber-600 mt-1">{{ $stats['submitted'] }}</div>
        </div>
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-4">
            <div class="text-xs text-gray-500 font-medium">Analyzing</div>
            <div class="text-2xl font-semibold text-blue-600 mt-1">{{ $stats['analyzing'] }}</div>
        </div>
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-4">
            <div class="text-xs text-gray-500 font-medium">Evaluated</div>
            <div class="text-2xl font-semibold text-purple-600 mt-1">{{ $stats['evaluated'] }}</div>
        </div>
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-4">
            <div class="text-xs text-gray-500 font-medium">Shortlisted</div>
            <div class="text-2xl font-semibold text-green-600 mt-1">{{ $stats['shortlisted'] }}</div>
        </div>
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-4">
            <div class="text-xs text-gray-500 font-medium">Avg Score</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $stats['avg_score'] }}<span class="text-sm font-normal text-gray-400">/10</span></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-card border border-gray-100 shadow-card">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Recent Candidates</h2>
            </div>
            @if($recentCandidates->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <p class="text-gray-500 text-sm">No candidates yet.</p>
                    <a href="{{ route('candidates.create') }}" class="mt-2 inline-flex items-center gap-1 text-sm text-accent hover:text-blue-700 font-medium">
                        Add the first one
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-50">
                                <th class="text-left px-5 py-2.5 font-medium text-gray-400 text-xs uppercase tracking-wider">Name</th>
                                <th class="text-left px-5 py-2.5 font-medium text-gray-400 text-xs uppercase tracking-wider">Status</th>
                                <th class="text-left px-5 py-2.5 font-medium text-gray-400 text-xs uppercase tracking-wider">Score</th>
                                <th class="text-left px-5 py-2.5 font-medium text-gray-400 text-xs uppercase tracking-wider">Verdict</th>
                                <th class="text-left px-5 py-2.5 font-medium text-gray-400 text-xs uppercase tracking-wider">Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recentCandidates as $candidate)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600">
                                                {{ strtoupper(substr($candidate->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:text-accent transition-colors">{{ $candidate->name }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
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
                                    <td class="px-5 py-3 font-mono text-sm text-gray-700">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        @if($candidate->evaluation)
                                            @php
                                                $verdictColors = [
                                                    'strong_hire' => 'text-green-600',
                                                    'hire' => 'text-emerald-600',
                                                    'maybe' => 'text-amber-600',
                                                    'no_hire' => 'text-orange-600',
                                                    'strong_no_hire' => 'text-red-600',
                                                ];
                                            @endphp
                                            <span class="text-xs font-medium {{ $verdictColors[$candidate->evaluation->verdict] ?? '' }}">
                                                {{ $candidate->evaluation->getVerdictLabel() }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $candidate->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Pipeline</h2>
                <div class="space-y-3">
                    @php
                        $funnel = [
                            'submitted' => ['label' => 'Submitted', 'color' => 'bg-amber-500'],
                            'analyzing' => ['label' => 'Analyzing', 'color' => 'bg-blue-500'],
                            'evaluated' => ['label' => 'Evaluated', 'color' => 'bg-purple-500'],
                            'shortlisted' => ['label' => 'Shortlisted', 'color' => 'bg-green-500'],
                        ];
                    @endphp
                    @foreach($funnel as $key => $info)
                        @php
                            $count = $pipeline[$key] ?? 0;
                            $pipelineValues = array_values($pipeline);
                            $maxCount = !empty($pipelineValues) ? max($pipelineValues) : 1;
                            $maxCount = max($maxCount, 1);
                            $width = $count > 0 ? max(4, ($count / $maxCount) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="text-gray-500 font-medium">{{ $info['label'] }}</span>
                                <span class="font-semibold text-gray-700">{{ $count }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden" x-data="{ width: 0 }" x-init="width = {{ $width }}" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="w-0" x-transition:enter-end="w-full">
                                <div class="h-full {{ $info['color'] }} rounded-full transition-all duration-500" :style="`width: ${width}%`"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Verdicts</h2>
                <div class="flex flex-wrap gap-2">
                    @php
                        $verdictLabels = [
                            'strong_hire' => 'Strong Hire',
                            'hire' => 'Hire',
                            'maybe' => 'Maybe',
                            'no_hire' => 'No Hire',
                            'strong_no_hire' => 'Strong No Hire',
                        ];
                        $verdictBg = [
                            'strong_hire' => 'bg-green-500',
                            'hire' => 'bg-emerald-500',
                            'maybe' => 'bg-amber-500',
                            'no_hire' => 'bg-orange-500',
                            'strong_no_hire' => 'bg-red-500',
                        ];
                    @endphp
                    @forelse($stats['verdicts'] as $verdict => $count)
                        <div class="flex items-center gap-2 px-2.5 py-1.5 bg-gray-50 rounded-badge">
                            <div class="w-2 h-2 rounded-full {{ $verdictBg[$verdict] ?? 'bg-gray-300' }}"></div>
                            <span class="text-xs font-medium text-gray-600">{{ $verdictLabels[$verdict] ?? $verdict }}</span>
                            <span class="text-xs font-semibold text-gray-900">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-gray-400 text-xs text-center py-4 w-full">No evaluations yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
