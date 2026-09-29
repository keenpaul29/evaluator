<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\InterviewQuestion;
use App\Services\Evaluation\AiProviderClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InterviewQuestionService
{
    private const SCORE_THRESHOLD = 6.0;

    private const QUESTIONS_PER_DIMENSION = 2;

    public function __construct(
        private AiProviderClient $client
    ) {}

    public function generate(Candidate $candidate, Evaluation $evaluation): array
    {
        $weakDimensions = $evaluation->dimensions
            ->where('score', '<', self::SCORE_THRESHOLD)
            ->sortBy('score')
            ->take(3);

        if ($weakDimensions->isEmpty()) {
            $weakDimensions = $evaluation->dimensions->sortBy('score')->take(2);
        }

        $prompt = $this->buildPrompt($candidate, $evaluation, $weakDimensions);

        try {
            $response = $this->callAi($prompt);
            $parsed = $this->parseResponse($response);

            return $this->store($evaluation, $parsed);
        } catch (\Exception $e) {
            Log::warning('Failed to generate interview questions', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function buildPrompt(Candidate $candidate, Evaluation $evaluation, $weakDimensions): string
    {
        $context = ColoredCowContext::getFullContext();
        $dimLabels = [
            'code_quality' => 'Code Quality',
            'technical_judgment' => 'Technical Judgment',
            'colvalues_alignment' => 'ColoredCow Values Alignment',
            'communication' => 'Communication',
            'problem_complexity' => 'Problem Complexity',
            'learning_trajectory' => 'Learning Trajectory',
            'technical_breadth' => 'Technical Breadth',
        ];

        $dimSummaries = $weakDimensions->map(function ($dim) use ($dimLabels) {
            $label = $dimLabels[$dim->dimension] ?? $dim->dimension;

            return "{$label}: {$dim->score}/10 — {$dim->justification}";
        })->implode("\n");

        $repoInfo = $candidate->repositories->map(function ($repo) {
            $analysis = $repo->analysis;
            $patterns = $analysis?->architectural_patterns ? implode(', ', $analysis->architectural_patterns) : 'none';
            $languages = $analysis?->primary_languages ? implode(', ', array_keys($analysis->primary_languages)) : 'unknown';

            return "- {$repo->full_name}: {$repo->primary_language}, patterns: {$patterns}, languages: {$languages}";
        })->implode("\n");

        $backendStack = implode(', ', $context['tech_stack']['backend']['primary'] ?? []);
        $frontendStack = implode(', ', $context['tech_stack']['frontend']['primary'] ?? []);
        $questionsPerDim = self::QUESTIONS_PER_DIMENSION;

        return <<<EOT
        Generate specific, file-referencing interview questions for a candidate evaluation.

        CANDIDATE: {$candidate->name} ({$candidate->github_username})

        REPOSITORIES:
        {$repoInfo}

        DIMENSIONS TO FOCUS ON (weakest areas):
        {$dimSummaries}

        TECH STACK CONTEXT:
        Backend: {$backendStack}
        Frontend: {$frontendStack}

        Generate {$questionsPerDim} questions per weak dimension. Each question should:
        1. Reference a specific repository and file path when possible
        2. Be based on actual code patterns found in the analysis
        3. Target the specific weakness identified in the dimension score
        4. Be answerable in a 5-minute interview segment

        OUTPUT FORMAT (JSON only):
        {
            "questions": [
                {
                    "dimension": "code_quality",
                    "question": "In your [repo-name] repo, you [specific observation]. How would you approach [refactoring/improvement]?",
                    "repo_reference": "owner/repo-name",
                    "file_reference": "path/to/file.php",
                    "why_ask": "Score: X/10 — [explanation of why this question matters for this dimension]"
                }
            ]
        }

        Be specific. Reference actual files and patterns. Make questions that reveal thinking, not just knowledge.
        EOT;
    }

    private function callAi(string $prompt): string
    {
        return $this->client->callWithFallback($prompt);
    }

    private function parseResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleaned = preg_replace('/```json\s*/', '', $response);
            $cleaned = preg_replace('/```\s*/', '', $cleaned);
            $decoded = json_decode(trim($cleaned), true);
        }

        if (! $decoded || ! isset($decoded['questions']) || ! is_array($decoded['questions'])) {
            throw new \RuntimeException('Invalid AI response format');
        }

        return $decoded['questions'];
    }

    private function store(Evaluation $evaluation, array $questions): array
    {
        $batchId = Str::uuid();
        $stored = [];

        foreach ($questions as $q) {
            if (! isset($q['dimension'], $q['question'])) {
                continue;
            }

            $stored[] = InterviewQuestion::create([
                'evaluation_id' => $evaluation->id,
                'dimension' => $q['dimension'],
                'question' => $q['question'],
                'repo_reference' => $q['repo_reference'] ?? null,
                'file_reference' => $q['file_reference'] ?? null,
                'why_ask' => $q['why_ask'] ?? '',
                'generated_at' => now(),
                'batch_id' => $batchId,
            ]);
        }

        return $stored;
    }
}
