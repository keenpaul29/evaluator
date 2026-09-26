<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Exceptions\AiEvaluationException;
use App\Jobs\EvaluateCandidateJob;
use App\Models\BatchJob;
use App\Models\Candidate;
use App\Models\CandidateComparison;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use App\Models\EvaluationProgress;
use App\Models\HrUser;
use App\Models\InterviewQuestion;
use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use App\Services\AiEvaluationService;
use App\Services\Evaluation\EvaluationStorage;
use App\Services\GithubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    public function test_candidate_show_page_renders_citation_evidence(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);
        EvaluationDimension::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
            'score' => 8.5,
            'justification' => 'Strong.',
            'evidence' => [
                ['file_path' => 'app/Services/GameService.php', 'commit_sha' => 'abc123def456', 'url' => 'https://github.com/janedoe/rpg-app'],
            ],
        ]);
        EvaluationDimension::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'communication',
            'score' => 5.0,
            'justification' => 'No signal.',
            'evidence' => [['insufficient' => true]],
        ]);

        $response = $this->get("/candidates/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertSee('app/Services/GameService.php');
        $response->assertSee('abc123d');
        $response->assertSee('insufficient evidence: no collected signal for this value');
        $response->assertSee('provisional');
    }

    public function test_candidate_can_be_shortlisted(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

        $response = $this->post("/candidates/{$candidate->id}/shortlist");

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Shortlisted, $candidate->status);
    }

    public function test_candidate_can_be_rejected(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

        $response = $this->post("/candidates/{$candidate->id}/reject");

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::Rejected, $candidate->status);
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

    public function test_self_apply_fails_and_does_not_dispatch_evaluation_when_repos_fail_to_sync(): void
    {
        Http::fake([
            'api.github.com/repos/selfuser/invalidrepo' => Http::response([], 404),
        ]);

        $response = $this->post('/apply', [
            'name' => 'Failed Apply Candidate',
            'email' => 'failed@example.com',
            'github_username' => 'selfuser',
            'repo_urls' => ['https://github.com/selfuser/invalidrepo'],
        ]);

        $response->assertSessionHasErrors(['repo_urls']);
        $this->assertDatabaseMissing('candidates', [
            'email' => 'failed@example.com',
        ]);
        Bus::assertNotDispatched(EvaluateCandidateJob::class);
    }

    public function test_hr_candidate_creation_does_not_dispatch_evaluation_when_provided_repo_urls_fail_to_sync(): void
    {
        HrUser::factory()->create();

        Http::fake([
            'api.github.com/repos/octo/invalid' => Http::response([], 404),
        ]);

        $response = $this->post('/candidates', [
            'name' => 'Failed Sync Candidate',
            'email' => 'failsync@example.com',
            'github_username' => 'octo',
            'repo_urls' => ['https://github.com/octo/invalid'],
        ]);

        $candidate = Candidate::where('email', 'failsync@example.com')->first();
        $this->assertNotNull($candidate);
        $response->assertRedirect(route('candidates.show', $candidate));
        $response->assertSessionHas('error');
        Bus::assertNotDispatched(EvaluateCandidateJob::class);
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
            'api.openai.com/v1/chat/completions' => function ($request) {
                $prompt = data_get($request->data(), 'messages.1.content') ?? '';

                if (str_contains($prompt, 'You verify whether cited repository artifacts')) {
                    return Http::response([
                        'choices' => [
                            ['message' => ['content' => json_encode(['supported' => ['app/Services/GameService.php']])]],
                        ],
                    ]);
                }

                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode($this->aiEvaluationPayload()),
                            ],
                        ],
                    ],
                ]);
            },
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
        $this->assertEquals(CandidateStatus::Evaluated, $candidate->fresh()->status);
    }

    public function test_evaluation_dimensions_are_stored(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

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
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Analyzing]);

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
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

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

    public function test_candidate_status_enum_values(): void
    {
        $this->assertEquals('submitted', CandidateStatus::Submitted->value);
        $this->assertEquals('analyzing', CandidateStatus::Analyzing->value);
        $this->assertEquals('evaluated', CandidateStatus::Evaluated->value);
        $this->assertEquals('shortlisted', CandidateStatus::Shortlisted->value);
        $this->assertEquals('rejected', CandidateStatus::Rejected->value);
    }

    public function test_candidate_status_enum_labels(): void
    {
        $this->assertEquals('Submitted', CandidateStatus::Submitted->label());
        $this->assertEquals('Evaluated', CandidateStatus::Evaluated->label());
    }

    public function test_ai_validation_rejects_invalid_score(): void
    {
        $this->expectException(AiEvaluationException::class);

        $storage = new EvaluationStorage;
        $candidate = Candidate::factory()->create();

        $storage->store($candidate, [
            'overall_score' => 15,
            'verdict' => 'hire',
            'dimensions' => [],
        ], 'gemini-1.5-flash');
    }

    public function test_ai_validation_rejects_invalid_verdict(): void
    {
        $this->expectException(AiEvaluationException::class);

        $storage = new EvaluationStorage;
        $candidate = Candidate::factory()->create();

        $storage->store($candidate, [
            'overall_score' => 7.5,
            'verdict' => 'invalid_verdict',
            'dimensions' => [],
        ], 'gemini-1.5-flash');
    }

    public function test_ai_validation_rejects_wrong_dimension_count(): void
    {
        $this->expectException(AiEvaluationException::class);

        $storage = new EvaluationStorage;
        $candidate = Candidate::factory()->create();

        $storage->store($candidate, [
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'dimensions' => [
                ['dimension' => 'code_quality', 'score' => 8.0],
            ],
        ], 'gemini-1.5-flash');
    }

    public function test_comparison_can_be_created(): void
    {
        $candidates = Candidate::factory()->count(3)->create();

        $response = $this->post('/comparisons', [
            'name' => 'Test Comparison',
            'candidate_ids' => $candidates->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('candidate_comparisons', [
            'name' => 'Test Comparison',
        ]);

        $comparison = CandidateComparison::first();
        $this->assertEquals(3, $comparison->candidates->count());
    }

    public function test_comparison_page_renders(): void
    {
        $response = $this->get('/comparisons');

        $response->assertStatus(200);
    }

    public function test_batch_page_renders(): void
    {
        $response = $this->get('/batches');

        $response->assertStatus(200);
    }

    public function test_batch_create_page_renders(): void
    {
        $response = $this->get('/batches/create');

        $response->assertStatus(200);
    }

    public function test_evaluation_progress_is_created(): void
    {
        $candidate = Candidate::factory()->create();

        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid(),
            'candidate_id' => $candidate->id,
            'status' => 'queued',
            'current_step' => 'queued',
            'steps_total' => 3,
        ]);

        $this->assertDatabaseHas('evaluation_progress', [
            'candidate_id' => $candidate->id,
            'status' => 'queued',
        ]);

        $progress->updateProgress('analyzing', 1, 'analyzing');
        $this->assertEquals('analyzing', $progress->fresh()->status);
        $this->assertEquals(1, $progress->fresh()->steps_completed);
    }

    public function test_interview_question_model(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $question = InterviewQuestion::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
            'question' => 'How do you handle error handling?',
            'repo_reference' => 'test/repo',
            'file_reference' => 'src/App.php',
            'why_ask' => 'Low score on code quality',
            'generated_at' => now(),
        ]);

        $this->assertDatabaseHas('interview_questions', [
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
        ]);

        $this->assertEquals($evaluation->id, $question->evaluation->id);
    }

    public function test_batch_job_model(): void
    {
        $batch = BatchJob::create([
            'name' => 'Test Batch',
            'total_candidates' => 5,
            'status' => 'processing',
        ]);

        $this->assertDatabaseHas('batch_jobs', [
            'name' => 'Test Batch',
            'status' => 'processing',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $batch->incrementProcessed();
        }

        $this->assertEquals('complete', $batch->fresh()->status);
    }

    public function test_batch_job_increment_counts(): void
    {
        $batch = BatchJob::create([
            'name' => 'Test Batch',
            'total_candidates' => 3,
            'status' => 'processing',
        ]);

        $batch->incrementProcessed();
        $this->assertEquals(1, $batch->fresh()->processed_count);

        $batch->incrementFailed();
        $this->assertEquals(1, $batch->fresh()->failed_count);
    }

    public function test_candidate_can_be_assigned_to_batch(): void
    {
        $batch = BatchJob::create([
            'name' => 'Test Batch',
            'total_candidates' => 1,
            'status' => 'processing',
        ]);

        $candidate = Candidate::factory()->create([
            'batch_id' => $batch->id,
        ]);

        $this->assertEquals($batch->id, $candidate->batch_id);
    }

    public function test_evaluation_progress_mark_complete(): void
    {
        $candidate = Candidate::factory()->create();
        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid(),
            'candidate_id' => $candidate->id,
            'status' => 'analyzing',
            'current_step' => 'ai_evaluation',
            'steps_total' => 3,
            'steps_completed' => 2,
        ]);

        $progress->markComplete();
        $this->assertEquals('complete', $progress->fresh()->status);
        $this->assertEquals('done', $progress->fresh()->current_step);
        $this->assertEquals(100, $progress->fresh()->progress_percent);
    }

    public function test_evaluation_progress_mark_failed(): void
    {
        $candidate = Candidate::factory()->create();
        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid(),
            'candidate_id' => $candidate->id,
            'status' => 'analyzing',
            'current_step' => 'fetching_repos',
            'steps_total' => 3,
        ]);

        $progress->markFailed('API error');
        $this->assertEquals('failed', $progress->fresh()->status);
        $this->assertStringContainsString('API error', $progress->fresh()->error_message);
    }

    public function test_interview_questions_attached_to_evaluation(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        InterviewQuestion::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
            'question' => 'Explain your approach to error handling.',
            'repo_reference' => 'test/repo',
            'file_reference' => 'src/App.php',
            'why_ask' => 'Low score on code quality',
            'generated_at' => now(),
        ]);

        InterviewQuestion::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'testing',
            'question' => 'How do you structure your tests?',
            'repo_reference' => 'test/repo',
            'file_reference' => 'tests/Feature/ExampleTest.php',
            'why_ask' => 'Missing test coverage',
            'generated_at' => now(),
        ]);

        $this->assertEquals(2, $evaluation->interviewQuestions->count());
    }

    public function test_comparison_can_be_deleted(): void
    {
        $candidates = Candidate::factory()->count(2)->create();

        $comparison = CandidateComparison::create(['name' => 'Test Delete']);
        $comparison->candidates()->attach($candidates->pluck('id')->toArray());

        $comparison->delete();

        $this->assertDatabaseMissing('candidate_comparisons', ['name' => 'Test Delete']);
    }

    public function test_dashboard_displays_stats(): void
    {
        Candidate::factory()->count(2)->create(['status' => CandidateStatus::Evaluated]);
        Candidate::factory()->create(['status' => CandidateStatus::Shortlisted]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_candidate_show_page_with_full_evaluation(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 8.5,
            'verdict' => 'strong_hire',
            'narrative_summary' => 'Excellent candidate.',
            'strengths' => ['Code quality', 'Architecture'],
            'concerns' => ['Needs more open source'],
            'interview_focus_areas' => ['Deep dive on system design'],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        EvaluationDimension::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
            'score' => 9.0,
            'justification' => 'Excellent code quality.',
            'evidence' => ['Clean architecture'],
            'weight' => 1.0,
        ]);

        $response = $this->get("/candidates/{$candidate->id}");

        $response->assertStatus(200);
    }

    public function test_candidate_index_shows_status_labels(): void
    {
        Candidate::factory()->create(['status' => CandidateStatus::Submitted]);
        Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

        $response = $this->get('/candidates');

        $response->assertStatus(200);
        $response->assertSee(CandidateStatus::Submitted->label());
        $response->assertSee(CandidateStatus::Evaluated->label());
    }

    public function test_api_evaluation_status_for_nonexistent_candidate(): void
    {
        $response = $this->get('/api/evaluation-status/999');

        $response->assertStatus(404);
    }

    public function test_comparison_show_displays_candidates(): void
    {
        $candidates = Candidate::factory()->count(2)->create();

        $comparison = CandidateComparison::create(['name' => 'Display Test']);
        $comparison->candidates()->attach($candidates->pluck('id')->toArray());

        $response = $this->get("/comparisons/{$comparison->id}");

        $response->assertStatus(200);
    }

    public function test_batch_show_page_displays_candidates(): void
    {
        $batch = BatchJob::create([
            'name' => 'Show Test',
            'total_candidates' => 2,
            'status' => 'complete',
            'processed_count' => 2,
        ]);

        Candidate::factory()->count(2)->create(['batch_id' => $batch->id]);

        $response = $this->get("/batches/{$batch->id}");

        $response->assertStatus(200);
    }

    public function test_ai_validation_rejects_empty_dimensions(): void
    {
        $this->expectException(AiEvaluationException::class);

        $storage = new EvaluationStorage;
        $candidate = Candidate::factory()->create();

        $storage->store($candidate, [
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'dimensions' => [],
        ], 'gemini-1.5-flash');
    }

    public function test_ai_validation_verdict_score_mismatch_is_stored_verbatim(): void
    {
        $candidate = Candidate::factory()->create();

        $storage = new EvaluationStorage;

        $evaluation = $storage->store($candidate, [
            'overall_score' => 5.0,
            'verdict' => 'strong_hire',
            'dimensions' => [
                ['dimension' => 'code_quality', 'score' => 8.0],
                ['dimension' => 'technical_judgment', 'score' => 8.0],
                ['dimension' => 'colvalues_alignment', 'score' => 8.0],
                ['dimension' => 'communication', 'score' => 8.0],
                ['dimension' => 'problem_complexity', 'score' => 8.0],
                ['dimension' => 'learning_trajectory', 'score' => 8.0],
                ['dimension' => 'technical_breadth', 'score' => 8.0],
            ],
        ], 'gemini-1.5-flash');

        $this->assertSame('strong_hire', $evaluation->verdict);
        $this->assertSame(5.0, (float) $evaluation->overall_score);
    }

    public function test_evaluation_can_be_created_with_all_fields(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);

        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 9.0,
            'verdict' => 'strong_hire',
            'narrative_summary' => 'Outstanding candidate with exceptional skills.',
            'strengths' => ['Clean code', 'System design', 'Leadership'],
            'concerns' => ['Could improve on documentation'],
            'interview_focus_areas' => ['System design deep dive', 'Code review simulation'],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
            'onboarding_friction' => 'low',
            'onboarding_friction_reason' => 'Already familiar with Laravel ecosystem.',
        ]);

        $this->assertDatabaseHas('evaluations', [
            'candidate_id' => $candidate->id,
            'overall_score' => 9.0,
            'verdict' => 'strong_hire',
            'onboarding_friction' => 'low',
        ]);
    }

    public function test_evaluation_dimensions_store_evidence_and_weight(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.5,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $dimension = EvaluationDimension::create([
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
            'score' => 8.5,
            'justification' => 'Strong code quality.',
            'evidence' => ['Clean commits', 'Good test coverage'],
            'weight' => 1.2,
        ]);

        $this->assertEquals(['Clean commits', 'Good test coverage'], $dimension->evidence);
        $this->assertEquals(1.2, $dimension->weight);
    }

    public function test_evaluation_progress_stores_error_message(): void
    {
        $candidate = Candidate::factory()->create();
        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid(),
            'candidate_id' => $candidate->id,
            'status' => 'analyzing',
            'current_step' => 'fetching_repos',
            'steps_total' => 3,
        ]);

        $progress->markFailed('GitHub API rate limit exceeded');

        $this->assertEquals('failed', $progress->fresh()->status);
        $this->assertStringContainsString('rate limit', $progress->fresh()->error_message);
    }

    public function test_candidate_status_enum_all_values(): void
    {
        $allStatuses = CandidateStatus::cases();
        $this->assertCount(5, $allStatuses);
        $this->assertContains(CandidateStatus::Submitted, $allStatuses);
        $this->assertContains(CandidateStatus::Analyzing, $allStatuses);
        $this->assertContains(CandidateStatus::Evaluated, $allStatuses);
        $this->assertContains(CandidateStatus::Shortlisted, $allStatuses);
        $this->assertContains(CandidateStatus::Rejected, $allStatuses);
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
            'artifact_file_paths' => [
                'README.md',
                'app/Services/GameService.php',
                'docs/architecture.md',
            ],
            'commit_samples' => [
                ['sha' => sha1('2025-11-01T10:00:00ZAdd game movement system'), 'author_date' => '2025-11-01T10:00:00Z', 'subject' => 'Add game movement system'],
                ['sha' => sha1('2025-11-02T11:00:00ZFix spawn collision bug'), 'author_date' => '2025-11-02T11:00:00Z', 'subject' => 'Fix spawn collision bug'],
            ],
            'artifact_excerpts' => [
                'README.md' => "# Candidate Repo\n\nInstructions.",
                'app/Services/GameService.php' => "<?php\n\nclass GameService\n{\n}\n",
            ],
            'analyzed_at' => now(),
        ];
    }

    private function aiEvaluationPayload(): array
    {
        $citation = [
            'file_path' => 'app/Services/GameService.php',
            'commit_sha' => sha1('2025-11-01T10:00:00ZAdd game movement system'),
            'url' => 'https://github.com/fallback/portfolio',
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
        ];
    }
}
