<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEvaluationService
{
    private string $provider;
    private string $geminiKey;
    private string $geminiModel;
    private string $openaiKey;

    public function __construct()
    {
        $this->provider = config('services.ai.provider', 'gemini');
        $this->geminiKey = config('services.gemini.api_key', '');
        $this->geminiModel = config('services.gemini.model', 'gemini-1.5-flash');
        $this->openaiKey = config('services.openai.api_key', '');
    }

    public function evaluate(Candidate $candidate, array $repositoryAnalyses): Evaluation
    {
        $prompt = $this->buildPrompt($candidate, $repositoryAnalyses);

        $response = $this->provider === 'gemini'
            ? $this->callGemini($prompt)
            : $this->callOpenAI($prompt);

        $parsed = $this->parseResponse($response);

        return $this->storeEvaluation($candidate, $parsed);
    }

    private function buildPrompt(Candidate $candidate, array $repositoryAnalyses): string
    {
        $context = ColoredCowContext::getFullContext();

        $repoSummaries = '';
        foreach ($repositoryAnalyses as $analysis) {
            $repo = $analysis->repository;
            $repoSummaries .= <<<EOT

            REPOSITORY: {$repo->full_name}
            - Description: {$repo->description}
            - Primary Language: {$repo->primary_language}
            - Stars: {$repo->stars_count}, Forks: {$repo->forks_count}
            - Topics: {$repo->topics ? implode(', ', $repo->topics) : 'none'}
            - Is Fork: {$repo->is_fork ? 'Yes' : 'No'}
            
            ANALYSIS:
            - Files analyzed: {$analysis->total_files_analyzed}
            - Lines of code: {$analysis->total_lines_analyzed}
            - Languages: {$analysis->primary_languages ? implode(', ', array_keys($analysis->primary_languages)) : 'unknown'}
            - Has README: {$analysis->has_readme ? 'Yes' : 'No'}
            - Has Tests: {$analysis->has_tests ? 'Yes' : 'No'}
            - Has CI/CD: {$analysis->has_ci_config ? 'Yes' : 'No'}
            - Has Documentation: {$analysis->has_documentation ? 'Yes' : 'No'}
            - Commit frequency score: {$analysis->commit_frequency_score}/10
            - Commit quality score: {$analysis->avg_commit_quality_score}/10
            - Code complexity: {$analysis->code_complexity_estimate}
            - Architectural patterns: {$analysis->architectural_patterns ? implode(', ', $analysis->architectural_patterns) : 'none detected'}

            EOT;
        }

        $valuesPrompt = '';
        foreach ($context['values'] as $name => $details) {
            $signals = implode("\n        ", array_map(fn ($s) => "- {$s}", $details['evaluation_signals']));
            $valuesPrompt .= "        {$name}: {$details['description']}\n        Evaluation signals:\n        {$signals}\n\n";
        }

        $techStack = $context['tech_stack'];
        $techSummary = "Backend: " . implode(', ', $techStack['backend']['primary']) .
            "\n        Frontend: " . implode(', ', $techStack['frontend']['primary']) .
            "\n        Database: " . implode(', ', $techStack['database']['primary']) .
            "\n        Cloud: " . $techStack['cloud']['provider'] .
            "\n        Practices: " . implode(', ', $techStack['practices']);

        return <<<EOT
        You are evaluating a technical candidate for ColoredCow.

        COMPANY PROFILE:
        {$context['company']}

        HIRING PHILOSOPHY:
        {$context['hiring_philosophy']}

        CORE VALUES TO EVALUATE AGAINST:
        {$valuesPrompt}

        TECH STACK:
        {$techSummary}

        CANDIDATE PROFILE:
        - Name: {$candidate->name}
        - GitHub Username: {$candidate->github_username}
        - Repositories submitted: {$candidate->repositories->count()}

        REPOSITORY ANALYSES:
        {$repoSummaries}

        EVALUATE THIS CANDIDATE ACROSS 7 DIMENSIONS (score each 1-10):

        1. CODE_QUALITY — Readability, structure, naming conventions, DRY principles, error handling
        2. TECHNICAL_JUDGMENT — Architecture decisions, trade-off awareness, pragmatic choices
        3. COLVALUES_ALIGNMENT — Evidence of ColoredCow values: ownership, learning, collaboration, craftsmanship, generosity, creativity
        4. COMMUNICATION — README quality, commit message clarity, code comments, issue responses
        5. PROBLEM_COMPLEXITY — Sophistication of problems solved, system design thinking
        6. LEARNING_TRAJECTORY — Evidence of growth over time, responding to feedback, trying new technologies
        7. TECHNICAL_BREADTH — Range across frontend/backend/DevOps/database

        OUTPUT FORMAT (JSON only, no markdown):
        {
            "overall_score": 7.5,
            "verdict": "hire",
            "dimensions": [
                {
                    "dimension": "code_quality",
                    "score": 8.0,
                    "justification": "Detailed explanation of code quality assessment",
                    "evidence": ["Specific example from their code"]
                }
            ],
            "strengths": ["Strength 1", "Strength 2"],
            "concerns": ["Concern 1", "Concern 2"],
            "interview_focus_areas": ["Area to explore in interview"],
            "narrative_summary": "A comprehensive 2-3 paragraph assessment of this candidate, covering their technical abilities, alignment with ColoredCow values, and overall recommendation."
        }

        VERDICT OPTIONS:
        - strong_hire: Score >= 8.5, exceptional fit
        - hire: Score >= 7.0, solid candidate
        - maybe: Score >= 5.0, has potential but needs interview exploration
        - no_hire: Score >= 3.0, significant gaps
        - strong_no_hire: Score < 3.0, fundamental misalignment

        Be specific. Use evidence from the actual repositories. Be honest about gaps. Think about whether this person would thrive at a 25-person company that values ownership, craft, and long-term thinking.
        EOT;
    }

    private function callGemini(string $prompt): string
    {
        if (! $this->geminiKey) {
            throw new \RuntimeException('Gemini API key not configured');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->geminiKey}";

        $response = Http::timeout(120)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Gemini API call failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function callOpenAI(string $prompt): string
    {
        if (! $this->openaiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a technical hiring evaluator. Output valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('OpenAI API call failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
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
            throw new \RuntimeException('Invalid AI response format: ' . substr($response, 0, 500));
        }

        return $decoded;
    }

    private function storeEvaluation(Candidate $candidate, array $parsed): Evaluation
    {
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => $parsed['overall_score'],
            'verdict' => $parsed['verdict'],
            'narrative_summary' => $parsed['narrative_summary'] ?? '',
            'strengths' => $parsed['strengths'] ?? [],
            'concerns' => $parsed['concerns'] ?? [],
            'interview_focus_areas' => $parsed['interview_focus_areas'] ?? [],
            'ai_model_used' => $this->provider === 'gemini' ? $this->geminiModel : 'gpt-4o-mini',
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

        $candidate->update(['status' => 'evaluated']);

        return $evaluation;
    }
}
