<?php

namespace Tests\Unit;

use App\Models\Candidate;
use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use App\Services\Evaluation\EvaluationPromptBuilder;
use Tests\TestCase;

class EvaluationPromptBuilderTest extends TestCase
{
    public function test_build_injects_commit_subjects_and_excerpts(): void
    {
        $candidate = Candidate::factory()->create(['github_username' => 'janedoe']);
        $repo = Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 1,
            'name' => 'rpg-app',
            'full_name' => 'janedoe/rpg-app',
            'html_url' => 'https://github.com/janedoe/rpg-app',
            'default_branch' => 'main',
            'topics' => ['laravel'],
            'is_fork' => false,
        ]);

        $analysis = new RepositoryAnalysis([
            'repository_id' => $repo->id,
            'total_files_analyzed' => 10,
            'total_lines_analyzed' => 500,
            'primary_languages' => ['PHP' => 500],
            'has_readme' => true,
            'has_tests' => true,
            'has_ci_config' => true,
            'has_documentation' => true,
            'commit_frequency_score' => 8.0,
            'avg_commit_quality_score' => 8.0,
            'code_complexity_estimate' => 'medium',
            'architectural_patterns' => ['MVC'],
        ]);

        $analysis->setRelation('repository', $repo);
        $analysis->commit_samples = [
            ['sha' => 'abc123def456', 'author_date' => '2025-11-01', 'subject' => 'Add game movement system'],
            ['sha' => 'def789abc012', 'author_date' => '2025-11-02', 'subject' => 'Fix spawn collision bug'],
        ];
        $analysis->artifact_excerpts = [
            'README.md' => "# Candidate Repo\n\nInstructions.",
            'app/Services/GameService.php' => "<?php\n\nclass GameService\n{\n}\n",
        ];

        $prompt = $this->buildPrompt($candidate, [$analysis]);

        $this->assertStringContainsString('# Candidate Repo', $prompt);
        $this->assertStringContainsString('## FILE app/Services/GameService.php', $prompt);
        $this->assertStringContainsString('- COMMIT abc123d | 2025-11-01 | Add game movement system', $prompt);
        $this->assertStringContainsString('CITATION REQUIREMENTS', $prompt);
        $this->assertStringContainsString('"insufficient": true', $prompt);
        $this->assertStringContainsString('score each 0-10', $prompt);
    }

    public function test_build_caps_oversized_artifact_content(): void
    {
        $candidate = Candidate::factory()->create(['github_username' => 'janedoe']);
        $repo = Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 2,
            'name' => 'big-repo',
            'full_name' => 'janedoe/big-repo',
            'html_url' => 'https://github.com/janedoe/big-repo',
            'default_branch' => 'main',
            'topics' => [],
            'is_fork' => false,
        ]);

        $analysis = new RepositoryAnalysis([
            'repository_id' => $repo->id,
            'total_files_analyzed' => 2,
            'total_lines_analyzed' => 1000000,
            'primary_languages' => ['PHP' => 999999],
        ]);

        $analysis->setRelation('repository', $repo);
        $analysis->commit_samples = [];
        $analysis->artifact_excerpts = [
            'huge/One.php' => str_repeat('a', 90000),
            'huge/Two.php' => str_repeat('b', 90000),
        ];

        $prompt = $this->buildPrompt($candidate, [$analysis]);

        $this->assertLessThan(150 * 1024, strlen($prompt));
        $this->assertStringContainsString('[TRUNCATED: artifact content exceeds context limits]', $prompt);
    }

    public function test_build_returns_empty_with_no_artifacts(): void
    {
        $candidate = Candidate::factory()->create(['github_username' => 'janedoe']);
        $repo = Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 3,
            'name' => 'empty-repo',
            'full_name' => 'janedoe/empty-repo',
            'html_url' => 'https://github.com/janedoe/empty-repo',
            'default_branch' => 'main',
            'topics' => [],
            'is_fork' => false,
        ]);

        $analysis = new RepositoryAnalysis([
            'repository_id' => $repo->id,
            'total_files_analyzed' => 0,
            'total_lines_analyzed' => 0,
            'primary_languages' => [],
        ]);

        $analysis->setRelation('repository', $repo);

        $prompt = $this->buildPrompt($candidate, [$analysis]);

        $this->assertStringContainsString('EVALUATE THIS CANDIDATE', $prompt);
    }

    private function buildPrompt(Candidate $candidate, array $analyses): string
    {
        return app(EvaluationPromptBuilder::class)->build($candidate, $analyses);
    }
}
