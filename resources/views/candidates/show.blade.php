@extends('layouts.app')

@section('title', $candidate->name . ' - Evaluation')

@section('content')
<div class="space-y-4">
    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('candidates.index') }}" class="text-xs text-gray-400 hover:text-gray-600">&larr; Back</a>
            <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ $candidate->name }}</h1>
            <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400">
                @if($candidate->github_username)
                    <a href="https://github.com/{{ $candidate->github_username }}" target="_blank" class="hover:text-gray-600">
                        {{ $candidate->github_username }}
                    </a>
                @endif
                @if($candidate->email)
                    <span>{{ $candidate->email }}</span>
                @endif
                @php
                    $statusColors = [
                        'submitted' => 'bg-amber-50 text-amber-600',
                        'analyzing' => 'bg-blue-50 text-blue-600',
                        'evaluated' => 'bg-purple-50 text-purple-600',
                        'shortlisted' => 'bg-green-50 text-green-600',
                        'rejected' => 'bg-red-50 text-red-600',
                    ];
                @endphp
                <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColors[$candidate->status] ?? '' }}">
                    {{ ucfirst($candidate->status) }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-1.5">
            @if($candidate->status === 'evaluated' || $candidate->status === 'submitted')
                <form method="POST" action="{{ route('candidates.shortlist', $candidate) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                        Shortlist
                    </button>
                </form>
                <form method="POST" action="{{ route('candidates.reject', $candidate) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                        Reject
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($candidate->status === 'analyzing')
        <div class="bg-blue-50 rounded-lg p-4 text-center" x-data="pollStatus()" x-init="startPolling()">
            <div class="animate-spin w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-2"></div>
            <p class="text-blue-700 text-sm font-medium">Evaluating...</p>
            <p class="text-blue-500 text-xs mt-0.5">Page refreshes automatically.</p>
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
        @if($evaluation->onboarding_friction)
            <div class="bg-white rounded-lg border border-gray-100 p-5 mb-4 shadow-sm border-l-4 border-l-indigo-500">
                <h2 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wider">
                    First-Round Eliminator Recommendation
                </h2>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs text-gray-500 font-semibold">Onboarding Friction:</span>
                            @if($evaluation->onboarding_friction === 'low')
                                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">Low (High Fit)</span>
                            @elseif($evaluation->onboarding_friction === 'medium')
                                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">Medium</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">High (Low Fit)</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $evaluation->onboarding_friction_reason }}</p>
                    </div>
                    
                    <div class="md:text-right border-t md:border-t-0 md:border-l border-gray-100 pt-3 md:pt-0 md:pl-5 md:min-w-[200px]">
                        <span class="block text-xs text-gray-400 mb-1">Recommended Action</span>
                        @if($evaluation->onboarding_friction === 'high' || $evaluation->overall_score < 5.0)
                            <div class="text-lg font-bold text-red-600 flex items-center md:justify-end gap-1.5">
                                <span>&times;</span> Auto-Reject
                            </div>
                        @else
                            <div class="text-lg font-bold text-green-600 flex items-center md:justify-end gap-1.5">
                                <span>&rarr;</span> Advance to Onsite
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg border border-gray-100 p-4">
                <div class="text-xs text-gray-400 mb-2">Score</div>
                <div class="text-3xl font-semibold text-gray-900">{{ $evaluation->overall_score }}</div>
                <div class="text-xs text-gray-400 mt-0.5">/10</div>
                <div class="mt-3">
                    @php
                        $verdictColors = [
                            'strong_hire' => 'bg-green-50 text-green-700',
                            'hire' => 'bg-emerald-50 text-emerald-700',
                            'maybe' => 'bg-amber-50 text-amber-700',
                            'no_hire' => 'bg-orange-50 text-orange-700',
                            'strong_no_hire' => 'bg-red-50 text-red-700',
                        ];
                    @endphp
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $verdictColors[$evaluation->verdict] ?? '' }}">
                        {{ $evaluation->getVerdictLabel() }}
                    </span>
                </div>
                <div class="text-xs text-gray-300 mt-3">
                    {{ $evaluation->evaluated_at->diffForHumans() }} · {{ $evaluation->ai_model_used }}
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-lg border border-gray-100 p-4">
                <div class="text-xs text-gray-400 mb-3">Dimensions</div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($evaluation->dimensions as $dim)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-600">{{ $dim->getDimensionLabel() }}</span>
                                <span class="text-xs font-medium text-gray-900">{{ $dim->score }}</span>
                            </div>
                            <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                @php
                                    $barColors = [
                                        'green' => 'bg-green-500',
                                        'emerald' => 'bg-emerald-500',
                                        'amber' => 'bg-amber-500',
                                        'orange' => 'bg-orange-500',
                                        'red' => 'bg-red-500',
                                    ];
                                @endphp
                                <div class="h-full {{ $barColors[$dim->getScoreColor()] ?? 'bg-gray-400' }} rounded-full"
                                    style="width: {{ ($dim->score / 10) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-100 p-4">
            <div class="text-xs text-gray-400 mb-3">Radar</div>
            <div class="max-w-sm mx-auto">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg border border-gray-100 p-4">
                <div class="text-xs text-gray-400 mb-2">Strengths</div>
                <ul class="space-y-1.5">
                    @foreach($evaluation->strengths as $strength)
                        <li class="flex items-start gap-1.5 text-xs">
                            <span class="text-green-500 mt-px">&#10003;</span>
                            <span class="text-gray-600">{{ $strength }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-lg border border-gray-100 p-4">
                <div class="text-xs text-gray-400 mb-2">Concerns</div>
                <ul class="space-y-1.5">
                    @foreach($evaluation->concerns as $concern)
                        <li class="flex items-start gap-1.5 text-xs">
                            <span class="text-amber-500 mt-px">&#9888;</span>
                            <span class="text-gray-600">{{ $concern }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-100 p-4">
            <div class="text-xs text-gray-400 mb-2">Summary</div>
            <div class="text-sm text-gray-600 leading-relaxed">
                {!! nl2br(e($evaluation->narrative_summary)) !!}
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-100 p-4">
            <div class="text-xs text-gray-400 mb-2">Custom Interview Questions</div>
            <div class="space-y-2">
                @foreach($evaluation->interview_focus_areas as $area)
                    <div class="bg-gray-50 rounded p-2.5 text-sm text-gray-700 border-l-2 border-gray-900">
                        {{ $area }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-100 p-4">
            <div class="text-xs text-gray-400 mb-3">Justifications</div>
            <div class="space-y-4">
                @foreach($evaluation->dimensions as $dim)
                    <div class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-gray-900">{{ $dim->getDimensionLabel() }}</span>
                            <span class="text-xs text-gray-400">{{ $dim->score }}/10</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $dim->justification }}</p>
                        @if($dim->evidence && count($dim->evidence) > 0)
                            <ul class="mt-1.5 space-y-0.5">
                                @foreach($dim->evidence as $item)
                                    <li class="text-xs text-gray-400 pl-3">· {{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($candidate->repositories->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-100 p-4">
            <div class="text-xs text-gray-400 mb-3">Repositories ({{ $candidate->repositories->count() }})</div>
            <div class="space-y-2">
                @foreach($candidate->repositories as $repo)
                    <div class="flex items-start justify-between py-2 border-b border-gray-50 last:border-0">
                        <div>
                            <a href="{{ $repo->html_url }}" target="_blank" class="text-sm font-medium text-gray-900 hover:text-gray-600">
                                {{ $repo->full_name }}
                            </a>
                            @if($repo->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $repo->description }}</p>
                            @endif
                            <div class="flex items-center gap-2 mt-1 text-xs text-gray-400">
                                @if($repo->primary_language)
                                    <span>{{ $repo->primary_language }}</span>
                                @endif
                                <span>&#9733; {{ $repo->stars_count }}</span>
                                <span>&#9741; {{ $repo->forks_count }}</span>
                                @if($repo->is_fork)
                                    <span class="text-amber-500">fork</span>
                                @endif
                            </div>
                            @if($repo->analysis && $repo->analysis->authenticity_score !== null)
                                <div class="mt-2 text-xs bg-gray-50 p-2 rounded">
                                    <span class="font-medium text-gray-700">Authenticity Score:</span>
                                    @if($repo->analysis->authenticity_score >= 80)
                                        <span class="text-green-600 font-medium">{{ $repo->analysis->authenticity_score }}% (Organic)</span>
                                    @elseif($repo->analysis->authenticity_score >= 50)
                                        <span class="text-amber-600 font-medium">{{ $repo->analysis->authenticity_score }}% (Needs Review)</span>
                                    @else
                                        <span class="text-red-600 font-bold">{{ $repo->analysis->authenticity_score }}% (High AI/Copy Risk)</span>
                                    @endif
                                    @if(!empty($repo->analysis->authenticity_flags))
                                        <ul class="mt-1 space-y-0.5">
                                            @foreach($repo->analysis->authenticity_flags as $flag)
                                                <li class="text-gray-500 pl-2">· {{ $flag }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($repo->analysis)
                            <span class="text-xs text-green-600 bg-green-50 px-1.5 py-0.5 rounded">Analyzed</span>
                        @else
                            <span class="text-xs text-gray-300 bg-gray-50 px-1.5 py-0.5 rounded">Pending</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-100 p-4">
        <div class="text-xs text-gray-400 mb-3">Comments</div>
        @if($candidate->evaluation && $candidate->evaluation->comments->isNotEmpty())
            <div class="space-y-2 mb-3">
                @foreach($candidate->evaluation->comments as $comment)
                    <div class="bg-gray-50 rounded p-2.5">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-0.5">
                            <span class="font-medium text-gray-600">{{ $comment->hrUser->name }}</span>
                            <span>&middot;</span>
                            <span>{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-gray-600">{{ $comment->comment }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if($candidate->evaluation)
            <form method="POST" action="{{ route('candidates.comment', $candidate) }}">
                @csrf
                <textarea name="comment" rows="2" required placeholder="Add a comment..."
                    class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-gray-900 focus:border-gray-900"></textarea>
                @error('comment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <button type="submit" class="mt-1.5 px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                    Comment
                </button>
            </form>
        @else
            <p class="text-gray-300 text-xs">No evaluation yet.</p>
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
        'problem_complexity' => 'Complexity',
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
                    backgroundColor: 'rgba(17, 24, 39, 0.05)',
                    borderColor: 'rgb(17, 24, 39)',
                    pointBackgroundColor: 'rgb(17, 24, 39)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(17, 24, 39)',
                    pointRadius: 3,
                    borderWidth: 1.5
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 2, font: { size: 10 } },
                        pointLabels: { font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
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
@endpush
@endsection
