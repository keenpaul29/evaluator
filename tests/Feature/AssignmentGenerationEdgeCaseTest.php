<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\AssignmentGenerationService;
use App\Services\ColoredCowContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssignmentGenerationEdgeCaseTest extends TestCase
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

    public function test_oversized_objective_is_truncated(): void
    {
        $assignment = $this->generate([
            'title' => 'T',
            'objective' => str_repeat('a', 5000),
            'deliverables' => ['D'],
        ]);

        $this->assertNotNull($assignment);
        $this->assertLessThanOrEqual(2001, mb_strlen($assignment->brief['objective']));
    }

    public function test_oversized_lists_are_capped(): void
    {
        $assignment = $this->generate([
            'title' => 'T',
            'objective' => 'O',
            'deliverables' => array_map(fn ($i) => "Item {$i}", range(1, 40)),
        ]);

        $this->assertNotNull($assignment);
        $this->assertCount(10, $assignment->brief['deliverables']);
    }

    public function test_non_string_list_items_are_dropped(): void
    {
        $assignment = $this->generate([
            'title' => 'T',
            'objective' => 'O',
            'deliverables' => ['Real item', 42, null, ['nested'], 'Another real item'],
        ]);

        $this->assertNotNull($assignment);
        $this->assertSame(['Real item', 'Another real item'], $assignment->brief['deliverables']);
    }

    public function test_whitespace_is_collapsed_in_brief_text(): void
    {
        $assignment = $this->generate([
            'title' => 'T',
            'objective' => "Build   a thing\n\n\nwith   spacing",
            'deliverables' => ['D'],
        ]);

        $this->assertNotNull($assignment);
        $this->assertSame('Build a thing with spacing', $assignment->brief['objective']);
    }

    public function test_non_string_objective_is_rejected(): void
    {
        $this->assertNull($this->generate([
            'title' => 'T',
            'objective' => ['an', 'array'],
            'deliverables' => ['D'],
        ]));
    }

    public function test_string_deliverables_is_rejected(): void
    {
        $this->assertNull($this->generate([
            'title' => 'T',
            'objective' => 'O',
            'deliverables' => 'not an array',
        ]));
    }

    public function test_brief_shape_is_normalized_even_when_fields_are_wrong_types(): void
    {
        $assignment = $this->generate([
            'title' => 12345,
            'objective' => 'O',
            'context' => ['nope'],
            'deliverables' => ['D'],
            'review_criteria' => 'not an array',
            'timebox' => null,
            'ai_use_note' => 99,
        ]);

        $this->assertNotNull($assignment);
        $this->assertSame('Untitled assignment', $assignment->brief['title']);
        $this->assertSame('', $assignment->brief['context']);
        $this->assertSame([], $assignment->brief['review_criteria']);
        $this->assertStringContainsString('7 days', $assignment->brief['timebox']);
        $this->assertStringContainsString('AI', $assignment->brief['ai_use_note']);
    }

    public function test_submission_block_is_normalized(): void
    {
        $assignment = $this->generate([
            'title' => 'T',
            'objective' => 'O',
            'deliverables' => ['D'],
            'submission' => ['repo_url' => 'A fresh repo', 'reflection' => ['bad']],
        ]);

        $this->assertNotNull($assignment);
        $this->assertSame('A fresh repo', $assignment->brief['submission']['repo_url']);
        $this->assertSame('', $assignment->brief['submission']['reflection']);
    }

    public function test_missing_timebox_and_ai_note_get_safe_defaults(): void
    {
        $brief = [
            'title' => 'Minimal brief',
            'objective' => 'Do the thing.',
            'deliverables' => ['One deliverable'],
        ];

        $this->fakeResponse($brief);

        $assignment = $this->generate();

        $this->assertSame('Minimal brief', $assignment->brief['title']);
        $this->assertArrayHasKey('timebox', $assignment->brief);
        $this->assertArrayHasKey('ai_use_note', $assignment->brief);
        $this->assertStringContainsString('7 days', $assignment->brief['timebox']);
        $this->assertStringContainsString('AI', $assignment->brief['ai_use_note']);
    }

    public function test_response_without_objective_is_rejected(): void
    {
        $this->fakeResponse(['deliverables' => ['orphan']]);

        $this->assertNull($this->generate());
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_response_without_deliverables_is_rejected(): void
    {
        $this->fakeResponse(['objective' => 'No deliverables here.']);

        $this->assertNull($this->generate());
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_empty_response_body_is_rejected(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '']]]],
                ],
            ]),
        ]);

        $this->assertNull($this->generate());
    }

    public function test_prose_without_json_is_rejected(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'I would love to help, but here is a poem instead.']]]],
                ],
            ]),
        ]);

        $this->assertNull($this->generate());
    }

    public function test_falls_back_to_openai_when_gemini_fails(): void
    {
        config([
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'nope']], 500),
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'title' => 'Fallback brief',
                        'objective' => 'Built via the fallback provider.',
                        'deliverables' => ['Deliverable'],
                    ])]],
                ],
            ]),
        ]);

        $assignment = $this->generate();

        $this->assertNotNull($assignment);
        $this->assertSame('Fallback brief', $assignment->brief['title']);
        $this->assertSame('gpt-4o-mini', $assignment->ai_model_used);
    }

    public function test_generation_prompt_includes_portfolio_and_work_methods(): void
    {
        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request['contents'][0]['parts'][0]['text'] ?? '';

            return Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'T',
                        'objective' => 'O',
                        'deliverables' => ['D'],
                    ])]]]],
                ],
            ]);
        });

        $this->generate();

        $this->assertNotNull($captured);
        $this->assertStringContainsString('an interactive-video learning platform', $captured);
        $this->assertStringContainsString('a high-volume donor and field-worker CRM', $captured);
        $this->assertStringContainsString('code review', $captured);
        $this->assertStringContainsString('12-15', $captured);
        $this->assertStringContainsString('anonymize', $captured);
    }

    public function test_prompt_omits_raw_client_names_in_favour_of_anonymized_arcs(): void
    {
        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request['contents'][0]['parts'][0]['text'] ?? '';

            return Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'T',
                        'objective' => 'O',
                        'deliverables' => ['D'],
                    ])]]]],
                ],
            ]);
        });

        $this->generate();

        $this->assertStringNotContainsString('Plio', $captured);
        $this->assertStringNotContainsString('Goonj', $captured);
        $this->assertStringNotContainsString('Dost Education', $captured);
    }

    public function test_prompt_never_contains_raw_client_names_in_output_instruction(): void
    {
        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request['contents'][0]['parts'][0]['text'] ?? '';

            return Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'T',
                        'objective' => 'O',
                        'deliverables' => ['D'],
                    ])]]]],
                ],
            ]);
        });

        $this->generate();

        $this->assertStringContainsString('never name a client', $captured);
    }

    public function test_context_includes_wordpress_in_backend_stack(): void
    {
        $stack = ColoredCowContext::getFullContext()['tech_stack']['backend']['primary'];

        $this->assertContains('WordPress', $stack);
    }

    public function test_portfolio_archetypes_are_structured_and_anonymized(): void
    {
        $portfolio = ColoredCowContext::getPortfolio();

        $this->assertNotEmpty($portfolio['archetypes']);
        $this->assertStringContainsString('anonymized', strtolower($portfolio['intro']));

        foreach ($portfolio['archetypes'] as $archetype) {
            $this->assertArrayHasKey('name', $archetype);
            $this->assertArrayHasKey('anonymized_reference', $archetype);
            $this->assertArrayHasKey('sector', $archetype);
            $this->assertArrayHasKey('stack', $archetype);
            $this->assertNotSame($archetype['name'], $archetype['anonymized_reference']);
        }
    }

    public function test_take_home_contract_encourages_ai_use(): void
    {
        $contract = ColoredCowContext::getTakeHomeContract();

        $this->assertStringContainsString('AI', $contract);
        $this->assertStringContainsString('DSA', $contract);
        $this->assertStringContainsString('take-home', $contract);
        $this->assertStringContainsString('thinking partner', $contract);
    }

    public function test_default_due_days_is_seven(): void
    {
        $this->assertSame(7, AssignmentGenerationService::DEFAULT_DUE_DAYS);
    }

    public function test_assignment_starts_in_generated_state_with_no_due_date(): void
    {
        $this->fakeResponse([
            'title' => 'T',
            'objective' => 'O',
            'deliverables' => ['D'],
        ]);

        $assignment = $this->generate();

        $this->assertNotNull($assignment);
        $this->assertSame(AssignmentStatus::Generated, $assignment->status);
        $this->assertNull($assignment->due_at);
        $this->assertNull($assignment->dispatched_at);
        $this->assertNull($assignment->submitted_at);
    }

    private function fakeResponse(array $brief): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($brief)]]]],
                ],
            ]),
        ]);
    }

    private function generate(?array $brief = null): ?Assignment
    {
        $candidate = Candidate::factory()->evaluated()->create(['github_username' => 'candidate']);
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

        if ($brief !== null) {
            $this->fakeResponse($brief);
        }

        return app(AssignmentGenerationService::class)->generate($candidate, $evaluation);
    }
}
