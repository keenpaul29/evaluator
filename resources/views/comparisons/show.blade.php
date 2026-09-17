@extends('layouts.app')

@section('title', $comparison->name . ' - Comparison')

@section('content')
<div class="space-y-4">
    <div>
        <a href="{{ route('comparisons.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <h1 class="text-xl font-semibold text-gray-900 mt-1">{{ $comparison->name }}</h1>
    </div>

    @php
        $candidates = $comparison->candidates;
        $colors = ['rgb(37,99,235)', 'rgb(239,68,68)', 'rgb(16,185,129)', 'rgb(245,158,11)', 'rgb(139,92,246)'];
        $bgColors = ['rgba(37,99,235,0.08)', 'rgba(239,68,68,0.08)', 'rgba(16,185,129,0.08)', 'rgba(245,158,11,0.08)', 'rgba(139,92,246,0.08)'];
        $dimLabels = [
            'code_quality' => 'Code Quality',
            'technical_judgment' => 'Tech Judgment',
            'colvalues_alignment' => 'ColValues',
            'communication' => 'Communication',
            'problem_complexity' => 'Complexity',
            'learning_trajectory' => 'Learning',
            'technical_breadth' => 'Breadth',
        ];
    @endphp

    <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
        <div class="text-xs text-gray-400 font-medium mb-3">Radar Comparison</div>
        <div class="max-w-lg mx-auto">
            <canvas id="comparisonRadar"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-card border border-gray-100 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Dimension</th>
                        @foreach($candidates as $c)
                            <th class="text-center px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">{{ $c->name }}</th>
                        @endforeach
                        <th class="text-center px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider">Gap</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $allDimensions = collect($dimLabels)->keys(); @endphp
                    @foreach($allDimensions as $dim)
                        @php
                            $scores = $candidates->map(fn($c) => $c->evaluation?->dimensions->firstWhere('dimension', $dim)?->score ?? 0);
                            $max = $scores->max();
                            $min = $scores->min();
                            $gap = $max - $min;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $dimLabels[$dim] }}</td>
                            @foreach($candidates as $i => $c)
                                @php $score = $c->evaluation?->dimensions->firstWhere('dimension', $dim)?->score ?? 0; @endphp
                                <td class="px-5 py-3 text-center">
                                    @if($score >= $max && $score > 0)
                                        <span class="inline-flex px-2 py-0.5 rounded-badge text-sm font-semibold bg-green-50 text-green-700 ring-1 ring-green-600/20">
                                            {{ $score }}
                                        </span>
                                    @elseif($score > 0)
                                        <span class="text-sm font-mono {{ $score >= 7 ? 'text-green-600' : ($score >= 5 ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ $score }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-5 py-3 text-center text-sm text-gray-400 font-mono">
                                {{ $gap > 0 ? number_format($gap, 1) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50/80">
                        <td class="px-5 py-3 text-sm font-semibold text-gray-900">Overall</td>
                        @foreach($candidates as $c)
                            <td class="px-5 py-3 text-center text-sm font-semibold text-gray-900 font-mono">
                                {{ $c->evaluation?->overall_score ?? '—' }}
                            </td>
                        @endforeach
                        <td class="px-5 py-3 text-center text-sm text-gray-400 font-mono">
                            @php
                                $overallScores = $candidates->map(fn($c) => $c->evaluation?->overall_score ?? 0);
                                $overallGap = $overallScores->max() - $overallScores->min();
                            @endphp
                            {{ $overallGap > 0 ? number_format($overallGap, 1) : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($candidates as $c)
            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
                <div class="flex items-center justify-between mb-3">
                    <a href="{{ route('candidates.show', $c) }}" class="text-sm font-semibold text-gray-900 hover:text-accent transition-colors">{{ $c->name }}</a>
                    @if($c->evaluation)
                        @php
                            $verdictColors = [
                                'strong_hire' => 'text-green-600',
                                'hire' => 'text-emerald-600',
                                'maybe' => 'text-amber-600',
                                'no_hire' => 'text-orange-600',
                                'strong_no_hire' => 'text-red-600',
                            ];
                        @endphp
                        <span class="text-xs font-semibold {{ $verdictColors[$c->evaluation->verdict] ?? '' }}">
                            {{ $c->evaluation->getVerdictLabel() }}
                        </span>
                    @endif
                </div>
                @if($c->evaluation)
                    <div class="text-3xl font-semibold text-gray-900">{{ $c->evaluation->overall_score }}<span class="text-sm font-normal text-gray-400">/10</span></div>
                    <p class="text-sm text-gray-500 mt-3 line-clamp-3 leading-relaxed">{{ Str::limit($c->evaluation->narrative_summary, 150) }}</p>
                @else
                    <p class="text-sm text-gray-400">No evaluation yet</p>
                @endif
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const candidates = @json($candidates->map(fn($c) => [
        'name' => $c->name,
        'scores' => $c->evaluation ? $c->evaluation->dimensions->pluck('score')->values() : [],
    ]));
    const labels = @json(array_values($dimLabels));
    const colors = @json($colors);
    const bgColors = @json($bgColors);

    const ctx = document.getElementById('comparisonRadar');
    if (ctx) {
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: candidates.map((c, i) => ({
                    label: c.name,
                    data: c.scores,
                    fill: true,
                    backgroundColor: bgColors[i],
                    borderColor: colors[i],
                    pointBackgroundColor: colors[i],
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    borderWidth: 2,
                })),
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 2, font: { size: 10 }, backdropColor: 'transparent' },
                        pointLabels: { font: { size: 11, weight: '500' } },
                        grid: { color: 'rgba(0,0,0,0.06)' },
                        angleLines: { color: 'rgba(0,0,0,0.06)' }
                    }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
