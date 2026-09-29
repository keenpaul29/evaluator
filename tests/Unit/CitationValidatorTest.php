<?php

namespace Tests\Unit;

use App\Exceptions\AiEvaluationException;
use App\Models\Candidate;
use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use App\Services\Evaluation\AiProviderClient;
use App\Services\Evaluation\CitationValidator;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitationValidatorTest extends TestCase
{
    use RefreshDatabase;

    private string $shaA;

    private string $shaB;

    private RepositoryAnalysis $analysis;

    protected function setUp(): void
    {
        parent::setUp();

        $candidate = Candidate::factory()->create(['github_username' => 'janedoe']);
        $repo = Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 10,
            'name' => 'rpg-app',
            'full_name' => 'janedoe/rpg-app',
            'html_url' => 'https://github.com/janedoe/rpg-app',
            'default_branch' => 'main',
            'topics' => ['laravel'],
            'is_fork' => false,
        ]);

        $this->shaA = sha1('commit-a');
        $this->shaB = sha1('commit-b');

        $this->analysis = RepositoryAnalysis::create([
            'repository_id' => $repo->id,
            'primary_languages' => ['PHP'],
            'artifact_file_paths' => [
                'README.md',
                'app/Services/GameService.php',
                'docs/architecture.md',
            ],
            'commit_samples' => [
                ['sha' => $this->shaA, 'author_date' => '2025-11-01T10:00:00Z', 'subject' => 'Add game movement system'],
                ['sha' => $this->shaB, 'author_date' => '2025-11-02T11:00:00Z', 'subject' => 'Fix spawn collision bug'],
            ],
            'artifact_excerpts' => [
                'README.md' => "# Candidate Repo\n\nInstructions.",
                'app/Services/GameService.php' => "<?php\n\nclass GameService\n{\n}\n",
            ],
            'analyzed_at' => now(),
        ]);

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-1.5-flash',
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.model' => 'gpt-4o-mini',
        ]);
    }

    private function baseDimensions(array $evidence): array
    {
        return [[
            'dimension' => 'code_quality',
            'score' => 8.0,
            'justification' => 'Clean service layer structure.',
            'evidence' => $evidence,
        ]];
    }

    public function test_string_evidence_is_flagged_insufficient(): void
    {
        $validator = new CitationValidator;

        $dimensions = $validator->validate($this->baseDimensions(['Repository structure is clean.']), [$this->analysis]);

        $this->assertSame([['insufficient' => true]], $dimensions[0]['evidence']);
    }

    public function test_insufficient_marker_is_preserved(): void
    {
        $validator = new CitationValidator;

        $dimensions = $validator->validate($this->baseDimensions([['insufficient' => true]]), [$this->analysis]);

        $this->assertSame([['insufficient' => true]], $dimensions[0]['evidence']);
    }

    public function test_fabricated_file_path_is_flagged_insufficient(): void
    {
        $validator = new CitationValidator;

        $dimensions = $validator->validate($this->baseDimensions([['file_path' => 'totally/made/up.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']]), [$this->analysis]);

        $this->assertSame([['insufficient' => true]], $dimensions[0]['evidence']);
    }

    public function test_leading_path_dots_and_case_are_normalized(): void
    {
        $validator = new CitationValidator($this->stubProvider(['app/Services/GameService.php']));

        $dimensions = $validator->validate($this->baseDimensions([['file_path' => './APP/SERVICES/GAMESERVICE.PHP', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']]), [$this->analysis]);

        $this->assertSame('app/Services/GameService.php', $dimensions[0]['evidence'][0]['file_path']);
    }

    public function test_directory_citation_matches_subtree(): void
    {
        $validator = new CitationValidator($this->stubProvider(['app/Services']));

        $dimensions = $validator->validate($this->baseDimensions([['file_path' => 'app/Services', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']]), [$this->analysis]);

        $this->assertSame('app/Services', $dimensions[0]['evidence'][0]['file_path']);
    }

    public function test_commit_sha_prefix_match_resolves(): void
    {
        $validator = new CitationValidator;

        $dimensions = $validator->validate($this->baseDimensions([['file_path' => null, 'commit_sha' => substr($this->shaA, 0, 7), 'url' => 'https://github.com/janedoe/rpg-app']]), [$this->analysis]);

        $this->assertCount(1, $dimensions[0]['evidence']);
    }

    public function test_unknown_commit_sha_is_flagged_insufficient(): void
    {
        $validator = new CitationValidator;

        $dimensions = $validator->validate($this->baseDimensions([['file_path' => 'README.md', 'commit_sha' => sha1('wont-be-found'), 'url' => 'https://github.com/janedoe/rpg-app']]), [$this->analysis]);

        $this->assertSame([['insufficient' => true]], $dimensions[0]['evidence']);
    }

    public function test_support_check_keeps_supported_file_and_flags_unsupported(): void
    {
        $validator = new CitationValidator($this->stubProvider(['app/Services/GameService.php']));

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertSame([['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']], $dimensions[0]['evidence']);

        $validator = new CitationValidator($this->stubProvider(['README.md']));

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertSame([['insufficient' => true]], $dimensions[0]['evidence']);
    }

    public function test_support_check_accepts_dimension_keyed_response(): void
    {
        $provider = $this->stubProvider([], false, fn () => json_encode([
            'code_quality' => ['app/Services/GameService.php'],
            'communication' => [],
        ]));

        $validator = new CitationValidator($provider);

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertSame([['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']], $dimensions[0]['evidence']);
    }

    public function test_support_check_accepts_fenced_json_response(): void
    {
        $provider = $this->stubProvider([], false, fn () => "```json\n".json_encode([
            'supported' => ['app/Services/GameService.php'],
        ])."\n```");

        $validator = new CitationValidator($provider);

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertSame([['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']], $dimensions[0]['evidence']);
    }

    public function test_support_check_accepts_concatenated_json_objects(): void
    {
        $provider = $this->stubProvider([], false, fn () => json_encode([
            'supported' => ['app/Services/GameService.php'],
        ])."\n".json_encode([
            'supported' => ['README.md'],
        ]));

        $validator = new CitationValidator($provider);

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertSame([['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app']], $dimensions[0]['evidence']);
    }

    public function test_concatenated_support_check_does_not_collapse_the_dimension(): void
    {
        $provider = $this->stubProvider([], false, fn () => '{"supported": ["app/Services/GameService.php"]}'."\n".'{"supported": ["README.md"]}');

        $validator = new CitationValidator($provider);

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
            ['file_path' => 'README.md', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertCount(2, $dimensions[0]['evidence']);
    }

    public function test_concatenated_objects_merge_lists_under_the_same_key(): void
    {
        $method = new \ReflectionMethod(CitationValidator::class, 'decodeJson');
        $method->setAccessible(true);

        $validator = new CitationValidator($this->stubProvider([]));

        $this->assertSame(
            ['supported' => ['a.php', 'b.php', 'c.php']],
            $method->invoke($validator, '{"supported":["a.php"]}'."\n".'{"supported":["b.php"]}'."\n".'{"supported":["c.php"]}')
        );
    }

    public function test_brace_scan_ignores_braces_inside_strings(): void
    {
        $method = new \ReflectionMethod(CitationValidator::class, 'decodeJson');
        $method->setAccessible(true);

        $validator = new CitationValidator($this->stubProvider([]));

        $this->assertSame(
            ['supported' => ['a}weird.php', 'b.php']],
            $method->invoke($validator, '{"supported":["a}weird.php"]}'."\n".'{"supported":["b.php"]}')
        );
    }

    public function test_support_check_failure_flags_insufficient_never_rejects(): void
    {
        $provider = $this->stubProvider([], true);

        $validator = new CitationValidator($provider);

        $dimensions = $validator->validate($this->baseDimensions([
            ['file_path' => 'app/Services/GameService.php', 'commit_sha' => $this->shaA, 'url' => 'https://github.com/janedoe/rpg-app'],
        ]), [$this->analysis]);

        $this->assertSame([['insufficient' => true]], $dimensions[0]['evidence']);
    }

    private function stubProvider(array $supported, bool $fail = false, ?Closure $responder = null): AiProviderClient
    {
        return new class($supported, $fail, $responder) extends AiProviderClient
        {
            public function __construct(private array $supported, private bool $fail, private ?Closure $responder) {}

            public function callWithFallback(string $prompt): string
            {
                if ($this->fail) {
                    throw new AiEvaluationException('all providers failed');
                }

                return $this->responder
                    ? ($this->responder)()
                    : json_encode(['supported' => $this->supported]);
            }
        };
    }
}
