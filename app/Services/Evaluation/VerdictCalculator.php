<?php

namespace App\Services\Evaluation;

use App\Exceptions\AiEvaluationException;

class VerdictCalculator
{
    private const VALID_DIMENSIONS = [
        'code_quality',
        'technical_judgment',
        'colvalues_alignment',
        'communication',
        'problem_complexity',
        'learning_trajectory',
        'technical_breadth',
    ];

    private const COLVALUES_DIMENSION = 'colvalues_alignment';

    private const MIN_EVIDENCE_BACKED = 2;

    private const COLVALUES_WEIGHT = 2.0;

    private const VERDICT_SCORE_MAP = [
        'strong_hire' => 8.5,
        'hire' => 7.0,
        'maybe' => 5.0,
        'no_hire' => 3.0,
        'strong_no_hire' => 0,
    ];

    /**
     * Derive overall score and verdict from evidence-backed dimensions only
     * (E1/E5): flagged ("insufficient") dimensions are recorded for display but
     * excluded from the verdict. A verdict requires at least 2 evidence-backed
     * scored dimensions, else it is `insufficient_data`. ColValues carries 2×
     * weight when evidence-backed, and hire/strong_hire are prohibited when
     * ColValues is flagged.
     *
     * @throws AiEvaluationException when the input is malformed
     */
    public function calculate(array $dimensions): array
    {
        $this->assertValidDimensions($dimensions);

        $weightedSum = 0.0;
        $weightTotal = 0.0;
        $colValuesFlagged = false;

        foreach ($dimensions as $dim) {
            $flagged = $this->isFlagged($dim['evidence'] ?? []);

            if ($dim['dimension'] === self::COLVALUES_DIMENSION && $flagged) {
                $colValuesFlagged = true;
            }

            if ($flagged) {
                continue;
            }

            $weight = $dim['dimension'] === self::COLVALUES_DIMENSION
                ? self::COLVALUES_WEIGHT
                : 1.0;

            $weightedSum += (float) $dim['score'] * $weight;
            $weightTotal += $weight;
        }

        $evidenceBackedCount = $this->evidenceBackedCount($dimensions);

        if ($evidenceBackedCount < self::MIN_EVIDENCE_BACKED) {
            return $this->insufficientDataResult();
        }

        if ($weightTotal <= 0) {
            return $this->insufficientDataResult();
        }

        $score = round($weightedSum / $weightTotal, 1);

        $verdict = $this->verdictForScore($score);

        if ($colValuesFlagged && in_array($verdict, ['hire', 'strong_hire'], true)) {
            $verdict = 'maybe';
        }

        return [
            'overall_score' => $score,
            'verdict' => $verdict,
        ];
    }

    private function assertValidDimensions(array $dimensions): void
    {
        if (count($dimensions) !== 7) {
            throw new AiEvaluationException('Invalid AI response: expected 7 dimensions, got '.count($dimensions));
        }

        foreach ($dimensions as $dim) {
            if (! isset($dim['dimension']) || ! in_array($dim['dimension'], self::VALID_DIMENSIONS, true)) {
                throw new AiEvaluationException('Invalid AI response: invalid dimension '.($dim['dimension'] ?? 'null'));
            }

            if (! isset($dim['score']) || ! is_numeric($dim['score'])) {
                throw new AiEvaluationException('Invalid AI response: non-numeric score for '.$dim['dimension']);
            }
        }
    }

    private function evidenceBackedCount(array $dimensions): int
    {
        $count = 0;

        foreach ($dimensions as $dim) {
            if (! $this->isFlagged($dim['evidence'] ?? [])) {
                $count++;
            }
        }

        return $count;
    }

    private function isFlagged(array $evidence): bool
    {
        return $evidence === [['insufficient' => true]];
    }

    private function verdictForScore(float $score): string
    {
        $verdict = 'strong_no_hire';

        $map = array_reverse(self::VERDICT_SCORE_MAP, true);

        foreach ($map as $candidate => $minScore) {
            if ($score >= $minScore) {
                $verdict = $candidate;
            }
        }

        return $verdict;
    }

    private function insufficientDataResult(): array
    {
        return [
            'overall_score' => 0.0,
            'verdict' => 'insufficient_data',
        ];
    }
}
