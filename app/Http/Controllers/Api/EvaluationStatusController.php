<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;

class EvaluationStatusController extends Controller
{
    public function show(Candidate $candidate): JsonResponse
    {
        $candidate->load('evaluation');

        return response()->json([
            'id' => $candidate->id,
            'status' => $candidate->status->value,
            'has_evaluation' => $candidate->evaluation !== null,
            'overall_score' => $candidate->evaluation?->overall_score,
            'verdict' => $candidate->evaluation?->verdict,
        ]);
    }
}
