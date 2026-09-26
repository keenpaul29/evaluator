<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Repository;
use App\Services\RepositoryAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RepositoryAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_builds_full_repository_analysis(): void
    {
        [$repository] = $this->repositoryWithGithubFake([
            'commits' => [
                $this->commit('2025-11-01T10:00:00Z', 'Add game movement system'),
                $this->commit('2025-11-02T10:00:00Z', 'Fix spawn collision bug'),
                $this->commit('2025-11-03T10:00:00Z', 'Refactor service layer for tests'),
                $this->commit('2025-11-04T10:00:00Z', 'Document installation steps'),
            ],
        ]);

        $analysis = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertSame($repository->id, $analysis->repository_id);
        $this->assertSame(7, $analysis->total_files_analyzed);
        $this->assertSame(true, $analysis->has_readme);
        $this->assertSame(true, $analysis->has_tests);
        $this->assertSame(true, $analysis->has_ci_config);
        $this->assertSame(true, $analysis->has_documentation);
        $this->assertEquals(['PHP' => 1200, 'JavaScript' => 300], $analysis->primary_languages);
        $this->assertEquals(10.0, $analysis->commit_frequency_score);
        $this->assertEquals(10.0, $analysis->avg_commit_quality_score);
        $this->assertContains('MVC', $analysis->architectural_patterns);
        $this->assertContains('Service Layer', $analysis->architectural_patterns);
        $this->assertContains('Migration-based schema', $analysis->architectural_patterns);
        $this->assertArrayHasKey('php', $analysis->dependencies_analysis);
        $this->assertSame(100, $analysis->authenticity_score);
        $this->assertContains('Organic commit history detected.', $analysis->authenticity_flags);

        $repository->refresh();
        $this->assertNotNull($analysis->analyzed_at);
    }

    public function test_analyze_handles_empty_commit_history(): void
    {
        [$repository] = $this->repositoryWithGithubFake(['commits' => []]);

        $analysis = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertEquals(0.0, $analysis->commit_frequency_score);
        $this->assertEquals(0.0, $analysis->avg_commit_quality_score);
        $this->assertSame(50, $analysis->authenticity_score);
        $this->assertContains('No commit history available', $analysis->authenticity_flags);
    }

    public function test_analyze_scores_commit_quality_by_message_length(): void
    {
        [$repository] = $this->repositoryWithGithubFake([
            'commits' => [
                $this->commit('2025-11-01T10:00:00Z', 'This is a well formatted commit message'),
                $this->commit('2025-11-01T11:00:00Z', 'fix'),
            ],
        ]);

        $analysis = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertEquals(5.0, $analysis->avg_commit_quality_score);
        $this->assertEquals(5.0, $analysis->commit_frequency_score);
    }

    public function test_analyze_updates_existing_analysis_instead_of_duplicating(): void
    {
        [$repository] = $this->repositoryWithGithubFake();

        $first = app(RepositoryAnalyzer::class)->analyze($repository);
        $second = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('repository_analyses', 1);
    }

    public function test_analyze_counts_lines_across_code_files(): void
    {
        [$repository] = $this->repositoryWithGithubFake();

        $analysis = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertGreaterThan(10, $analysis->total_lines_analyzed);
        $this->assertLessThan(60, $analysis->total_lines_analyzed);
    }

    public function test_analyze_persists_selected_artifact_paths_and_commit_samples(): void
    {
        [$repository] = $this->repositoryWithGithubFake();

        $analysis = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertIsArray($analysis->artifact_file_paths);
        $this->assertNotEmpty($analysis->artifact_file_paths);
        $this->assertContains('README.md', $analysis->artifact_file_paths);
        $this->assertContains('composer.json', $analysis->artifact_file_paths);
        $this->assertContains('tests/Feature/GameTest.php', $analysis->artifact_file_paths);

        $this->assertIsArray($analysis->artifact_excerpts);
        $this->assertArrayHasKey('README.md', $analysis->artifact_excerpts);
        $this->assertStringContainsString('# Candidate Repo', $analysis->artifact_excerpts['README.md']);
        $this->assertArrayHasKey('app/Http/Controllers/GameController.php', $analysis->artifact_excerpts);

        $this->assertIsArray($analysis->commit_samples);
        $this->assertCount(2, $analysis->commit_samples);
        $this->assertArrayHasKey('sha', $analysis->commit_samples[0]);
        $this->assertArrayHasKey('author_name', $analysis->commit_samples[0]);
        $this->assertArrayHasKey('author_date', $analysis->commit_samples[0]);
        $this->assertArrayHasKey('subject', $analysis->commit_samples[0]);
        $this->assertSame('Add game movement system', $analysis->commit_samples[0]['subject']);
    }

    public function test_analyze_limits_commit_samples_and_artifact_paths(): void
    {
        $commits = [];
        for ($i = 0; $i < 40; $i++) {
            $commits[] = $this->commit('2025-11-'.str_pad((string) (($i % 27) + 1), 2, '0', STR_PAD_LEFT).'T10:00:00Z', "Commit {$i} with a descriptive subject");
        }

        [$repository] = $this->repositoryWithGithubFake(['commits' => $commits]);

        $analysis = app(RepositoryAnalyzer::class)->analyze($repository);

        $this->assertCount(25, $analysis->commit_samples);
        $this->assertLessThanOrEqual(10, count($analysis->artifact_file_paths));
    }

    private function repositoryWithGithubFake(array $overrides = []): array
    {
        $candidate = Candidate::factory()->create(['github_username' => 'candidate']);
        $repository = Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 123,
            'name' => 'rpg-app',
            'full_name' => 'candidate/rpg-app',
            'description' => 'Test repository',
            'html_url' => 'https://github.com/candidate/rpg-app',
            'default_branch' => 'main',
            'primary_language' => 'PHP',
            'stars_count' => 0,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'topics' => ['laravel'],
            'is_fork' => false,
        ]);

        $files = $this->codeFiles();

        $commits = $overrides['commits'] ?? [
            $this->commit('2025-11-01T10:00:00Z', 'Add game movement system'),
            $this->commit('2025-11-02T10:00:00Z', 'Fix spawn collision bug'),
        ];

        $tree = [
            ['path' => 'README.md', 'type' => 'blob'],
            ['path' => 'composer.json', 'type' => 'blob'],
            ['path' => '.github/workflows/ci.yml', 'type' => 'blob'],
            ['path' => 'app/Http/Controllers/GameController.php', 'type' => 'blob'],
            ['path' => 'app/Services/GameService.php', 'type' => 'blob'],
            ['path' => 'database/migrations/2026_01_01_create_games_table.php', 'type' => 'blob'],
            ['path' => 'tests/Feature/GameTest.php', 'type' => 'blob'],
            ['path' => 'docs/architecture.md', 'type' => 'blob'],
            ['path' => 'public/index.php', 'type' => 'blob'],
        ];

        Http::fake([
            'api.github.com/repos/candidate/rpg-app/git/trees/*' => Http::response(['tree' => $tree]),
            'api.github.com/repos/candidate/rpg-app/languages' => Http::response(['PHP' => 1200, 'JavaScript' => 300]),
            'api.github.com/repos/candidate/rpg-app/commits*' => Http::response($commits),
            'api.github.com/repos/candidate/rpg-app/contents/*' => function ($request) use ($files) {
                $path = $this->contentsPath($request->url());

                return ['download_url' => "https://raw.test/{$path}", 'size' => strlen($files[$path] ?? '')];
            },
            'https://raw.test/*' => function ($request) use ($files) {
                $path = $this->contentsPath($request->url());

                return Http::response($files[$path] ?? '', 200);
            },
        ]);

        return [$repository, $candidate];
    }

    private function codeFiles(): array
    {
        return [
            'app/Http/Controllers/GameController.php' => "<?php\n\nnamespace App;\n\nclass GameController\n{\n    public function index()\n    {\n        return view('game');\n    }\n}\n",
            'app/Services/GameService.php' => "<?php\n\nnamespace App;\n\nclass GameService\n{\n    public function move()\n    {\n        return true;\n    }\n}\n",
            'database/migrations/2026_01_01_create_games_table.php' => "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\n\nclass CreateGamesTable extends Migration\n{\n    public function up()\n    {\n        // schema\n    }\n}\n",
            'tests/Feature/GameTest.php' => "<?php\n\nnamespace Tests;\n\nclass GameTest\n{\n    public function testGame()\n    {\n        self::assertTrue(1 === 1);\n    }\n}\n",
            'composer.json' => '{"name":"candidate/rpg-app"}',
            '.github/workflows/ci.yml' => "on: [push]\njobs:\n  ci:\n    runs-on: ubuntu-latest\n",
            'public/index.php' => "<?php\n\nreturn 1;\n",
            'README.md' => "# Candidate Repo\n\nInstallation and usage instructions.\n",
            'docs/architecture.md' => "# Architecture\n\nDomain-driven design notes.\n",
        ];
    }

    private function commit(string $date, string $message): array
    {
        return [
            'sha' => sha1($date.$message),
            'commit' => [
                'author' => ['date' => $date],
                'message' => $message,
            ],
        ];
    }

    private function contentsPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path ?? '')));

        if (($key = array_search('contents', $segments)) !== false) {
            return implode('/', array_slice($segments, $key + 1));
        }

        return ltrim($path ?? '', '/');
    }
}
