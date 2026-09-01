<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use App\Models\HrUser;
use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateEvaluationTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_candidate_show_page_renders(): void
    {
        $candidate = Candidate::factory()->create();

        $response = $this->get("/candidates/{$candidate->id}");

        $response->assertStatus(200);
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
}
