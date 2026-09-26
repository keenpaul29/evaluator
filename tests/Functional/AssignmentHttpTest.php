<?php

namespace Tests\Functional;

use App\Enums\AssignmentStatus;
use App\Http\Middleware\CheckHrUser;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\HrUser;
use App\Models\InterviewQuestion;
use App\Services\AssignmentGenerationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssignmentHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_dispatch_requires_a_valid_method(): void
    {
        $assignment = $this->dispatchedAssignment();
        $candidate = $assignment->candidate;

        $this->get(route('candidates.assignment.dispatch', $candidate))
            ->assertMethodNotAllowed();

        $this->assertSame(AssignmentStatus::Dispatched, $assignment->fresh()->status);
    }

    public function test_assignment_dispatch_rejects_unknown_candidate(): void
    {
        $this->post('/candidates/999999/assignment/dispatch')
            ->assertNotFound();
    }

    public function test_dispatch_is_rejected_for_non_hire_verdict(): void
    {
        $assignment = $this->dispatchedAssignment(AssignmentStatus::Generated, 'maybe');

        $this->post(route('candidates.assignment.dispatch', $assignment->candidate))
            ->assertRedirect();

        $this->assertSame(AssignmentStatus::Generated, $assignment->fresh()->status);
    }

    public function test_public_submission_rejects_get_request_with_side_effects(): void
    {
        $assignment = $this->dispatchedAssignment();

        $before = $assignment->fresh()->status;

        $this->get(route('assignments.show', $assignment->token))->assertOk();

        $this->assertSame($before, $assignment->fresh()->status);
    }

    public function test_submission_rejects_non_github_url_scheme(): void
    {
        $assignment = $this->dispatchedAssignment();

        $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'javascript:alert(1)',
            'reflection' => 'A perfectly valid reflection that is long enough to pass validation.',
        ])->assertSessionHasErrors('submitted_repo_url');

        $this->assertSame(AssignmentStatus::Dispatched, $assignment->fresh()->status);
    }

    public function test_submission_trims_surrounding_whitespace_in_reflection(): void
    {
        $assignment = $this->dispatchedAssignment();

        $reflection = "  Approached it iteratively.\n\nUsed AI to explore options, then wrote the final version myself.  ";

        $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/candidate/work',
            'reflection' => $reflection,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            "Approached it iteratively.\n\nUsed AI to explore options, then wrote the final version myself.",
            $assignment->fresh()->reflection
        );
    }

    public function test_submitted_assignment_cannot_be_overwritten(): void
    {
        $assignment = $this->dispatchedAssignment();

        $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/candidate/work',
            'reflection' => 'A valid reflection that passes the fifty character minimum length.',
        ])->assertSessionHasNoErrors();

        $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/attacker/evil',
            'reflection' => 'A second attempt overwriting the first submission with attacker data.',
        ])->assertNotFound();

        $this->assertSame(
            'https://github.com/candidate/work',
            $assignment->fresh()->submitted_repo_url
        );
    }

    public function test_token_is_not_guessable_from_candidate_id(): void
    {
        $assignment = $this->dispatchedAssignment();

        $this->assertNotSame(Str::random(48), $assignment->token);
        $this->assertSame(48, strlen($assignment->token));

        $this->get('/assignments/'.$assignment->candidate->id)->assertNotFound();
        $this->get('/assignments/'.$assignment->id)->assertNotFound();
    }

    public function test_public_page_does_not_leak_internal_fields(): void
    {
        $assignment = $this->dispatchedAssignment();
        $assignment->update(['review_notes' => 'internal-only-review-note']);

        $this->get(route('assignments.show', $assignment->token))
            ->assertOk()
            ->assertDontSee('internal-only-review-note');
    }

    public function test_public_page_escapes_injected_html_in_brief(): void
    {
        $assignment = $this->dispatchedAssignment();
        $assignment->update([
            'brief' => array_merge($assignment->brief, [
                'objective' => '<script>alert("xss")</script>',
            ]),
        ]);

        $this->get(route('assignments.show', $assignment->token))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_candidate_index_shows_assignment_link_after_dispatch(): void
    {
        $assignment = $this->dispatchedAssignment();

        $this->get(route('candidates.show', $assignment->candidate))
            ->assertOk()
            ->assertSee(route('assignments.show', $assignment->token), false);
    }

    public function test_assignment_generation_service_is_idempotent_at_database_level(): void
    {
        $assignment = $this->dispatchedAssignment(AssignmentStatus::Generated);

        $service = app(AssignmentGenerationService::class);
        $again = $service->generate($assignment->candidate, $assignment->evaluation);

        $this->assertSame($assignment->id, $again->id);
        $this->assertDatabaseCount('assignments', 1);
    }

    public function test_evaluation_id_is_unique_at_the_database_level(): void
    {
        $assignment = $this->dispatchedAssignment(AssignmentStatus::Generated);

        $this->expectException(QueryException::class);

        Assignment::create([
            'candidate_id' => $assignment->candidate_id,
            'evaluation_id' => $assignment->evaluation_id,
            'token' => Str::random(48),
            'status' => AssignmentStatus::Generated,
            'brief' => ['objective' => 'Duplicate attempt.'],
        ]);
    }

    public function test_interview_questions_unaffected_by_assignment_flow(): void
    {
        $assignment = $this->dispatchedAssignment(AssignmentStatus::Generated);

        InterviewQuestion::create([
            'evaluation_id' => $assignment->evaluation_id,
            'dimension' => 'code_quality',
            'question' => 'How would you test this?',
            'why_ask' => 'Testing signal is weak.',
            'generated_at' => now(),
        ]);

        $this->get(route('candidates.show', $assignment->candidate))
            ->assertOk()
            ->assertSee('How would you test this?');
    }

    public function test_check_hr_user_middleware_allows_valid_and_rejects_invalid_id(): void
    {
        $hr = HrUser::factory()->create();

        $request = Request::create('/api/probe', 'GET', ['hr_user_id' => $hr->id]);
        $response = (new CheckHrUser)->handle($request, fn ($r) => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($request->has('hr_user'));
        $this->assertSame($hr->id, $request->input('hr_user')->id);

        $badRequest = Request::create('/api/probe', 'GET', ['hr_user_id' => 999999]);
        $badResponse = (new CheckHrUser)->handle($badRequest, fn ($r) => response('ok'));

        $this->assertSame(401, $badResponse->getStatusCode());
    }

    private function dispatchedAssignment(
        AssignmentStatus $status = AssignmentStatus::Dispatched,
        string $verdict = 'hire'
    ): Assignment {
        $candidate = Candidate::factory()->create();

        $evaluation = $candidate->evaluation()->create([
            'overall_score' => 7.5,
            'verdict' => $verdict,
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
                'objective' => 'Build a small course-authoring API.',
                'deliverables' => ['REST API'],
            ],
            'generated_at' => now(),
        ]);
    }
}
