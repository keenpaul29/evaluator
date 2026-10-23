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
            $statusCounts = Candidate::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $stats = [
                'total' => array_sum($statusCounts),
                'submitted' => $statusCounts['submitted'] ?? 0,
                'analyzing' => $statusCounts['analyzing'] ?? 0,
                'evaluated' => $statusCounts['evaluated'] ?? 0,
                'shortlisted' => $statusCounts['shortlisted'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
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
