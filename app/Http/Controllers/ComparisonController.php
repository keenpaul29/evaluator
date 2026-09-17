<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateComparison;
use App\Models\HrUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    public function index()
    {
        $comparisons = CandidateComparison::with('candidates.evaluation')
            ->latest()
            ->get();

        return view('comparisons.index', compact('comparisons'));
    }

    public function show(CandidateComparison $comparison)
    {
        $comparison->load(['candidates.evaluation.dimensions']);

        return view('comparisons.show', compact('comparison'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'candidate_ids' => 'required|array|min:2|max:10',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        $comparison = CandidateComparison::create([
            'name' => $validated['name'],
            'created_by' => HrUser::first()?->id,
        ]);

        $candidates = Candidate::whereIn('id', $validated['candidate_ids'])->get();

        foreach ($validated['candidate_ids'] as $index => $candidateId) {
            $comparison->candidates()->attach($candidateId, ['sort_order' => $index]);
        }

        $comparison->load('candidates.evaluation.dimensions');

        return response()->json([
            'id' => $comparison->id,
            'name' => $comparison->name,
            'candidates' => $comparison->candidates->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'overall_score' => $c->evaluation?->overall_score,
                'verdict' => $c->evaluation?->verdict,
                'dimensions' => $c->evaluation?->dimensions->map(fn ($d) => [
                    'dimension' => $d->dimension,
                    'score' => $d->score,
                ])->values() ?? [],
            ]),
        ]);
    }

    public function destroy(CandidateComparison $comparison)
    {
        $comparison->delete();

        return back()->with('success', 'Comparison deleted.');
    }
}
