<?php

namespace Tests\Evals;

use App\Models\Candidate;
use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class LiveLlmTestCase extends TestCase
{
    use RefreshDatabase;

    protected const FIXTURE_SHA = 'abc123def4567890';

    protected function setUp(): void
    {
        parent::setUp();

        $enabled = filter_var(config('services.evals.enabled'), FILTER_VALIDATE_BOOL);

        if (! $enabled) {
            $this->markTestSkipped(
                'Live LLM evals are disabled. Enable with EVALS_ENABLED=true and an AI provider key, then run: EVALS_ENABLED=true php artisan test tests/evals'
            );
        }

        if (! config('services.gemini.api_key') && ! config('services.openai.api_key')) {
            $this->markTestSkipped('No AI provider API key configured for the live eval harness.');
        }
    }

    protected function fixtureCandidate(): Candidate
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Eval Fixture Candidate',
            'github_username' => 'eval-fixture',
        ]);

        $repository = Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 9090,
            'name' => 'rpg-app',
            'full_name' => 'eval-fixture/rpg-app',
            'description' => 'An RPG platformer engine with docs and tests.',
            'html_url' => 'https://github.com/eval-fixture/rpg-app',
            'default_branch' => 'main',
            'primary_language' => 'PHP',
            'stars_count' => 4,
            'forks_count' => 1,
            'open_issues_count' => 0,
            'topics' => ['game', 'php'],
            'is_fork' => false,
        ]);

        RepositoryAnalysis::create([
            'repository_id' => $repository->id,
            'total_files_analyzed' => 18,
            'total_lines_analyzed' => 2400,
            'primary_languages' => ['PHP' => 1800, 'JavaScript' => 600],
            'has_readme' => true,
            'has_tests' => true,
            'has_ci_config' => true,
            'has_documentation' => true,
            'commit_frequency_score' => 8.5,
            'avg_commit_quality_score' => 8.0,
            'code_complexity_estimate' => 'medium',
            'architectural_patterns' => ['MVC', 'Service Layer'],
            'dependencies_analysis' => ['php' => 'composer.json detected'],
            'authenticity_score' => 92,
            'authenticity_flags' => ['Organic commit history detected.'],
            'artifact_file_paths' => [
                'README.md',
                'app/Game/Physics/PhysicsEngine.php',
                'app/Game/Movement/MovementSystem.php',
                'tests/Unit/MovementSystemTest.php',
                '.github/workflows/ci.yml',
            ],
            'commit_samples' => [
                ['sha' => 'abc123def4567890', 'author_date' => '2025-11-01T10:00:00Z', 'subject' => 'Add physics collision resolution'],
                ['sha' => 'def456abc7890123', 'author_date' => '2025-11-02T14:30:00Z', 'subject' => 'Fix movement system edge case'],
            ],
            'artifact_excerpts' => [
                'README.md' => "# rpg-app\n\nAn RPG platformer engine. Includes a physics engine, movement system, and CI.",
                'app/Game/Physics/PhysicsEngine.php' => "<?php\n\nclass PhysicsEngine\n{\n    public function resolveCollisions(array \$bodies): void\n    {\n        // deterministic collision resolution with restitution\n    }\n}\n",
                'app/Game/Movement/MovementSystem.php' => "<?php\n\nclass MovementSystem\n{\n    public function move(Entity \$entity, float \$dt): void\n    {\n        \$entity->x += \$this->velocityX * \$dt;\n    }\n}\n",
                'tests/Unit/MovementSystemTest.php' => "<?php\n\nclass MovementSystemTest extends TestCase\n{\n    public function test_movement_scales_with_delta_time(): void\n    {\n        // assertion\n    }\n}\n",
            ],
            'analyzed_at' => now(),
        ]);

        return $candidate;
    }
}
