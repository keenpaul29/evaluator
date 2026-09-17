<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = Cache::remember('dashboard_stats', 60, function () {
            $stats = [
                'total' => Candidate::count(),
                'submitted' => Candidate::submitted()->count(),
                'analyzing' => Candidate::analyzed()->count(),
                'evaluated' => Candidate::evaluated()->count(),
                'shortlisted' => Candidate::shortlisted()->count(),
                'rejected' => Candidate::rejected()->count(),
            ];

            $stats['avg_score'] = Evaluation::avg('overall_score');
            $stats['avg_score'] = $stats['avg_score'] ? round($stats['avg_score'], 1) : 0;

            $verdictCounts = Evaluation::selectRaw('verdict, count(*) as count')
                ->groupBy('verdict')
                ->pluck('count', 'verdict')
                ->toArray();

            $stats['verdicts'] = $verdictCounts;

            return $stats;
        });

        $recentCandidates = Candidate::with('evaluation')
            ->latest()
            ->take(10)
            ->get();

        $pipeline = Cache::remember('dashboard_pipeline', 60, function () {
            return Candidate::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        });

        return view('dashboard', compact('stats', 'recentCandidates', 'pipeline'));
    }
}
