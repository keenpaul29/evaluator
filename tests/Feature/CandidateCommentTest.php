<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationComment;
use App\Models\HrUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_can_be_added_to_evaluated_candidate(): void
    {
        $hrUser = HrUser::factory()->create();

        $candidate = Candidate::factory()->evaluated()->create();
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

        $response = $this->post("/candidates/{$candidate->id}/comment", [
            'comment' => 'Strong recommendation, verify deployment experience in final round.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment added.');

        $this->assertDatabaseHas('evaluation_comments', [
            'evaluation_id' => $evaluation->id,
            'hr_user_id' => $hrUser->id,
            'comment' => 'Strong recommendation, verify deployment experience in final round.',
        ]);
    }

    public function test_comment_requires_comment_text(): void
    {
        HrUser::factory()->create();

        $candidate = Candidate::factory()->evaluated()->create();

        $response = $this->post("/candidates/{$candidate->id}/comment", [
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment']);
        $this->assertDatabaseCount('evaluation_comments', 0);
    }

    public function test_comment_without_evaluation_returns_error(): void
    {
        HrUser::factory()->create();

        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Submitted]);

        $response = $this->post("/candidates/{$candidate->id}/comment", [
            'comment' => 'Comment on candidate with no evaluation yet.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No evaluation exists for this candidate yet.');
        $this->assertDatabaseCount('evaluation_comments', 0);
    }

    public function test_comment_is_visible_on_candidate_show_page(): void
    {
        $hrUser = HrUser::factory()->create();

        $candidate = Candidate::factory()->evaluated()->create();
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 8.0,
            'verdict' => 'strong_hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        EvaluationComment::create([
            'evaluation_id' => $evaluation->id,
            'hr_user_id' => $hrUser->id,
            'comment' => 'Notable growth in last two projects.',
        ]);

        $response = $this->get("/candidates/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertSee('Notable growth in last two projects.');
    }
}
