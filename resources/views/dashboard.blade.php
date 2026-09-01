@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Candidate evaluation pipeline overview</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Total</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Submitted</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['submitted'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Analyzing</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['analyzing'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Evaluated</div>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['evaluated'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Shortlisted</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ $stats['shortlisted'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Avg Score</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['avg_score'] }}/10</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Candidates</h2>
            @if($recentCandidates->isEmpty())
                <p class="text-gray-400 text-sm py-8 text-center">No candidates yet. <a href="{{ route('candidates.create') }}" class="text-gray-900 underline">Add one</a>.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-2 font-medium text-gray-500">Name</th>
                                <th class="text-left py-2 font-medium text-gray-500">Status</th>
                                <th class="text-left py-2 font-medium text-gray-500">Score</th>
                                <th class="text-left py-2 font-medium text-gray-500">Verdict</th>
                                <th class="text-left py-2 font-medium text-gray-500">Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recentCandidates as $candidate)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2.5">
                                        <a href="{{ route('candidates.show', $candidate) }}" class="font-medium text-gray-900 hover:underline">{{ $candidate->name }}</a>
                                    </td>
                                    <td class="py-2.5">
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
                                    <td class="py-2.5 font-medium">{{ $candidate->evaluation?->overall_score ?? '—' }}</td>
                                    <td class="py-2.5">
                                        @if($candidate->evaluation)
                                            @php
                                                $verdictColors = [
                                                    'strong_hire' => 'text-green-700',
                                                    'hire' => 'text-emerald-600',
                                                    'maybe' => 'text-amber-600',
                                                    'no_hire' => 'text-orange-600',
                                                    'strong_no_hire' => 'text-red-600',
                                                ];
                                            @endphp
                                            <span class="font-medium {{ $verdictColors[$candidate->evaluation->verdict] ?? '' }}">
                                                {{ $candidate->evaluation->getVerdictLabel() }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-gray-500">{{ $candidate->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Pipeline Funnel</h2>
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
                            $width = max(5, ($count / $maxCount) * 100);
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">{{ $info['label'] }}</span>
                                <span class="font-medium">{{ $count }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $info['color'] }} rounded-full" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Verdict Distribution</h2>
                <div class="space-y-2">
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
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full {{ $verdictBg[$verdict] ?? 'bg-gray-400' }}"></div>
                                <span class="text-gray-600">{{ $verdictLabels[$verdict] ?? $verdict }}</span>
                            </div>
                            <span class="font-medium">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm text-center py-4">No evaluations yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
