<?php

namespace Tests\Unit;

use App\Exceptions\AiEvaluationException;
use App\Services\Evaluation\VerdictCalculator;
use PHPUnit\Framework\TestCase;

class VerdictCalculatorTest extends TestCase
{
    private VerdictCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new VerdictCalculator;
    }

    public function test_all_evidence_backed_dimensions_yield_weighted_verdict(): void
    {
        $dimensions = $this->dimensions(dimensionScores: [
            'code_quality' => 8.0,
            'technical_judgment' => 7.0,
            'colvalues_alignment' => 9.0,
            'communication' => 6.0,
            'problem_complexity' => 7.0,
            'learning_trajectory' => 8.0,
            'technical_breadth' => 6.0,
        ]);

        $result = $this->calculator->calculate($dimensions);

        $this->assertSame('hire', $result['verdict']);

        $weighted = (8.0 + 7.0 + (9.0 * 2) + 6.0 + 7.0 + 8.0 + 6.0) / 8.0;
        $this->assertEqualsWithDelta(round($weighted, 1), $result['overall_score'], 0.001);
    }

    public function test_colvalues_flagged_excludes_score_and_caps_strong_verdict(): void
    {
        $dimensions = $this->dimensions(dimensionScores: [
            'code_quality' => 9.0,
            'technical_judgment' => 9.0,
            'communication' => 9.0,
            'problem_complexity' => 9.0,
            'learning_trajectory' => 9.0,
            'technical_breadth' => 9.0,
        ]);

        $dimensions[2]['evidence'] = [['insufficient' => true]];

        $result = $this->calculator->calculate($dimensions);

        $this->assertSame('maybe', $result['verdict']);
        $this->assertSame(9.0, $result['overall_score']);
    }

    public function test_fewer_than_two_evidence_backed_dimensions_yields_insufficient_data(): void
    {
        $dimensions = $this->dimensions();

        $dimensions[0]['evidence'] = [['insufficient' => true]];
        $dimensions[1]['evidence'] = [['insufficient' => true]];
        $dimensions[2]['evidence'] = [['insufficient' => true]];
        $dimensions[3]['evidence'] = [['insufficient' => true]];
        $dimensions[4]['evidence'] = [['insufficient' => true]];
        $dimensions[5]['evidence'] = [['insufficient' => true]];

        $result = $this->calculator->calculate($dimensions);

        $this->assertSame('insufficient_data', $result['verdict']);
        $this->assertSame(0.0, $result['overall_score']);
    }

    public function test_exactly_two_evidence_backed_dimensions_satisfy_threshold(): void
    {
        $dimensions = $this->dimensions();

        $dimensions[0]['score'] = 2.0;
        $dimensions[1]['score'] = 2.0;
        $dimensions[2]['evidence'] = [['insufficient' => true]];
        $dimensions[3]['evidence'] = [['insufficient' => true]];
        $dimensions[4]['evidence'] = [['insufficient' => true]];
        $dimensions[5]['evidence'] = [['insufficient' => true]];
        $dimensions[6]['evidence'] = [['insufficient' => true]];

        $result = $this->calculator->calculate($dimensions);

        $this->assertSame('strong_no_hire', $result['verdict']);
        $this->assertSame(2.0, $result['overall_score']);
    }

    public function test_rejects_wrong_dimension_count(): void
    {
        $this->expectException(AiEvaluationException::class);

        $this->calculator->calculate(array_slice($this->dimensions(), 0, 5));
    }

    private function dimensions(array $dimensionScores = []): array
    {
        $names = [
            'code_quality',
            'technical_judgment',
            'colvalues_alignment',
            'communication',
            'problem_complexity',
            'learning_trajectory',
            'technical_breadth',
        ];

        $defaults = [
            'code_quality' => 8.0,
            'technical_judgment' => 8.0,
            'colvalues_alignment' => 8.0,
            'communication' => 8.0,
            'problem_complexity' => 8.0,
            'learning_trajectory' => 8.0,
            'technical_breadth' => 8.0,
        ];

        return array_map(fn (string $name) => [
            'dimension' => $name,
            'score' => $dimensionScores[$name] ?? $defaults[$name],
            'justification' => 'ok',
            'evidence' => [['file_path' => 'app/Services/GameService.php', 'commit_sha' => 'abc123d', 'url' => 'https://github.com/janedoe/rpg-app']],
        ], $names);
    }
}
