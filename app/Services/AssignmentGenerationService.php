<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\Evaluation\AiProviderClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssignmentGenerationService
{
    public const DEFAULT_DUE_DAYS = 7;

    private const MAX_TITLE = 200;

    private const MAX_OBJECTIVE = 2000;

    private const MAX_CONTEXT = 2000;

    private const MAX_SHORT = 500;

    private const MAX_ITEM = 500;

    private const MAX_LIST_ITEMS = 10;

    public function __construct(
        private AiProviderClient $client
    ) {}

    public function generate(Candidate $candidate, Evaluation $evaluation): ?Assignment
    {
        $existing = Assignment::where('evaluation_id', $evaluation->id)->first();

        if ($existing) {
            return $existing;
        }

        $prompt = $this->buildPrompt($candidate, $evaluation);

        try {
            $brief = $this->callAi($prompt);
            $brief = $this->parseResponse($brief);

            return $this->store($evaluation, $brief);
        } catch (QueryException $e) {
            $concurrent = Assignment::where('evaluation_id', $evaluation->id)->first();

            if ($concurrent) {
                return $concurrent;
            }

            throw $e;
        } catch (\Exception $e) {
            Log::warning('Failed to generate take-home assignment', [
                'candidate_id' => $candidate->id,
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildPrompt(Candidate $candidate, Evaluation $evaluation): string
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

        $dimSummaries = $evaluation->dimensions->map(function ($dim) use ($dimLabels) {
            $label = $dimLabels[$dim->dimension] ?? $dim->dimension;

            return "{$label}: {$dim->score}/10 — {$dim->justification}";
        })->implode("\n");

        $repoInfo = $candidate->repositories->map(function ($repo) {
            $analysis = $repo->analysis;
            $patterns = $analysis?->architectural_patterns ? implode(', ', $analysis->architectural_patterns) : 'none';
            $languages = $analysis?->primary_languages ? implode(', ', array_keys($analysis->primary_languages)) : 'unknown';

            return "- {$repo->full_name}: {$repo->primary_language}, patterns: {$patterns}, languages: {$languages}";
        })->implode("\n");

        $values = implode('; ', array_map(
            fn ($name, $v) => "{$name} — {$v['description']}",
            array_keys($context['values']),
            $context['values']
        ));

        $portfolio = array_map(
            fn ($a) => "- {$a['anonymized_reference']} (sector: {$a['sector']}, stack: ".implode(', ', $a['stack']).')',
            $context['portfolio']['archetypes']
        );
        $portfolio = implode("\n", $portfolio);

        $backendStack = implode(', ', $context['tech_stack']['backend']['primary'] ?? []);
        $frontendStack = implode(', ', $context['tech_stack']['frontend']['primary'] ?? []);

        return <<<EOT
        You are a senior hiring engineer at ColoredCow. Write a personalized 7-day take-home assignment for a candidate who has passed the technical evaluation round. The assignment must reflect real work done at ColoredCow, be tailored to the candidate's demonstrated strengths and weaknesses, and be scoped for roughly 12-15 focused working hours across 7 days.

        Verdict: {$evaluation->verdict} (overall score {$evaluation->overall_score}/10)

        EVALUATION SUMMARIES (per dimension, with scores):
        {$dimSummaries}

        CANDIDATE REPOSITORIES:
        {$repoInfo}

        ABOUT COLOREDCOW:
        Company: {$context['company']}

        Values (evaluate against these):
        {$values}

        Tech stack at ColoredCow — Backend: {$backendStack}; Frontend: {$frontendStack}

        How ColoredCow builds:
        {$context['work_methods']}

        Portfolio (anonymize — never name a client, allude to sector + system shape only):
        {$portfolio}

        Hiring philosophy:
        {$context['hiring_philosophy']}

        OUTPUT FORMAT (JSON only), match these exact keys:
        {
            "title": "Concise working title for the assignment",
            "objective": "What the candidate must achieve, written as a brief a client would give",
            "context": "Short context paragraph grounded in a ColoredCow-style project arc (anonymized sector + system shape pulled from the portfolio list), plus why this exercise matters",
            "deliverables": ["3-5 concrete deliverables the candidate must produce"],
            "review_criteria": ["3-5 criteria mirroring how ColoredCow would actually review it: tests, CI, docs, code review readiness, run-without-us quality"],
            "timebox": "7 days, ~12-15 focused hours. State this explicitly.",
            "ai_use_note": "Permit AI tools as a thinking partner. State clearly: AI must not replace understanding; the work must be the candidate's own, and maturity expects honest attribution.",
            "submission": {"repo_url": "Provide a fresh GitHub repository", "reflection": "Short reflection: approach taken, decisions made, what stood in the way, what was learned"}
        }

        Rules:
        1. Make it a real, small project that reflects the kind of work we do — not an algorithm puzzle or DSA problem.
        2. Tailor it to the candidate's evaluation: lean into their stronger dimensions but include at least one need that targets their weakest dimension.
        3. Prefer ColoredCow's portfolio arcs and stack (Laravel/PHP/Elixir/WordPress backend, React/Vue frontend, PostgreSQL).
        4. Keep it scoped to 12-15 focused hours. Do not ask for two weeks of work.
        5. Anonymous briefs only — sector + system shape, never client names.
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

        if (! $decoded || ! isset($decoded['objective'], $decoded['deliverables'])) {
            throw new \RuntimeException('Invalid AI response format for take-home assignment');
        }

        if (! is_string($decoded['objective']) || ! is_array($decoded['deliverables'])) {
            throw new \RuntimeException('Unexpected shape for take-home assignment brief');
        }

        $brief = [
            'title' => $this->cleanText($decoded['title'] ?? null, self::MAX_TITLE, 'Untitled assignment'),
            'objective' => $this->cleanText($decoded['objective'], self::MAX_OBJECTIVE),
            'context' => $this->cleanText($decoded['context'] ?? null, self::MAX_CONTEXT, ''),
            'deliverables' => $this->cleanList($decoded['deliverables']),
            'review_criteria' => $this->cleanList($decoded['review_criteria'] ?? []),
            'timebox' => $this->cleanText(
                $decoded['timebox'] ?? null,
                self::MAX_SHORT,
                '7 days, ~12-15 focused hours.'
            ),
            'ai_use_note' => $this->cleanText(
                $decoded['ai_use_note'] ?? null,
                self::MAX_SHORT,
                'You may use AI tools as a thinking partner. AI must not replace understanding — the work must be your own, and honest attribution is expected.'
            ),
        ];

        $submission = $decoded['submission'] ?? null;

        if (is_array($submission)) {
            $brief['submission'] = [
                'repo_url' => $this->cleanText($submission['repo_url'] ?? null, self::MAX_SHORT, ''),
                'reflection' => $this->cleanText($submission['reflection'] ?? null, self::MAX_SHORT, ''),
            ];
        }

        return $brief;
    }

    private function cleanText(mixed $value, int $max, string $fallback = ''): string
    {
        if (! is_string($value)) {
            return $fallback;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($value === '') {
            return $fallback;
        }

        return Str::limit($value, $max, '…');
    }

    private function cleanList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach (array_slice($value, 0, self::MAX_LIST_ITEMS) as $item) {
            if (! is_string($item)) {
                continue;
            }

            $item = $this->cleanText($item, self::MAX_ITEM);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function store(Evaluation $evaluation, array $brief): Assignment
    {
        $candidate = $evaluation->candidate;

        return Assignment::create([
            'candidate_id' => $candidate->id,
            'evaluation_id' => $evaluation->id,
            'token' => Str::random(48),
            'brief' => $brief,
            'ai_model_used' => $this->client->get_model_used(),
            'status' => AssignmentStatus::Generated,
            'generated_at' => now(),
        ]);
    }
}
