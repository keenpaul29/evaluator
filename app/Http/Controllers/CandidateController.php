<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Jobs\EvaluateCandidateJob;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use App\Models\HrUser;
use App\Services\CsvExporter;
use App\Services\GithubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $candidates = $this->candidateQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('candidates.index', compact('candidates'));
    }

    public function export(Request $request, CsvExporter $exporter): StreamedResponse
    {
        $candidates = $this->candidateQuery($request)
            ->with('repositories')
            ->latest()
            ->get();

        return $exporter->exportCandidates(
            $candidates,
            'candidates_'.now()->format('Y-m-d_His').'.csv'
        );
    }

    private function candidateQuery(Request $request)
    {
        $query = Candidate::with('evaluation');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('verdict') && $request->verdict !== 'all') {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('verdict', $request->verdict);
            });
        }

        if ($request->filled('min_score')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('overall_score', '>=', $request->min_score);
            });
        }

        if ($request->filled('max_score')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('overall_score', '<=', $request->max_score);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('github_username', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function create()
    {
        return view('candidates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'github_username' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url|max:500',
            'portfolio_url' => 'nullable|url|max:500',
            'repo_urls' => 'nullable|array',
            'repo_urls.*' => 'url',
            'notes' => 'nullable|string',
        ]);

        $candidate = Candidate::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'github_username' => $validated['github_username'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => CandidateStatus::Submitted,
            'submitted_by' => Auth::id() ?? HrUser::first()?->id,
            'submission_type' => 'hr_initiated',
        ]);

        if (! empty($validated['repo_urls'])) {
            $githubService = app(GithubService::class);
            $synced = $githubService->syncCandidateRepoUrls($validated['repo_urls'], $candidate->id);

            if (empty($synced)) {
                return redirect()->route('candidates.show', $candidate)
                    ->with('error', 'Candidate added, but provided repositories failed to synchronize. Evaluation was not queued.');
            }
        }

        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid(),
            'candidate_id' => $candidate->id,
            'status' => 'queued',
            'current_step' => 'queued',
            'steps_total' => 3,
        ]);

        EvaluateCandidateJob::dispatch($candidate->id, $progress->id);

        return redirect()->route('candidates.show', $candidate)
            ->with('success', 'Candidate added. Evaluation will begin shortly.');
    }

    public function show(Candidate $candidate)
    {
        $candidate->load(['repositories.analysis', 'evaluation.dimensions', 'evaluation.comments.hrUser', 'evaluation.interviewQuestions', 'evaluation.assignment']);

        return view('candidates.show', compact('candidate'));
    }

    public function addComment(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $evaluation = $candidate->evaluation;

        if (! $evaluation) {
            return back()->with('error', 'No evaluation exists for this candidate yet.');
        }

        $evaluation->comments()->create([
            'hr_user_id' => Auth::id() ?? HrUser::first()?->id,
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function shortlist(Candidate $candidate)
    {
        $candidate->update(['status' => CandidateStatus::Shortlisted]);

        return back()->with('success', 'Candidate shortlisted.');
    }

    public function reject(Candidate $candidate)
    {
        $candidate->update(['status' => CandidateStatus::Rejected]);

        return back()->with('success', 'Candidate rejected.');
    }
}
