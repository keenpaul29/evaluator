<?php

namespace Tests\Evals;

use App\Models\RepositoryAnalysis;
use App\Services\AiEvaluationService;

class LadderLiveEvalTest extends LiveLlmTestCase
{
    public function test_live_ladder_runs_with_citations_and_derives_verdict(): void
    {
        $candidate = $this->fixtureCandidate();
        $analyses = RepositoryAnalysis::with('repository')->get()->all();

        $service = new AiEvaluationService;
        $evaluation = $service->evaluate($candidate, $analyses);

        $this->assertNotNull($evaluation->id);
        $this->assertContains($evaluation->verdict, [
            'strong_hire',
            'hire',
            'maybe',
            'no_hire',
            'strong_no_hire',
            'insufficient_data',
        ]);
        $this->assertGreaterThanOrEqual(0.0, $evaluation->overall_score);
        $this->assertLessThanOrEqual(10.0, $evaluation->overall_score);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_.:-]+$/', $evaluation->ai_model_used);

        $this->assertCount(7, $evaluation->dimensions);
        foreach ($evaluation->dimensions as $dimension) {
            $this->assertIsArray($dimension->evidence);
            $this->assertNotEmpty($dimension->evidence);
            foreach ($dimension->evidence as $item) {
                $this->assertTrue(
                    isset($item['insufficient']) || isset($item['file_path']),
                    'Evidence entries must be citations or the insufficient marker, got: '.json_encode($item)
                );
            }
        }

        file_put_contents(
            'C:\Users\palpu\AppData\Local\Temp\opencode\live-ladder-report.txt',
            json_encode(
                [
                    'verdict' => $evaluation->verdict,
                    'overall_score' => $evaluation->overall_score,
                    'model' => $evaluation->ai_model_used,
                    'dimensions' => $evaluation->dimensions
                        ->map(fn ($d) => [
                            'dimension' => $d->dimension,
                            'score' => $d->score,
                            'evidence' => $d->evidence,
                        ])
                        ->values()
                        ->all(),
                ],
                JSON_PRETTY_PRINT
            )
        );
    }
}
