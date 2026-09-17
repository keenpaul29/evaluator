@extends('layouts.app')

@section('title', $candidate->name . ' - Evaluation')

@section('content')
<div class="space-y-4">
    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('candidates.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <h1 class="text-xl font-semibold text-gray-900 mt-1">{{ $candidate->name }}</h1>
            <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                @if($candidate->github_username)
                    <a href="https://github.com/{{ $candidate->github_username }}" target="_blank" class="hover:text-gray-700 inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        {{ $candidate->github_username }}
                    </a>
                @endif
                @if($candidate->email)
                    <span class="text-gray-400">·</span>
                    <span>{{ $candidate->email }}</span>
                @endif
                @php
                    $statusColors = [
                        'submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                        'analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                        'evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                        'shortlisted' => 'bg-green-50 text-green-700 ring-green-600/20',
                        'rejected' => 'bg-red-50 text-red-700 ring-red-600/20',
                    ];
                @endphp
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-badge text-xs font-medium ring-1 ring-inset {{ $statusColors[$candidate->status->value] ?? '' }}">
                    <span class="w-1 h-1 rounded-full bg-current"></span>
                    {{ $candidate->status->label() }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($candidate->status->value === 'evaluated' || $candidate->status->value === 'submitted')
                <form method="POST" action="{{ route('candidates.shortlist', $candidate) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-button hover:bg-green-700 transition-all duration-150 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Shortlist
                    </button>
                </form>
                <form method="POST" action="{{ route('candidates.reject', $candidate) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-button hover:bg-red-700 transition-all duration-150 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($candidate->status->value === 'analyzing')
        <div class="bg-blue-50 rounded-card border border-blue-100 p-5 text-center" x-data="pollStatus()" x-init="startPolling()">
            <div class="animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3"></div>
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
            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5 border-l-4 border-l-indigo-500">
                <h2 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wider">
                    First-Round Eliminator Recommendation
                </h2>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs text-gray-500 font-semibold">Onboarding Friction:</span>
                            @if($evaluation->onboarding_friction === 'low')
                                <span class="px-2.5 py-0.5 rounded-badge text-xs font-bold bg-green-100 text-green-800 ring-1 ring-green-600/20">Low (High Fit)</span>
                            @elseif($evaluation->onboarding_friction === 'medium')
                                <span class="px-2.5 py-0.5 rounded-badge text-xs font-bold bg-amber-100 text-amber-800 ring-1 ring-amber-600/20">Medium</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-badge text-xs font-bold bg-red-100 text-red-800 ring-1 ring-red-600/20">High (Low Fit)</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $evaluation->onboarding_friction_reason }}</p>
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
            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5 text-center">
                <div class="w-20 h-20 mx-auto rounded-full border-4 border-gray-100 flex items-center justify-center mb-3">
                    <div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $evaluation->overall_score }}</div>
                        <div class="text-xs text-gray-400">/10</div>
                    </div>
                </div>
                @php
                    $verdictColors = [
                        'strong_hire' => 'bg-green-50 text-green-700 ring-green-600/20',
                        'hire' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                        'maybe' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                        'no_hire' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                        'strong_no_hire' => 'bg-red-50 text-red-700 ring-red-600/20',
                    ];
                @endphp
                <span class="inline-flex px-2.5 py-1 rounded-badge text-sm font-semibold ring-1 ring-inset {{ $verdictColors[$evaluation->verdict] ?? '' }}">
                    {{ $evaluation->getVerdictLabel() }}
                </span>
                <div class="text-xs text-gray-400 mt-3">
                    {{ $evaluation->evaluated_at->diffForHumans() }} · {{ $evaluation->ai_model_used }}
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-card border border-gray-100 shadow-card p-5">
                <div class="text-xs text-gray-400 font-medium mb-3">Dimensions</div>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($evaluation->dimensions as $dim)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm text-gray-600">{{ $dim->getDimensionLabel() }}</span>
                                <span class="text-sm font-semibold text-gray-900 font-mono">{{ $dim->score }}</span>
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
                                <div class="h-full {{ $barColors[$dim->getScoreColor()] ?? 'bg-gray-400' }} rounded-full transition-all duration-500"
                                    style="width: {{ ($dim->score / 10) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
            <div class="text-xs text-gray-400 font-medium mb-3">Radar</div>
            <div class="max-w-md mx-auto">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">Strengths</span>
                </div>
                <ul class="space-y-2">
                    @foreach($evaluation->strengths as $strength)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-green-500 mt-0.5">&#10003;</span>
                            <span class="text-gray-600">{{ $strength }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">Concerns</span>
                </div>
                <ul class="space-y-2">
                    @foreach($evaluation->concerns as $concern)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-amber-500 mt-0.5">&#9888;</span>
                            <span class="text-gray-600">{{ $concern }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
            <div class="text-xs text-gray-400 font-medium mb-3">Summary</div>
            <div class="text-sm text-gray-600 leading-relaxed">
                {!! nl2br(e($evaluation->narrative_summary)) !!}
            </div>
        </div>

        <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
            <div class="text-xs text-gray-400 font-medium mb-3">Custom Interview Questions</div>
            <div class="space-y-2">
                @foreach($evaluation->interview_focus_areas as $area)
                    <div class="bg-gray-50 rounded-button p-3 text-sm text-gray-700 border-l-2 border-gray-900">
                        {{ $area }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
            <div class="text-xs text-gray-400 font-medium mb-4">Justifications</div>
            <div class="space-y-4">
                @foreach($evaluation->dimensions as $dim)
                    <div x-data="{ open: false }" class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                        <button @click="open = !open" class="w-full flex items-center justify-between text-left">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900">{{ $dim->getDimensionLabel() }}</span>
                                <span class="text-xs text-gray-400 font-mono">{{ $dim->score }}/10</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-2">
                            <p class="text-sm text-gray-500">{{ $dim->justification }}</p>
                            @if($dim->evidence && count($dim->evidence) > 0)
                                <ul class="mt-2 space-y-1">
                                    @foreach($dim->evidence as $item)
                                        <li class="text-xs text-gray-400 pl-3">· {{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($candidate->repositories->isNotEmpty())
        <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
            <div class="text-xs text-gray-400 font-medium mb-4">Repositories ({{ $candidate->repositories->count() }})</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($candidate->repositories as $repo)
                    <div class="bg-gray-50 rounded-card p-4 hover:bg-gray-100 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <a href="{{ $repo->html_url }}" target="_blank" class="text-sm font-medium text-gray-900 hover:text-accent transition-colors truncate block">
                                    {{ $repo->full_name }}
                                </a>
                                @if($repo->description)
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $repo->description }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                    @if($repo->primary_language)
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                            {{ $repo->primary_language }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                                        {{ $repo->stars_count }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                        {{ $repo->forks_count }}
                                    </span>
                                    @if($repo->is_fork)
                                        <span class="text-amber-500 font-medium">fork</span>
                                    @endif
                                </div>
                                @if($repo->analysis && $repo->analysis->authenticity_score !== null)
                                    <div class="mt-3 text-xs bg-white rounded-badge p-2.5 ring-1 ring-gray-200">
                                        <span class="font-medium text-gray-700">Authenticity:</span>
                                        @if($repo->analysis->authenticity_score >= 80)
                                            <span class="text-green-600 font-semibold">{{ $repo->analysis->authenticity_score }}% (Organic)</span>
                                        @elseif($repo->analysis->authenticity_score >= 50)
                                            <span class="text-amber-600 font-semibold">{{ $repo->analysis->authenticity_score }}% (Review)</span>
                                        @else
                                            <span class="text-red-600 font-bold">{{ $repo->analysis->authenticity_score }}% (Risk)</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if($repo->analysis)
                                <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-badge font-medium ring-1 ring-green-600/20">Analyzed</span>
                            @else
                                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-badge font-medium">Pending</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-card border border-gray-100 shadow-card p-5">
        <div class="text-xs text-gray-400 font-medium mb-3">Comments</div>
        @if($candidate->evaluation && $candidate->evaluation->comments->isNotEmpty())
            <div class="space-y-3 mb-4">
                @foreach($candidate->evaluation->comments as $comment)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600 shrink-0">
                            {{ strtoupper(substr($comment->hrUser->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <span class="font-medium text-gray-600">{{ $comment->hrUser->name }}</span>
                                <span>{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $comment->comment }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($candidate->evaluation)
            <form method="POST" action="{{ route('candidates.comment', $candidate) }}">
                @csrf
                <textarea name="comment" rows="2" required placeholder="Add a comment..."
                    class="w-full px-3 py-2 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"></textarea>
                @error('comment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <button type="submit" class="mt-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
                    Comment
                </button>
            </form>
        @else
            <p class="text-gray-400 text-sm">No evaluation yet.</p>
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
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderColor: 'rgb(37, 99, 235)',
                    pointBackgroundColor: 'rgb(37, 99, 235)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(37, 99, 235)',
                    pointRadius: 4,
                    borderWidth: 2
                }]
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
