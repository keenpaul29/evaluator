@extends('layouts.app')

@section('title', $candidate->name . ' - Evaluation Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('candidates.index') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to candidates</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $candidate->name }}</h1>
            <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                @if($candidate->github_username)
                    <a href="https://github.com/{{ $candidate->github_username }}" target="_blank" class="hover:text-gray-900">
                        github.com/{{ $candidate->github_username }}
                    </a>
                @endif
                @if($candidate->email)
                    <span>{{ $candidate->email }}</span>
                @endif
                <span class="text-gray-300">|</span>
                @php
                    $statusColors = [
                        'submitted' => 'bg-amber-100 text-amber-700',
                        'analyzing' => 'bg-blue-100 text-blue-700',
                        'evaluated' => 'bg-purple-100 text-purple-700',
                        'shortlisted' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$candidate->status] ?? '' }}">
                    {{ ucfirst($candidate->status) }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($candidate->status === 'evaluated' || $candidate->status === 'submitted')
                <form method="POST" action="{{ route('candidates.shortlist', $candidate) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Shortlist
                    </button>
                </form>
                <form method="POST" action="{{ route('candidates.reject', $candidate) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Reject
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($candidate->status === 'analyzing')
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center" x-data="pollStatus()" x-init="startPolling()">
            <div class="animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3"></div>
            <p class="text-blue-700 font-medium">Evaluation in progress...</p>
            <p class="text-blue-500 text-sm mt-1">This usually takes 1-2 minutes. Page will refresh automatically.</p>
        </div>
        @push('scripts')
        <script>
        function pollStatus() {
            return {
                polling: false,
                startPolling() {
                    this.polling = true;
                    this.poll();
                },
                async poll() {
                    if (!this.polling) return;
                    try {
                        const res = await fetch('{{ route("api.evaluation-status", $candidate) }}');
                        const data = await res.json();
                        if (data.has_evaluation || data.status === 'submitted') {
                            window.location.reload();
                        } else {
                            setTimeout(() => this.poll(), 3000);
                        }
                    } catch (e) {
                        setTimeout(() => this.poll(), 5000);
                    }
                }
            };
        }
        </script>
        @endpush
    @endif

    @if($candidate->evaluation)
        @php $evaluation = $candidate->evaluation; @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Overall Score</h2>
                <div class="text-5xl font-bold text-gray-900 mt-3">{{ $evaluation->overall_score }}</div>
                <div class="text-sm text-gray-500 mt-1">out of 10</div>
                <div class="mt-4">
                    @php
                        $verdictColors = [
                            'strong_hire' => 'bg-green-100 text-green-800',
                            'hire' => 'bg-emerald-100 text-emerald-800',
                            'maybe' => 'bg-amber-100 text-amber-800',
                            'no_hire' => 'bg-orange-100 text-orange-800',
                            'strong_no_hire' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $verdictColors[$evaluation->verdict] ?? '' }}">
                        {{ $evaluation->getVerdictLabel() }}
                    </span>
                </div>
                <div class="text-xs text-gray-400 mt-3">
                    Evaluated {{ $evaluation->evaluated_at->diffForHumans() }} via {{ $evaluation->ai_model_used }}
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Dimension Scores</h2>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($evaluation->dimensions as $dim)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $dim->getDimensionLabel() }}</span>
                                <span class="text-sm font-bold">{{ $dim->score }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                @php
                                    $barColors = [
                                        'green' => 'bg-green-500',
                                        'emerald' => 'bg-emerald-500',
                                        'amber' => 'bg-amber-500',
                                        'orange' => 'bg-orange-500',
                                        'red' => 'bg-red-500',
                                    ];
                                @endphp
                                <div class="h-full {{ $barColors[$dim->getScoreColor()] ?? 'bg-gray-500' }} rounded-full"
                                    style="width: {{ ($dim->score / 10) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Radar Chart</h2>
            <div class="max-w-lg mx-auto">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Strengths</h2>
                <ul class="space-y-2">
                    @foreach($evaluation->strengths as $strength)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-green-500 mt-0.5">&#10003;</span>
                            <span class="text-gray-700">{{ $strength }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Concerns</h2>
                <ul class="space-y-2">
                    @foreach($evaluation->concerns as $concern)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-amber-500 mt-0.5">&#9888;</span>
                            <span class="text-gray-700">{{ $concern }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Narrative Assessment</h2>
            <div class="prose prose-sm max-w-none text-gray-700">
                {!! nl2br(e($evaluation->narrative_summary)) !!}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Interview Focus Areas</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($evaluation->interview_focus_areas as $area)
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                        {{ $area }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detailed Justifications</h2>
            <div class="space-y-6">
                @foreach($evaluation->dimensions as $dim)
                    <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                        <h3 class="font-semibold text-gray-900">{{ $dim->getDimensionLabel() }} — {{ $dim->score }}/10</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $dim->justification }}</p>
                        @if($dim->evidence && count($dim->evidence) > 0)
                            <div class="mt-2">
                                <span class="text-xs font-medium text-gray-400 uppercase">Evidence</span>
                                <ul class="mt-1 space-y-1">
                                    @foreach($dim->evidence as $item)
                                        <li class="text-sm text-gray-500 ml-3">- {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($candidate->repositories->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Repositories ({{ $candidate->repositories->count() }})</h2>
            <div class="space-y-4">
                @foreach($candidate->repositories as $repo)
                    <div class="border border-gray-100 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <a href="{{ $repo->html_url }}" target="_blank" class="font-medium text-gray-900 hover:underline">
                                    {{ $repo->full_name }}
                                </a>
                                @if($repo->description)
                                    <p class="text-sm text-gray-500 mt-1">{{ $repo->description }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                    @if($repo->primary_language)
                                        <span>{{ $repo->primary_language }}</span>
                                    @endif
                                    <span>&#9733; {{ $repo->stars_count }}</span>
                                    <span>&#9741; {{ $repo->forks_count }}</span>
                                    @if($repo->is_fork)
                                        <span class="text-amber-500">(fork)</span>
                                    @endif
                                </div>
                            </div>
                            @if($repo->analysis)
                                <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Analyzed</span>
                            @else
                                <span class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">Not analyzed</span>
                            @endif
                        </div>
                        @if($repo->analysis)
                            @php $a = $repo->analysis; @endphp
                            <div class="grid grid-cols-4 gap-3 mt-3 text-xs">
                                <div class="bg-gray-50 rounded px-2 py-1">
                                    <span class="text-gray-400">Files:</span>
                                    <span class="font-medium ml-1">{{ $a->total_files_analyzed }}</span>
                                </div>
                                <div class="bg-gray-50 rounded px-2 py-1">
                                    <span class="text-gray-400">Lines:</span>
                                    <span class="font-medium ml-1">{{ number_format($a->total_lines_analyzed) }}</span>
                                </div>
                                <div class="bg-gray-50 rounded px-2 py-1">
                                    <span class="text-gray-400">Tests:</span>
                                    <span class="font-medium ml-1">{{ $a->has_tests ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="bg-gray-50 rounded px-2 py-1">
                                    <span class="text-gray-400">CI:</span>
                                    <span class="font-medium ml-1">{{ $a->has_ci_config ? 'Yes' : 'No' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">HR Comments</h2>
        @if($candidate->evaluation && $candidate->evaluation->comments->isNotEmpty())
            <div class="space-y-3 mb-4">
                @foreach($candidate->evaluation->comments as $comment)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                            <span class="font-medium text-gray-600">{{ $comment->hrUser->name }}</span>
                            <span>&middot;</span>
                            <span>{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-700">{{ $comment->comment }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if($candidate->evaluation)
            <form method="POST" action="{{ route('candidates.comment', $candidate) }}">
                @csrf
                <textarea name="comment" rows="2" required placeholder="Add a comment..."
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent"></textarea>
                @error('comment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <button type="submit" class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Add Comment
                </button>
            </form>
        @else
            <p class="text-gray-400 text-sm">No evaluation yet. Comments will be available after evaluation.</p>
        @endif
    </div>
</div>

@push('scripts')
@if($candidate->evaluation)
@php
    $dimLabels = [
        'code_quality' => 'Code Quality',
        'technical_judgment' => 'Tech Judgment',
        'colvalues_alignment' => 'ColValues',
        'communication' => 'Communication',
        'problem_complexity' => 'Problem Complexity',
        'learning_trajectory' => 'Learning',
        'technical_breadth' => 'Breadth',
    ];
    $dimNames = $candidate->evaluation->dimensions->pluck('dimension')->map(fn($d) => $dimLabels[$d] ?? $d)->values();
    $dimScores = $candidate->evaluation->dimensions->pluck('score')->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dimensions = @json($dimNames);
    const scores = @json($dimScores);

    const ctx = document.getElementById('radarChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: dimensions,
                datasets: [{
                    label: 'Score',
                    data: scores,
                    fill: true,
                    backgroundColor: 'rgba(17, 24, 39, 0.1)',
                    borderColor: 'rgb(17, 24, 39)',
                    pointBackgroundColor: 'rgb(17, 24, 39)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(17, 24, 39)'
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 2 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
@endif
@endsection
