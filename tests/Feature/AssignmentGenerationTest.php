<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use App\Services\AssignmentGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssignmentGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-1.5-flash',
        ]);
    }

    public function test_generates_and_stores_take_home_assignment(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($this->validBrief())],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 8.0,
            'technical_judgment' => 7.0,
            'colvalues_alignment' => 8.5,
            'communication' => 6.0,
            'problem_complexity' => 8.0,
            'learning_trajectory' => 8.0,
            'technical_breadth' => 7.5,
        ]);

        $assignment = app(AssignmentGenerationService::class)->generate($candidate, $evaluation);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'candidate_id' => $candidate->id,
            'evaluation_id' => $evaluation->id,
            'status' => AssignmentStatus::Generated,
        ]);

        $this->assertNotNull($assignment->token);
        $this->assertSame(sprintf('%s', AssignmentStatus::Generated->value), $assignment->status->value);
        $this->assertSame('Evolve a multi-tenant learning platform', $assignment->brief['title']);
        $this->assertCount(3, $assignment->brief['deliverables']);
        $this->assertNotNull($assignment->generated_at);
        $this->assertSame('gemini-1.5-flash', $assignment->ai_model_used);
    }

    public function test_returns_null_when_ai_call_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'unavailable']], 503),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 8.0,
            'technical_breadth' => 7.0,
            'technical_judgment' => 7.0,
            'colvalues_alignment' => 8.5,
            'communication' => 6.0,
            'problem_complexity' => 8.0,
            'learning_trajectory' => 8.0,
        ]);

        $assignment = app(AssignmentGenerationService::class)->generate($candidate, $evaluation);

        $this->assertNull($assignment);
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_stores_assignment_from_markdown_fenced_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "```json\n".json_encode($this->validBrief())."\n```"],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 8.0,
            'technical_breadth' => 7.0,
            'technical_judgment' => 7.0,
            'colvalues_alignment' => 8.5,
            'communication' => 6.0,
            'problem_complexity' => 8.0,
            'learning_trajectory' => 8.0,
        ]);

        $assignment = app(AssignmentGenerationService::class)->generate($candidate, $evaluation);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertSame('Evolve a multi-tenant learning platform', $assignment->brief['title']);
    }

    public function test_does_not_generate_second_assignment_when_one_exists(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($this->validBrief())],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 8.0,
            'technical_breadth' => 7.0,
        ]);

        $service = app(AssignmentGenerationService::class);
        $first = $service->generate($candidate, $evaluation);
        $second = $service->generate($candidate, $evaluation);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('assignments', 1);
    }

    private function validBrief(): array
    {
        return [
            'title' => 'Evolve a multi-tenant learning platform',
            'objective' => 'Build a small clone of a multi-tenant course authoring system with per-tenant data isolation.',
            'context' => 'ColoredCow builds and sustains interactive-video learning platforms for education NGOs. This exercise mirrors that arc: an authoring tool with reporting.',
            'deliverables' => [
                'A Laravel backend exposing a REST API for courses and lessons',
                'Per-tenant data isolation enforced in queries',
                'A test suite with CI-ready commands',
            ],
            'review_criteria' => [
                'Tests pass and are meaningful',
                'Code is review-ready: small PRs, clear naming, docs',
                'Runs without us: README setup works from scratch',
            ],
            'timebox' => '7 days, ~12-15 focused hours.',
            'ai_use_note' => 'You may use AI as a thinking partner. The work must be your own.',
            'submission' => [
                'repo_url' => 'Provide a fresh GitHub repository',
                'reflection' => 'Short reflection: approach, decisions, obstacles, learnings.',
            ],
        ];
    }

    private function candidateWithDimensions(array $scores): array
    {
        $candidate = Candidate::factory()->evaluated()->create(['github_username' => 'candidate']);
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

        foreach ($scores as $dimension => $score) {
            EvaluationDimension::create([
                'evaluation_id' => $evaluation->id,
                'dimension' => $dimension,
                'score' => $score,
                'justification' => "Justification for {$dimension}.",
                'evidence' => [],
            ]);
        }

        return [$candidate, $evaluation->load('dimensions')];
    }
}
