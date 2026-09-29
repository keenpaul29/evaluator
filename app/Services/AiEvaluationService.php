<?php

namespace App\Services;

use App\Exceptions\AiEvaluationException;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\Evaluation\AiProviderClient;
use App\Services\Evaluation\CitationValidator;
use App\Services\Evaluation\EvaluationPromptBuilder;
use App\Services\Evaluation\EvaluationStorage;
use App\Services\Evaluation\VerdictCalculator;

class AiEvaluationService
{
    private EvaluationPromptBuilder $promptBuilder;

    private AiProviderClient $providerClient;

    private EvaluationStorage $storage;

    private CitationValidator $citationValidator;

    private VerdictCalculator $verdictCalculator;

    public function __construct(
        ?EvaluationPromptBuilder $promptBuilder = null,
        ?AiProviderClient $providerClient = null,
        ?EvaluationStorage $storage = null,
        ?CitationValidator $citationValidator = null,
        ?VerdictCalculator $verdictCalculator = null
    ) {
        $this->promptBuilder = $promptBuilder ?? new EvaluationPromptBuilder;
        $this->providerClient = $providerClient ?? new AiProviderClient;
        $this->storage = $storage ?? new EvaluationStorage;
        $this->citationValidator = $citationValidator ?? new CitationValidator;
        $this->verdictCalculator = $verdictCalculator ?? new VerdictCalculator;
    }

    public function evaluate(Candidate $candidate, array $repositoryAnalyses): Evaluation
    {
        $prompt = $this->promptBuilder->build($candidate, $repositoryAnalyses);

        $response = $this->providerClient->callWithFallback($prompt);

        $parsed = $this->parseResponse($response);

        $parsed['dimensions'] = $this->citationValidator->validate(
            $parsed['dimensions'],
            $repositoryAnalyses
        );

        $derived = $this->verdictCalculator->calculate($parsed['dimensions']);
        $parsed['overall_score'] = $derived['overall_score'];
        $parsed['verdict'] = $derived['verdict'];

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
