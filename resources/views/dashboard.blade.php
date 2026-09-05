@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
        <p class="text-xs text-gray-400 mt-0.5">Pipeline overview</p>
    </div>

    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <div class="text-xs text-gray-400">Total</div>
            <div class="text-xl font-semibold text-gray-900 mt-0.5">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <div class="text-xs text-gray-400">Submitted</div>
            <div class="text-xl font-semibold text-amber-600 mt-0.5">{{ $stats['submitted'] }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <div class="text-xs text-gray-400">Analyzing</div>
            <div class="text-xl font-semibold text-blue-600 mt-0.5">{{ $stats['analyzing'] }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <div class="text-xs text-gray-400">Evaluated</div>
            <div class="text-xl font-semibold text-purple-600 mt-0.5">{{ $stats['evaluated'] }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <div class="text-xs text-gray-400">Shortlisted</div>
            <div class="text-xl font-semibold text-green-600 mt-0.5">{{ $stats['shortlisted'] }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3">
            <div class="text-xs text-gray-400">Avg Score</div>
            <div class="text-xl font-semibold text-gray-900 mt-0.5">{{ $stats['avg_score'] }}/10</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-100">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-medium text-gray-900">Recent Candidates</h2>
            </div>
            @if($recentCandidates->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-gray-400 text-sm">No candidates yet.</p>
                    <a href="{{ route('candidates.create') }}" class="text-xs text-gray-900 underline mt-1 inline-block">Add one</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-50">
                                <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Name</th>
                                <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Status</th>
                                <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Score</th>
                                <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Verdict</th>
                                <th class="text-left px-4 py-2 font-medium text-gray-400 text-xs">Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recentCandidates as $candidate)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:text-gray-600">{{ $candidate->name }}</a>
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
                                    <td class="px-4 py-2.5 text-gray-600">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                    <td class="px-4 py-2.5">
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
                                    <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $candidate->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-gray-100 p-4">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Pipeline</h2>
                <div class="space-y-2.5">
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
                            $width = max(4, ($count / $maxCount) * 100);
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-500">{{ $info['label'] }}</span>
                                <span class="font-medium text-gray-700">{{ $count }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $info['color'] }} rounded-full transition-all" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-100 p-4">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Verdicts</h2>
                <div class="space-y-1.5">
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
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full {{ $verdictBg[$verdict] ?? 'bg-gray-300' }}"></div>
                                <span class="text-gray-500">{{ $verdictLabels[$verdict] ?? $verdict }}</span>
                            </div>
                            <span class="font-medium text-gray-700">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-gray-300 text-xs text-center py-3">No evaluations yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
