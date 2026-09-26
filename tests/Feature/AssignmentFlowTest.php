<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\CandidateStatus;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\HrUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssignmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_requires_generated_status(): void
    {
        $assignment = $this->makeAssignment(AssignmentStatus::Generated);
        $candidate = $assignment->candidate;

        $response = $this->post(route('candidates.assignment.dispatch', $candidate));

        $response->assertRedirect();
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'status' => AssignmentStatus::Dispatched,
        ]);

        $assignment->refresh();
        $this->assertNotNull($assignment->dispatched_at);
        $this->assertNotNull($assignment->due_at);
        $this->assertSame(now()->addDays(7)->toDateString(), $assignment->due_at->toDateString());
    }

    public function test_dispatch_is_idempotent_for_already_dispatched_assignment(): void
    {
        $assignment = $this->makeAssignment(AssignmentStatus::Dispatched);
        $candidate = $assignment->candidate;

        $response = $this->post(route('candidates.assignment.dispatch', $candidate));

        $response->assertRedirect();
        $this->assertSame(AssignmentStatus::Dispatched, $assignment->fresh()->status);
        $this->assertDatabaseCount('assignments', 1);
    }

    public function test_dispatch_requires_existing_assignment(): void
    {
        $candidate = Candidate::factory()->evaluated()->create();

        $response = $this->post(route('candidates.assignment.dispatch', $candidate));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_public_show_requires_dispatched_or_later_state(): void
    {
        $generated = $this->makeAssignment(AssignmentStatus::Generated);

        $this->get(route('assignments.show', $generated->token))->assertNotFound();

        $dispatched = $this->makeAssignment(AssignmentStatus::Dispatched);

        $this->get(route('assignments.show', $dispatched->token))
            ->assertOk()
            ->assertSee($dispatched->brief['objective']);
    }

    public function test_candidate_can_submit_dispatched_assignment_with_repo_and_reflection(): void
    {
        $assignment = $this->makeAssignment(AssignmentStatus::Dispatched);

        $response = $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/candidate/take-home',
            'reflection' => 'I planned the data model first, shipped in small commits, and wrote tests along the way. The multi-tenancy piece was harder than expected.',
        ]);

        $response->assertSessionHas('success');

        $assignment->refresh();

        $this->assertSame(AssignmentStatus::Submitted, $assignment->status);
        $this->assertSame('https://github.com/candidate/take-home', $assignment->submitted_repo_url);
        $this->assertStringContainsString('multi-tenancy', $assignment->reflection);
        $this->assertNotNull($assignment->submitted_at);
    }

    public function test_submission_rejects_invalid_repo_url(): void
    {
        $assignment = $this->makeAssignment(AssignmentStatus::Dispatched);

        $response = $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'not-a-url',
            'reflection' => 'A perfectly fine reflection of at least fifty characters long.',
        ]);

        $response->assertSessionHasErrors('submitted_repo_url');
        $this->assertSame(AssignmentStatus::Dispatched, $assignment->fresh()->status);
    }

    public function test_submission_rejects_short_reflection(): void
    {
        $assignment = $this->makeAssignment(AssignmentStatus::Dispatched);

        $response = $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/candidate/take-home',
            'reflection' => 'Short.',
        ]);

        $response->assertSessionHasErrors('reflection');
        $this->assertSame(AssignmentStatus::Dispatched, $assignment->fresh()->status);
    }

    public function test_malformed_token_returns_404(): void
    {
        $this->get(route('assignments.show', Str::random(48)))->assertNotFound();
    }

    public function test_candidate_show_page_renders_assignment_card(): void
    {
        $assignment = $this->makeAssignment(AssignmentStatus::Dispatched);
        $candidate = $assignment->candidate;

        $this->actingAs(HrUser::factory()->create())
            ->get(route('candidates.show', $candidate))
            ->assertOk()
            ->assertSee('Take-Home Assignment')
            ->assertSee($assignment->brief['title']);
    }

    private function makeAssignment(AssignmentStatus $status): Assignment
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Evaluated]);
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.6,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        return Assignment::create([
            'candidate_id' => $candidate->id,
            'evaluation_id' => $evaluation->id,
            'token' => Str::random(48),
            'status' => $status,
            'brief' => [
                'title' => 'Evolve a multi-tenant learning platform',
                'objective' => 'Build a small clone of a multi-tenant course authoring system.',
                'deliverables' => ['REST API', 'Isolation', 'Tests'],
            ],
            'generated_at' => now(),
        ]);
    }
}
