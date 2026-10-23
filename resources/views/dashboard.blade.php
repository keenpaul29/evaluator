@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pipeline overview</p>
        </div>
        <div class="text-xs text-gray-400">{{ now()->format('M j, Y') }}</div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
            $statCards = [
                ['label' => 'Total', 'value' => $stats['total'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', 'color' => 'text-gray-600'],
                ['label' => 'Submitted', 'value' => $stats['submitted'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'color' => 'text-amber-600'],
                ['label' => 'Analyzing', 'value' => $stats['analyzing'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>', 'color' => 'text-blue-600'],
                ['label' => 'Evaluated', 'value' => $stats['evaluated'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'color' => 'text-purple-600'],
                ['label' => 'Shortlisted', 'value' => $stats['shortlisted'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>', 'color' => 'text-green-600'],
                ['label' => 'Avg Score', 'value' => $stats['avg_score'] . '<span class="text-sm font-normal text-gray-400">/10</span>', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h6a2 2 0 002-2V3"/></svg>', 'color' => 'text-gray-600'],
            @endphp
            @foreach($statCards as $card)
                <x-card variant="elevated" padding="md" class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0 {{ $card['color'] }}">
                        {!! $card['icon'] !!}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-gray-500 font-medium">{{ $card['label'] }}</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-1">{!! $card['value'] !!}</div>
                    </div>
                </x-card>
            @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-card variant="elevated" class="lg:col-span-2">
            <x-slot:header>Recent Candidates</x-slot:header>

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
                <x-table :headers="[
                    ['label' => 'Name', 'key' => 'name'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Score', 'key' => 'score', 'align' => 'right'],
                    ['label' => 'Verdict', 'key' => 'verdict'],
                    ['label' => 'Added', 'key' => 'created_at'],
                ]" :rows="{{ $recentCandidates->map(function($candidate) {
                    @php
                        $statusColors = [
                            'submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                            'analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                            'evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                            'shortlisted' => 'bg-green-50 text-green-700 ring-green-600/20',
                            'rejected' => 'bg-red-50 text-red-700 ring-red-600/20',
                        ];
                        $verdictColors = [
                            'strong_hire' => 'text-green-600',
                            'hire' => 'text-emerald-600',
                            'maybe' => 'text-amber-600',
                            'no_hire' => 'text-orange-600',
                            'strong_no_hire' => 'text-red-600',
                        ];
                        $verdictLabels = [
                            'strong_hire' => 'Strong Hire',
                            'hire' => 'Hire',
                            'maybe' => 'Maybe',
                            'no_hire' => 'No Hire',
                            'strong_no_hire' => 'Strong No Hire',
                        ];
                    @endphp
                    return [
                        'name' => '<a href="' . route('candidates.show', $candidate) . '" class="font-medium text-gray-900 hover:text-accent transition-colors">' . $candidate->name . '</a>',
                        'status' => '<x-badge label="' . $candidate->status->label() . '" variant="status-' . $candidate->status->value . '" size="sm" />',
                        'score' => '<span class="font-mono text-sm text-gray-700">' . ($candidate->evaluation?->overall_score ?? '—') . '</span>',
                        'verdict' => $candidate->evaluation ? '<span class="text-xs font-medium ' . ($verdictColors[$candidate->evaluation->verdict] ?? '') . '">' . ($verdictLabels[$candidate->evaluation->verdict] ?? '') . '</span>' : '<span class="text-gray-300">—</span>',
                        'created_at' => $candidate->created_at->diffForHumans(),
                    ];
                }) }}" />
            @endif
        </x-card>

        <div class="space-y-4">
            <x-card variant="elevated">
                <x-slot:header>Pipeline</x-slot:header>

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
            </x-card>

            <x-card variant="elevated">
                <x-slot:header>Verdicts</x-slot:header>

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
            </x-card>
        </div>
    </div>
@endsection
