<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Jobs\EvaluateCandidateJob;
use App\Mail\EvaluationCompletedMail;
use App\Models\BatchJob;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use App\Models\HrUser;
use App\Services\EvaluationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EvaluationPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        HrUser::factory()->create();

        Mail::fake();

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-1.5-flash',
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.model' => 'gpt-4o-mini',
        ]);
    }

    public function test_full_evaluation_pipeline_runs_end_to_end(): void
    {
        $this->fakeGithub('janedoe');

        $response = $this->post('/candidates', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'github_username' => 'janedoe',
            'notes' => 'Referred by internal team.',
        ]);

        $candidate = Candidate::where('email', 'jane@example.com')->first();

        $response->assertRedirect(route('candidates.show', $candidate));
        $response->assertSessionHas('success');

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Evaluated, $candidate->status);

        $evaluation = $candidate->evaluation;
        $this->assertNotNull($evaluation);
        $this->assertEquals(7.5, $evaluation->overall_score);
        $this->assertEquals('hire', $evaluation->verdict);
        $this->assertEquals('medium', $evaluation->onboarding_friction);
        $this->assertEquals('gemini-1.5-flash', $evaluation->ai_model_used);
        $this->assertSame(7, $evaluation->dimensions()->count());

        $this->assertSame(1, $candidate->repositories()->count());
        $this->assertDatabaseHas('repository_analyses', [
            'repository_id' => $candidate->repositories()->first()->id,
            'has_readme' => true,
            'has_tests' => true,
            'has_ci_config' => true,
            'has_documentation' => true,
        ]);
        $this->assertNotNull($candidate->repositories()->first()->analyzed_at);

        $progress = $candidate->latestProgress;
        $this->assertNotNull($progress);
        $this->assertEquals('complete', $progress->status);
        $this->assertEquals(100, $progress->progress_percent);
        $this->assertEquals('done', $progress->current_step);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/users/janedoe/repos'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/git/trees/HEAD'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/languages'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/commits'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));

        $this->assertSame(2, $candidate->evaluation->interviewQuestions()->count());
        $this->assertDatabaseHas('interview_questions', [
            'evaluation_id' => $candidate->evaluation->id,
            'dimension' => 'code_quality',
        ]);
    }

    public function test_evaluation_notification_is_sent_on_completion(): void
    {
        $this->fakeGithub('janedoe');

        $this->post('/candidates', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'github_username' => 'janedoe',
        ]);

        $candidate = Candidate::where('email', 'jane@example.com')->first();

        Mail::assertSent(EvaluationCompletedMail::class, function (EvaluationCompletedMail $mail) use ($candidate) {
            return $mail->candidate->is($candidate)
                && $mail->hasTo(HrUser::first()->email);
        });
    }

    public function test_pipeline_marks_failed_and_keeps_candidate_submitted_when_no_repos_found(): void
    {
        $this->fakeGithub('janedoe', ['repos' => []]);

        $this->post('/candidates', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'github_username' => 'janedoe',
        ]);

        $candidate = Candidate::where('email', 'jane@example.com')->first();
        $candidate->refresh();

        $this->assertEquals(CandidateStatus::Submitted, $candidate->status);
        $this->assertNull($candidate->evaluation);

        $progress = $candidate->latestProgress;
        $this->assertEquals('failed', $progress->status);
        $this->assertStringContainsString('No repositories found', $progress->error_message);

        Mail::assertNothingSent();
    }

    public function test_job_failure_marks_progress_failed_and_resets_candidate(): void
    {
        $this->fakeGithub('janedoe', [], 'failure');

        $candidate = Candidate::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'github_username' => 'janedoe',
            'status' => CandidateStatus::Submitted,
        ]);
        $progress = EvaluationProgress::create([
            'candidate_id' => $candidate->id,
            'status' => 'queued',
            'current_step' => 'queued',
            'steps_total' => 3,
        ]);

        $job = new EvaluateCandidateJob($candidate->id, $progress->id);

        try {
            $job->handle(app(EvaluationOrchestrator::class));
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Submitted, $candidate->status);

        $progress->refresh();
        $this->assertEquals('failed', $progress->status);
        $this->assertStringContainsString('AI', $progress->error_message);

        Mail::assertNothingSent();
    }

    public function test_full_pipeline_for_batch_candidate(): void
    {
        $this->fakeGithub('batchuser');

        $batch = BatchJob::create([
            'name' => 'Pipeline Batch',
            'csv_filename' => 'batch.csv',
            'total_candidates' => 1,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $candidate = Candidate::factory()->create([
            'name' => 'Batch User',
            'email' => 'batch@example.com',
            'github_username' => 'batchuser',
            'status' => CandidateStatus::Submitted,
            'batch_id' => $batch->id,
            'submitted_by' => HrUser::first()->id,
        ]);

        $progress = EvaluationProgress::create([
            'candidate_id' => $candidate->id,
            'status' => 'queued',
            'current_step' => 'queued',
            'steps_total' => 3,
        ]);

        (new EvaluateCandidateJob($candidate->id, $progress->id))->handle(app(EvaluationOrchestrator::class));

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Evaluated, $candidate->status);
        $this->assertNotNull($candidate->evaluation);
        $this->assertSame(7, $candidate->evaluation->dimensions()->count());

        $progress->refresh();
        $this->assertEquals('complete', $progress->status);
        $this->assertEquals(100, $progress->progress_percent);

        $batch->refresh();
        $this->assertSame(1, $batch->processed_count);
        $this->assertSame('complete', $batch->status);

        Mail::assertSent(EvaluationCompletedMail::class);
    }

    public function test_batch_candidate_failure_increments_batch_failed_count(): void
    {
        $this->fakeGithub('batchuser', [], 'failure');

        $batch = BatchJob::create([
            'name' => 'Pipeline Batch',
            'csv_filename' => 'batch.csv',
            'total_candidates' => 1,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $candidate = Candidate::factory()->create([
            'name' => 'Failing Batch User',
            'email' => 'fail@example.com',
            'github_username' => 'batchuser',
            'status' => CandidateStatus::Submitted,
            'batch_id' => $batch->id,
        ]);

        $progress = EvaluationProgress::create([
            'candidate_id' => $candidate->id,
            'status' => 'queued',
            'current_step' => 'queued',
            'steps_total' => 3,
        ]);

        $job = new EvaluateCandidateJob($candidate->id, $progress->id);

        try {
            $job->handle(app(EvaluationOrchestrator::class));
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        $batch->refresh();
        $this->assertSame(1, $batch->failed_count);
        $this->assertSame('partial_failure', $batch->status);
    }

    private function fakeGithub(string $username, array $overrides = [], string $ai = 'success'): void
    {
        $repo = [
            'id' => 9001,
            'name' => 'rpg-app',
            'full_name' => $username.'/rpg-app',
            'description' => 'A candidate test repository',
            'html_url' => "https://github.com/{$username}/rpg-app",
            'default_branch' => 'main',
            'language' => 'PHP',
            'stargazers_count' => 3,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-12-01T00:00:00Z',
            'topics' => ['laravel'],
            'fork' => false,
        ];

        $repos = $overrides['repos'] ?? [$repo];

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

        $files = [
            'app/Http/Controllers/GameController.php' => "<?php\n\nnamespace App;\n\nclass GameController\n{\n    public function index()\n    {\n    }\n}\n",
            'app/Services/GameService.php' => "<?php\n\nclass GameService\n{\n}\n",
            'database/migrations/2026_01_01_create_games_table.php' => "<?php\n\nclass CreateGamesTable\n{\n}\n",
            'tests/Feature/GameTest.php' => "<?php\n\nclass GameTest\n{\n}\n",
            'composer.json' => '{"name":"candidate/rpg-app"}',
            '.github/workflows/ci.yml' => "on: [push]\njobs:\n  ci:\n    runs-on: ubuntu-latest\n",
            'public/index.php' => "<?php\n\nreturn 1;\n",
            'README.md' => "# Candidate Repo\n\nInstallation and usage instructions.\n",
            'docs/architecture.md' => "# Architecture\n\nDomain-driven design notes.\n",
        ];

        $commits = [
            $this->commit('2025-11-01T10:00:00Z', 'Add game movement system'),
            $this->commit('2025-11-02T11:00:00Z', 'Fix spawn collision bug'),
            $this->commit('2025-11-03T09:30:00Z', 'Refactor service layer for tests'),
            $this->commit('2025-11-04T14:00:00Z', 'Document installation steps'),
        ];

        $fakes = [
            "api.github.com/users/{$username}/repos*" => Http::response($repos, 200, [
                'X-RateLimit-Remaining' => '4999',
            ]),
            "api.github.com/repos/{$username}/rpg-app/git/trees/*" => Http::response(['tree' => $tree]),
            "api.github.com/repos/{$username}/rpg-app/languages" => Http::response(['PHP' => 1200, 'JavaScript' => 300]),
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

        Http::fake(array_merge($fakes, $this->aiFakes($ai)));
    }

    private function aiFakes(string $mode): array
    {
        if ($mode === 'failure') {
            return [
                'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'unavailable']], 503),
                'api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'unavailable']], 503),
            ];
        }

        return [
            'generativelanguage.googleapis.com/*' => function ($request) {
                $prompt = data_get($request->data(), 'contents.0.parts.0.text') ?? '';

                if (str_contains($prompt, 'You verify whether cited repository artifacts')) {
                    return Http::response([
                        'candidates' => [
                            ['content' => ['parts' => [['text' => json_encode(['supported' => ['app/Services/GameService.php']])]]]],
                        ],
                    ]);
                }

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => json_encode($this->aiEvaluationPayload())],
                                ],
                            ],
                        ],
                    ],
                ]);
            },
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

    private function aiEvaluationPayload(): array
    {
        $citation = [
            'file_path' => 'app/Services/GameService.php',
            'commit_sha' => sha1('2025-11-01T10:00:00ZAdd game movement system'),
            'url' => 'https://github.com/janedoe/rpg-app',
        ];

        $dimensions = collect([
            'code_quality',
            'technical_judgment',
            'colvalues_alignment',
            'communication',
            'problem_complexity',
            'learning_trajectory',
            'technical_breadth',
        ])->map(fn (string $dimension) => [
            'dimension' => $dimension,
            'score' => 7.5,
            'justification' => "Solid {$dimension} evidence.",
            'evidence' => [$citation],
        ])->all();

        return [
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'onboarding_friction' => 'medium',
            'onboarding_friction_reason' => 'Strong Laravel overlap with some areas to validate.',
            'dimensions' => $dimensions,
            'strengths' => ['Good test discipline'],
            'concerns' => ['Validate deployment experience'],
            'interview_focus_areas' => ['Refactor a controller-heavy Laravel workflow into a service.'],
            'narrative_summary' => 'A solid candidate with practical Laravel signals and enough breadth for onsite discussion.',
            'questions' => [
                [
                    'dimension' => 'code_quality',
                    'question' => 'How would you refactor GameController into services?',
                    'repo_reference' => 'janedoe/rpg-app',
                    'file_reference' => 'app/Http/Controllers/GameController.php',
                    'why_ask' => 'Score 7.5/10 — controller orchestration to validate.',
                ],
                [
                    'dimension' => 'technical_judgment',
                    'question' => 'Walk through your migration strategy trade-offs.',
                    'repo_reference' => 'janedoe/rpg-app',
                    'file_reference' => 'database/migrations/2026_01_01_create_games_table.php',
                    'why_ask' => 'Score 7.5/10 — schema decisions to probe.',
                ],
            ],
        ];
    }
}
