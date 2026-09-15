<?php

namespace Tests\Feature;

use App\Jobs\EvaluateCandidateJob;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use App\Models\HrUser;
use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use App\Services\AiEvaluationService;
use App\Services\GithubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CandidateEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_candidate_list_page_renders(): void
    {
        $response = $this->get('/candidates');

        $response->assertStatus(200);
    }

    public function test_candidate_create_form_renders(): void
    {
        $response = $this->get('/candidates/create');

        $response->assertStatus(200);
    }

    public function test_candidate_can_be_created(): void
    {
        $hrUser = HrUser::factory()->create();

        $response = $this->post('/candidates', [
            'name' => 'Test Candidate',
            'email' => 'test@example.com',
            'github_username' => 'testuser',
            'notes' => 'Test notes',
        ]);

        $this->assertDatabaseHas('candidates', [
            'name' => 'Test Candidate',
            'email' => 'test@example.com',
            'github_username' => 'testuser',
            'status' => 'submitted',
            'submission_type' => 'hr_initiated',
        ]);

        Bus::assertDispatched(EvaluateCandidateJob::class);
    }

    public function test_candidate_show_page_renders(): void
    {
        $candidate = Candidate::factory()->create();

        $response = $this->get("/candidates/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertSee($candidate->name);
    }

    public function test_candidate_can_be_shortlisted(): void
    {
        $candidate = Candidate::factory()->create(['status' => 'evaluated']);

        $response = $this->post("/candidates/{$candidate->id}/shortlist");

        $candidate->refresh();
        $this->assertEquals('shortlisted', $candidate->status);
    }

    public function test_candidate_can_be_rejected(): void
    {
        $candidate = Candidate::factory()->create(['status' => 'evaluated']);

        $response = $this->post("/candidates/{$candidate->id}/reject");

        $candidate->refresh();
        $this->assertEquals('rejected', $candidate->status);
    }

    public function test_public_apply_form_renders(): void
    {
        $response = $this->get('/apply');

        $response->assertStatus(200);
    }

    public function test_candidate_can_self_apply(): void
    {
        Http::fake([
            'api.github.com/repos/selfuser/repo1' => Http::response($this->githubRepoPayload([
                'id' => 101,
                'name' => 'repo1',
                'full_name' => 'selfuser/repo1',
                'html_url' => 'https://github.com/selfuser/repo1',
            ])),
        ]);

        $response = $this->post('/apply', [
            'name' => 'Self Applied Candidate',
            'email' => 'self@example.com',
            'github_username' => 'selfuser',
            'repo_urls' => ['https://github.com/selfuser/repo1'],
        ]);

        $this->assertDatabaseHas('candidates', [
            'name' => 'Self Applied Candidate',
            'email' => 'self@example.com',
            'submission_type' => 'candidate_self_service',
        ]);

        $this->assertDatabaseHas('repositories', [
            'github_repo_id' => 101,
            'full_name' => 'selfuser/repo1',
        ]);

        Bus::assertDispatched(EvaluateCandidateJob::class);
    }

    public function test_hr_candidate_repo_urls_are_synced_without_fetching_entire_profile(): void
    {
        HrUser::factory()->create();

        Http::fake([
            'api.github.com/repos/octo/app' => Http::response($this->githubRepoPayload([
                'id' => 202,
                'name' => 'app',
                'full_name' => 'octo/app',
                'html_url' => 'https://github.com/octo/app',
                'language' => 'PHP',
            ])),
        ]);

        $this->post('/candidates', [
            'name' => 'Repository Focused Candidate',
            'email' => 'repo@example.com',
            'github_username' => 'octo',
            'repo_urls' => ['https://github.com/octo/app'],
        ]);

        $this->assertDatabaseHas('repositories', [
            'github_repo_id' => 202,
            'full_name' => 'octo/app',
            'primary_language' => 'PHP',
        ]);

        Http::assertSentCount(1);
    }

    public function test_github_service_accepts_git_suffixed_repository_urls(): void
    {
        Http::fake([
            'api.github.com/repos/octo/app' => Http::response($this->githubRepoPayload([
                'id' => 303,
                'name' => 'app',
                'full_name' => 'octo/app',
                'html_url' => 'https://github.com/octo/app',
            ])),
        ]);

        $candidate = Candidate::factory()->create();

        app(GithubService::class)->syncCandidateRepoUrls(['https://github.com/octo/app.git'], $candidate->id);

        $this->assertDatabaseHas('repositories', [
            'candidate_id' => $candidate->id,
            'github_repo_id' => 303,
            'full_name' => 'octo/app',
        ]);
    }

    public function test_ai_evaluation_falls_back_to_openai_when_gemini_fails(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'gemini-key',
            'services.openai.api_key' => 'openai-key',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'unavailable']], 503),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($this->aiEvaluationPayload()),
                        ],
                    ],
                ],
            ]),
        ]);

        $candidate = Candidate::factory()->create([
            'name' => 'Fallback Candidate',
            'github_username' => 'fallback',
        ]);
        $repository = Repository::create($this->repositoryAttributes($candidate->id));
        $analysis = RepositoryAnalysis::create($this->analysisAttributes($repository->id));

        app(AiEvaluationService::class)->evaluate($candidate, [$analysis->load('repository')]);

        $this->assertDatabaseHas('evaluations', [
            'candidate_id' => $candidate->id,
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'onboarding_friction' => 'medium',
            'ai_model_used' => 'gpt-4o-mini',
        ]);

        $this->assertDatabaseCount('evaluation_dimensions', 7);
        $this->assertEquals('evaluated', $candidate->fresh()->status);
    }

    public function test_evaluation_dimensions_are_stored(): void
    {
        $candidate = Candidate::factory()->create(['status' => 'evaluated']);

        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'narrative_summary' => 'Test narrative summary.',
            'strengths' => ['Strength 1'],
            'concerns' => ['Concern 1'],
            'interview_focus_areas' => ['Area 1'],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $dimensions = [
            ['dimension' => 'code_quality', 'score' => 8.0, 'justification' => 'Good code.', 'evidence' => ['Example 1']],
            ['dimension' => 'technical_judgment', 'score' => 7.0, 'justification' => 'Sound decisions.', 'evidence' => ['Example 2']],
            ['dimension' => 'colvalues_alignment', 'score' => 7.5, 'justification' => 'Values aligned.', 'evidence' => ['Example 3']],
            ['dimension' => 'communication', 'score' => 6.5, 'justification' => 'Clear communication.', 'evidence' => ['Example 4']],
            ['dimension' => 'problem_complexity', 'score' => 7.0, 'justification' => 'Complex problems.', 'evidence' => ['Example 5']],
            ['dimension' => 'learning_trajectory', 'score' => 8.0, 'justification' => 'Growing.', 'evidence' => ['Example 6']],
            ['dimension' => 'technical_breadth', 'score' => 7.5, 'justification' => 'Broad skills.', 'evidence' => ['Example 7']],
        ];

        foreach ($dimensions as $dim) {
            EvaluationDimension::create(array_merge($dim, [
                'evaluation_id' => $evaluation->id,
                'weight' => 1.0,
            ]));
        }

        $this->assertDatabaseCount('evaluation_dimensions', 7);
        $this->assertEquals(7, $evaluation->dimensions->count());
    }

    public function test_api_evaluation_status_endpoint(): void
    {
        $candidate = Candidate::factory()->create(['status' => 'analyzing']);

        $response = $this->get("/api/evaluation-status/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $candidate->id,
            'status' => 'analyzing',
            'has_evaluation' => false,
        ]);
    }

    public function test_api_evaluation_status_with_evaluation(): void
    {
        $candidate = Candidate::factory()->create(['status' => 'evaluated']);

        Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 8.0,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $response = $this->get("/api/evaluation-status/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'has_evaluation' => true,
            'overall_score' => 8.0,
            'verdict' => 'hire',
        ]);
    }

    private function githubRepoPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'repo',
            'full_name' => 'owner/repo',
            'description' => 'A test repository',
            'html_url' => 'https://github.com/owner/repo',
            'default_branch' => 'main',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-01-02T00:00:00Z',
            'topics' => ['laravel'],
            'fork' => false,
        ], $overrides);
    }

    private function repositoryAttributes(int $candidateId): array
    {
        return [
            'candidate_id' => $candidateId,
            'github_repo_id' => 404,
            'name' => 'portfolio',
            'full_name' => 'fallback/portfolio',
            'description' => 'Portfolio app',
            'html_url' => 'https://github.com/fallback/portfolio',
            'default_branch' => 'main',
            'primary_language' => 'PHP',
            'stars_count' => 2,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'topics' => ['laravel'],
            'is_fork' => false,
        ];
    }

    private function analysisAttributes(int $repositoryId): array
    {
        return [
            'repository_id' => $repositoryId,
            'total_files_analyzed' => 24,
            'total_lines_analyzed' => 1800,
            'primary_languages' => ['PHP' => 1400, 'JavaScript' => 400],
            'has_readme' => true,
            'has_tests' => true,
            'has_ci_config' => true,
            'has_documentation' => true,
            'commit_frequency_score' => 8.0,
            'avg_commit_quality_score' => 7.5,
            'code_complexity_estimate' => 'medium',
            'architectural_patterns' => ['MVC', 'Service Layer'],
            'dependencies_analysis' => ['php' => 'composer.json detected'],
            'authenticity_score' => 90,
            'authenticity_flags' => ['Organic commit history detected.'],
            'analyzed_at' => now(),
        ];
    }

    private function aiEvaluationPayload(): array
    {
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
            'evidence' => ['Repository structure and commit history support this score.'],
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
        ];
    }
}
