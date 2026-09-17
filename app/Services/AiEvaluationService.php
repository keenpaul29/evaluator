<?php

namespace App\Services;

use App\Exceptions\AiEvaluationException;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\Evaluation\AiProviderClient;
use App\Services\Evaluation\EvaluationPromptBuilder;
use App\Services\Evaluation\EvaluationStorage;

class AiEvaluationService
{
    private EvaluationPromptBuilder $promptBuilder;

    private AiProviderClient $providerClient;

    private EvaluationStorage $storage;

    public function __construct(
        ?EvaluationPromptBuilder $promptBuilder = null,
        ?AiProviderClient $providerClient = null,
        ?EvaluationStorage $storage = null
    ) {
        $this->promptBuilder = $promptBuilder ?? new EvaluationPromptBuilder;
        $this->providerClient = $providerClient ?? new AiProviderClient;
        $this->storage = $storage ?? new EvaluationStorage;
    }

    public function evaluate(Candidate $candidate, array $repositoryAnalyses): Evaluation
    {
        $prompt = $this->promptBuilder->build($candidate, $repositoryAnalyses);

        $response = $this->providerClient->callWithFallback($prompt);

        $parsed = $this->parseResponse($response);

        return $this->storage->store(
            $candidate,
            $parsed,
            $this->providerClient->get_model_used()
        );
    }

    private function parseResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleaned = preg_replace('/```json\s*/', '', $response);
            $cleaned = preg_replace('/```\s*/', '', $cleaned);
            $decoded = json_decode(trim($cleaned), true);
        }

        if (! $decoded || ! isset($decoded['overall_score'], $decoded['verdict'], $decoded['dimensions'])) {
            throw new AiEvaluationException('Invalid AI response format: '.substr($response, 0, 500));
        }

        return $decoded;
    }
}
