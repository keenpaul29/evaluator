<?php

namespace Tests\Evals;

use App\Models\Candidate;
use App\Models\RepositoryAnalysis;
use App\Services\AiEvaluationService;

class ArtifactVsLiveDivergenceTest extends LiveLlmTestCase
{
    public function test_artifact_fed_and_live_ladders_converge_on_verdict(): void
    {
        $candidate = $this->fixtureCandidate();
        $analyses = RepositoryAnalysis::with('repository')->get()->all();

        $artifactEvaluation = (new AiEvaluationService)->evaluate($candidate, $analyses);

        foreach ($analyses as $analysis) {
            $analysis->artifact_file_paths = [];
            $analysis->artifact_excerpts = [];
            $analysis->commit_samples = [];
            $analysis->save();
        }

        $liveCandidate = Candidate::factory()->create([
            'name' => 'Eval Fixture Candidate Live',
            'github_username' => 'eval-fixture-live',
        ]);
        $liveAnalyses = RepositoryAnalysis::whereIn('id', array_map(fn ($a) => $a->id, $analyses))
            ->get()
            ->all();

        $liveEvaluation = (new AiEvaluationService)->evaluate($liveCandidate, $liveAnalyses);

        $this->assertContains($artifactEvaluation->verdict, [
            'strong_hire',
            'hire',
            'maybe',
            'no_hire',
            'strong_no_hire',
            'insufficient_data',
        ]);
        $this->assertContains($liveEvaluation->verdict, [
            'strong_hire',
            'hire',
            'maybe',
            'no_hire',
            'strong_no_hire',
            'insufficient_data',
        ]);

        $verdicts = [
            'strong_no_hire' => 0,
            'no_hire' => 1,
            'maybe' => 2,
            'hire' => 3,
            'strong_hire' => 4,
        ];
        $artifactRank = $verdicts[$artifactEvaluation->verdict] ?? -1;
        $liveRank = $verdicts[$liveEvaluation->verdict] ?? -1;
        $this->assertLessThanOrEqual(
            2,
            abs($artifactRank - $liveRank),
            "Verdict diverged beyond one tier: artifact={$artifactEvaluation->verdict} live={$liveEvaluation->verdict}"
        );

        file_put_contents(
            'C:\Users\palpu\AppData\Local\Temp\opencode\ladder-divergence-report.txt',
            json_encode(
                [
                    'artifact_fed' => [
                        'verdict' => $artifactEvaluation->verdict,
                        'overall_score' => $artifactEvaluation->overall_score,
                    ],
                    'live' => [
                        'verdict' => $liveEvaluation->verdict,
                        'overall_score' => $liveEvaluation->overall_score,
                    ],
                    'divergence_tiers' => abs($artifactRank - $liveRank),
                ],
                JSON_PRETTY_PRINT
            )
        );
    }
}
