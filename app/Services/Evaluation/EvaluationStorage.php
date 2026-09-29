<?php

namespace App\Services\Evaluation;

use App\Enums\CandidateStatus;
use App\Exceptions\AiEvaluationException;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;

class EvaluationStorage
{
    public function store(Candidate $candidate, array $parsed, string $aiModelUsed): Evaluation
    {
        $this->validate($parsed);

        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => $parsed['overall_score'],
            'verdict' => $parsed['verdict'],
            'onboarding_friction' => $parsed['onboarding_friction'] ?? null,
            'onboarding_friction_reason' => $parsed['onboarding_friction_reason'] ?? null,
            'narrative_summary' => $parsed['narrative_summary'] ?? '',
            'strengths' => $parsed['strengths'] ?? [],
            'concerns' => $parsed['concerns'] ?? [],
            'interview_focus_areas' => $parsed['interview_focus_areas'] ?? [],
            'ai_model_used' => $aiModelUsed,
            'evaluated_at' => now(),
        ]);

        $dimensions = $parsed['dimensions'] ?? [];
        foreach ($dimensions as $dim) {
            EvaluationDimension::create([
                'evaluation_id' => $evaluation->id,
                'dimension' => $dim['dimension'],
                'score' => $dim['score'],
                'justification' => $dim['justification'] ?? '',
                'evidence' => $dim['evidence'] ?? [],
            ]);
        }

        $candidate->update(['status' => CandidateStatus::Evaluated]);

        return $evaluation;
    }

    private function validate(array $parsed): void
    {
        if (! isset($parsed['overall_score']) || ! is_numeric($parsed['overall_score'])) {
            throw new AiEvaluationException('Invalid AI response: missing or non-numeric overall_score');
        }

        $score = (float) $parsed['overall_score'];
        if ($score < 0 || $score > 10) {
            throw new AiEvaluationException("Invalid AI response: overall_score {$score} out of range 0-10");
        }

        $validVerdicts = ['strong_hire', 'hire', 'maybe', 'no_hire', 'strong_no_hire', 'insufficient_data'];
        if (! isset($parsed['verdict']) || ! in_array($parsed['verdict'], $validVerdicts)) {
            throw new AiEvaluationException('Invalid AI response: invalid verdict');
        }

        if (! isset($parsed['dimensions']) || ! is_array($parsed['dimensions'])) {
            throw new AiEvaluationException('Invalid AI response: missing dimensions');
        }

        if (count($parsed['dimensions']) !== 7) {
            throw new AiEvaluationException('Invalid AI response: expected 7 dimensions, got '.count($parsed['dimensions']));
        }

        $validDimensions = [
            'code_quality', 'technical_judgment', 'colvalues_alignment',
            'communication', 'problem_complexity', 'learning_trajectory',
            'technical_breadth',
        ];

        foreach ($parsed['dimensions'] as $dim) {
            if (! isset($dim['dimension']) || ! in_array($dim['dimension'], $validDimensions)) {
                throw new AiEvaluationException('Invalid AI response: invalid dimension '.$dim['dimension'] ?? 'null');
            }

            if (! isset($dim['score']) || ! is_numeric($dim['score'])) {
                throw new AiEvaluationException('Invalid AI response: non-numeric score for '.$dim['dimension']);
            }

            $dimScore = (float) $dim['score'];
            if ($dimScore < 0 || $dimScore > 10) {
                throw new AiEvaluationException("Invalid AI response: dimension score {$dimScore} out of range for {$dim['dimension']}");
            }
        }
    }
}
