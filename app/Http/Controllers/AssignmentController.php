<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Services\AssignmentGenerationService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function dispatch(Candidate $candidate)
    {
        $assignment = $candidate->evaluation?->assignment;

        if (! $assignment) {
            return back()->with('error', 'No take-home assignment has been generated for this candidate yet.');
        }

        if ($assignment->status !== AssignmentStatus::Generated) {
            return back()->with('error', 'Assignment was already dispatched.');
        }

        if (! in_array($assignment->evaluation->verdict, ['hire', 'strong_hire'], true)) {
            return back()->with('error', 'Only candidates with a hire or strong-hire verdict can receive a take-home assignment.');
        }

        $assignment->update([
            'status' => AssignmentStatus::Dispatched,
            'dispatched_at' => now(),
            'due_at' => now()->addDays(AssignmentGenerationService::DEFAULT_DUE_DAYS),
        ]);

        return back()->with('success', 'Take-home assignment dispatched to candidate.');
    }

    public function show(string $token)
    {
        $assignment = Assignment::with('candidate')
            ->where('token', $token)
            ->firstOrFail();

        if ($assignment->status === AssignmentStatus::Generated) {
            abort(404);
        }

        return view('assignments.show', compact('assignment'));
    }

    public function submit(Request $request, string $token)
    {
        $assignment = Assignment::with('candidate')
            ->where('token', $token)
            ->firstOrFail();

        abort_unless(
            $assignment->status === AssignmentStatus::Dispatched,
            404
        );

        $validated = $request->validate([
            'submitted_repo_url' => ['required', 'url'],
            'reflection' => ['required', 'string', 'min:50'],
        ]);

        $assignment->update([
            'status' => AssignmentStatus::Submitted,
            'submitted_repo_url' => $validated['submitted_repo_url'],
            'reflection' => $validated['reflection'],
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Assignment submitted. Our team will review it.');
    }
}
