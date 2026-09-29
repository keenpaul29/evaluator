<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\CandidateComparison;
use App\Models\HrUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvaluationJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        HrUser::factory()->create();

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-1.5-flash',
        ]);
    }

    public function test_full_candidate_lifecycle_from_apply_to_shortlist(): void
    {
        $this->fakeGithubAndAi('selfuser');

        $applyResponse = $this->post('/apply', [
            'name' => 'Self Service Candidate',
            'email' => 'candidate@example.com',
            'github_username' => 'selfuser',
            'repo_urls' => ['https://github.com/selfuser/rpg-app'],
            'notes' => 'Excited to join.',
        ]);

        $applyResponse->assertRedirect(route('apply.success'));
        $this->assertDatabaseHas('candidates', [
            'email' => 'candidate@example.com',
            'submission_type' => 'candidate_self_service',
        ]);

        $candidate = Candidate::where('email', 'candidate@example.com')->first();
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Evaluated, $candidate->status);

        $showResponse = $this->get("/candidates/{$candidate->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Self Service Candidate');
        $showResponse->assertSee('Hire');

        $statusResponse = $this->get("/api/evaluation-status/{$candidate->id}");
        $statusResponse->assertJson([
            'id' => $candidate->id,
            'status' => 'evaluated',
            'has_evaluation' => true,
            'overall_score' => 7.5,
            'verdict' => 'hire',
        ]);

        $commentResponse = $this->post("/candidates/{$candidate->id}/comment", [
            'comment' => 'Impressive architecture understanding. Move to final round.',
        ]);
        $commentResponse->assertSessionHas('success', 'Comment added.');
        $this->assertDatabaseHas('evaluation_comments', [
            'comment' => 'Impressive architecture understanding. Move to final round.',
        ]);

        $shortlistResponse = $this->post("/candidates/{$candidate->id}/shortlist");
        $shortlistResponse->assertSessionHas('success', 'Candidate shortlisted.');

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Shortlisted, $candidate->status);
    }

    public function test_shortlisted_candidate_can_join_a_comparison(): void
    {
        $this->fakeGithubAndAi('selfuser');

        $this->post('/apply', [
            'name' => 'Candidate Alpha',
            'email' => 'alpha@example.com',
            'github_username' => 'selfuser',
            'repo_urls' => ['https://github.com/selfuser/rpg-app'],
        ]);

        $alpha = Candidate::where('email', 'alpha@example.com')->first();
        $beta = Candidate::factory()->evaluated()->create(['name' => 'Candidate Beta']);

        $compareResponse = $this->post('/comparisons', [
            'name' => 'Frontend Final Round',
            'candidate_ids' => [$alpha->id, $beta->id],
        ]);
        $compareResponse->assertStatus(200);

        $comparison = CandidateComparison::where('name', 'Frontend Final Round')->first();
        $this->assertNotNull($comparison);
        $this->assertSame(2, $comparison->candidates()->count());

        $showResponse = $this->get("/comparisons/{$comparison->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Candidate Alpha');
        $showResponse->assertSee('Candidate Beta');
    }

    public function test_apply_with_invalid_repo_deletes_candidate_and_redirects_with_error(): void
    {
        Http::fake([
            'api.github.com/repos/selfuser/invalid' => Http::response([], 404),
        ]);

        $response = $this->post('/apply', [
            'name' => 'Broken Apply',
            'email' => 'broken@example.com',
            'github_username' => 'selfuser',
            'repo_urls' => ['https://github.com/selfuser/invalid'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['repo_urls']);
        $this->assertDatabaseMissing('candidates', ['email' => 'broken@example.com']);
    }

    public function test_success_page_renders_with_candidate_id(): void
    {
        $candidate = Candidate::factory()->create();

        $response = $this->withSession(['candidate_id' => $candidate->id])
            ->get('/apply/success');

        $response->assertStatus(200);
    }

    private function fakeGithubAndAi(string $username): void
    {
        $repo = [
            'id' => 7001,
            'name' => 'rpg-app',
            'full_name' => $username.'/rpg-app',
            'description' => 'Journey test repository',
            'html_url' => "https://github.com/{$username}/rpg-app",
            'default_branch' => 'main',
            'language' => 'PHP',
            'stargazers_count' => 1,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-12-01T00:00:00Z',
            'topics' => [],
            'fork' => false,
        ];

        $tree = [
            ['path' => 'README.md', 'type' => 'blob'],
            ['path' => 'composer.json', 'type' => 'blob'],
            ['path' => '.github/workflows/ci.yml', 'type' => 'blob'],
            ['path' => 'app/Http/Controllers/GameController.php', 'type' => 'blob'],
            ['path' => 'app/Services/GameService.php', 'type' => 'blob'],
            ['path' => 'database/migrations/2026_01_01_create_games_table.php', 'type' => 'blob'],
            ['path' => 'tests/Feature/GameTest.php', 'type' => 'blob'],
            ['path' => 'public/index.php', 'type' => 'blob'],
        ];

        $files = [
            'app/Http/Controllers/GameController.php' => "<?php\n\nclass GameController\n{\n    public function index()\n    {\n        return view('game');\n    }\n}\n",
            'app/Services/GameService.php' => "<?php\n\nclass GameService\n{\n}\n",
            'database/migrations/2026_01_01_create_games_table.php' => "<?php\n\nclass CreateGamesTable\n{\n}\n",
            'tests/Feature/GameTest.php' => "<?php\n\nclass GameTest\n{\n}\n",
            'composer.json' => '{"name":"candidate/rpg-app"}',
            '.github/workflows/ci.yml' => "on: [push]\njobs:\n  ci:\n    runs-on: ubuntu-latest\n",
            'public/index.php' => "<?php\n\nreturn 1;\n",
            'README.md' => "# Candidate Repo\n",
        ];

        $commits = [
            $this->commit('2025-11-01T10:00:00Z', 'Add game movement system'),
            $this->commit('2025-11-02T10:00:00Z', 'Fix spawn collision bug'),
        ];

        $fakes = [
            "api.github.com/users/{$username}/repos*" => Http::response([$repo]),
            "api.github.com/repos/{$username}/rpg-app" => Http::response($repo),
            "api.github.com/repos/{$username}/rpg-app/git/trees/*" => Http::response(['tree' => $tree]),
            "api.github.com/repos/{$username}/rpg-app/languages" => Http::response(['PHP' => 1200]),
            "api.github.com/repos/{$username}/rpg-app/commits*" => Http::response($commits),
            "api.github.com/repos/{$username}/rpg-app/contents/*" => function ($request) use ($files) {
                $path = $this->contentsPath($request->url());

                return ['download_url' => "https://raw.test/{$path}", 'size' => strlen($files[$path] ?? '')];
            },
            'https://raw.test/*' => function ($request) use ($files) {
                $path = $this->contentsPath($request->url());

                return Http::response($files[$path] ?? '', 200);
            },
        ];

        Http::fake(array_merge($fakes, [
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($this->aiEvaluationPayload())],
                            ],
                        ],
                    ],
                ],
            ]),
        ]));
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

    private function aiEvaluationPayload(): array
    {
        return [
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'onboarding_friction' => 'medium',
            'onboarding_friction_reason' => 'Partial stack overlap.',
            'dimensions' => collect([
                'code_quality', 'technical_judgment', 'colvalues_alignment',
                'communication', 'problem_complexity', 'learning_trajectory',
                'technical_breadth',
            ])->map(fn (string $dimension) => [
                'dimension' => $dimension,
                'score' => 7.5,
                'justification' => "Evidence for {$dimension}.",
                'evidence' => [],
            ])->all(),
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'narrative_summary' => 'A strong candidate.',
        ];
    }
}
