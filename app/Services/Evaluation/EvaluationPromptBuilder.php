<?php

namespace App\Services\Evaluation;

use App\Models\Candidate;
use App\Services\ColoredCowContext;

class EvaluationPromptBuilder
{
    public function build(Candidate $candidate, array $repositoryAnalyses): string
    {
        $context = ColoredCowContext::getFullContext();

        $repoSummaries = $this->formatRepositoryAnalyses($repositoryAnalyses);
        $valuesPrompt = $this->formatContextValues($context['values']);
        $techSummary = $this->formatTechStack($context['tech_stack']);

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

        CALCULATE ONBOARDING FRICTION:
        Compare the candidate's detected languages and architectural patterns against ColoredCow's Tech Stack.
        - "low": High overlap (e.g., strong PHP/Laravel/Vue experience).
        - "medium": Partial overlap or easily translatable skills (e.g., strong MVC in another language like Ruby on Rails).
        - "high": Completely disjointed stack (e.g., exclusively low-level C++ or legacy tools).

        OUTPUT FORMAT (JSON only, no markdown):
        {
            "overall_score": 7.5,
            "verdict": "hire",
            "onboarding_friction": "medium",
            "onboarding_friction_reason": "Explanation comparing their stack to ColoredCow's stack.",
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
            "interview_focus_areas": [
                "Instead of a generic topic, provide a highly specific technical interview question or a pair-programming refactoring challenge based on a complex or suboptimal architectural decision you found in their code. E.g., 'In your ecommerce-api repo, you placed payment logic in the CheckoutController. How would you refactor this to a Service class?'"
            ],
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

    private function formatRepositoryAnalyses(array $repositoryAnalyses): string
    {
        $repoSummaries = '';
        foreach ($repositoryAnalyses as $analysis) {
            $repoSummaries .= $this->formatSingleAnalysis($analysis);
        }

        return $repoSummaries;
    }

    private function formatSingleAnalysis(object $analysis): string
    {
        $repo = $analysis->repository;

        $topics = $repo->topics ? implode(', ', $repo->topics) : 'none';
        $isFork = $repo->is_fork ? 'Yes' : 'No';
        $languages = $analysis->primary_languages ? implode(', ', array_keys($analysis->primary_languages)) : 'unknown';
        $hasReadme = $analysis->has_readme ? 'Yes' : 'No';
        $hasTests = $analysis->has_tests ? 'Yes' : 'No';
        $hasCiConfig = $analysis->has_ci_config ? 'Yes' : 'No';
        $hasDocumentation = $analysis->has_documentation ? 'Yes' : 'No';
        $architecturalPatterns = $analysis->architectural_patterns ? implode(', ', $analysis->architectural_patterns) : 'none detected';

        return <<<EOT

            REPOSITORY: {$repo->full_name}
            - Description: {$repo->description}
            - Primary Language: {$repo->primary_language}
            - Stars: {$repo->stars_count}, Forks: {$repo->forks_count}
            - Topics: {$topics}
            - Is Fork: {$isFork}
            
            ANALYSIS:
            - Files analyzed: {$analysis->total_files_analyzed}
            - Lines of code: {$analysis->total_lines_analyzed}
            - Languages: {$languages}
            - Has README: {$hasReadme}
            - Has Tests: {$hasTests}
            - Has CI/CD: {$hasCiConfig}
            - Has Documentation: {$hasDocumentation}
            - Commit frequency score: {$analysis->commit_frequency_score}/10
            - Commit quality score: {$analysis->avg_commit_quality_score}/10
            - Code complexity: {$analysis->code_complexity_estimate}
            - Architectural patterns: {$architecturalPatterns}

            EOT;
    }

    private function formatContextValues(array $values): string
    {
        $valuesPrompt = '';
        foreach ($values as $name => $details) {
            $signals = implode("\n        ", array_map(fn ($s) => "- {$s}", $details['evaluation_signals']));
            $valuesPrompt .= "        {$name}: {$details['description']}\n        Evaluation signals:\n        {$signals}\n\n";
        }

        return $valuesPrompt;
    }

    private function formatTechStack(array $techStack): string
    {
        return 'Backend: '.implode(', ', $techStack['backend']['primary']).
            "\n        Frontend: ".implode(', ', $techStack['frontend']['primary']).
            "\n        Database: ".implode(', ', $techStack['database']['primary']).
            "\n        Cloud: ".$techStack['cloud']['provider'].
            "\n        Practices: ".implode(', ', $techStack['practices']);
    }
}
