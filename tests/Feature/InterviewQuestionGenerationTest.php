<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use App\Models\InterviewQuestion;
use App\Services\InterviewQuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InterviewQuestionGenerationTest extends TestCase
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

    public function test_generates_and_stores_questions_for_weak_dimensions(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'questions' => [
                                        [
                                            'dimension' => 'code_quality',
                                            'question' => 'In your portfolio repo, the GameController handles both HTTP and payment logic. How would you refactor this?',
                                            'repo_reference' => 'candidate/portfolio',
                                            'file_reference' => 'app/Http/Controllers/GameController.php',
                                            'why_ask' => 'Score: 5/10 - controller is doing too much.',
                                        ],
                                        [
                                            'dimension' => 'communication',
                                            'question' => 'Your README is minimal. How would you document onboarding for a new developer?',
                                            'repo_reference' => 'candidate/portfolio',
                                            'file_reference' => 'README.md',
                                            'why_ask' => 'Score: 5/10 - documentation signals are weak.',
                                        ],
                                    ],
                                ])],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 5.0,
            'communication' => 5.0,
            'technical_breadth' => 7.0,
        ]);

        $questions = app(InterviewQuestionService::class)->generate($candidate, $evaluation);

        $this->assertCount(2, $questions);
        $this->assertDatabaseCount('interview_questions', 2);
        $this->assertDatabaseHas('interview_questions', [
            'evaluation_id' => $evaluation->id,
            'dimension' => 'code_quality',
        ]);

        $stored = InterviewQuestion::where('evaluation_id', $evaluation->id)->get();
        $this->assertSame($stored->pluck('batch_id')->unique()->count(), 1);
        $this->assertNotNull($stored->first()->repo_reference);
        $this->assertNotNull($stored->first()->generated_at);
    }

    public function test_generates_questions_when_no_dimension_is_weak(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'questions' => [
                                        [
                                            'dimension' => 'problem_complexity',
                                            'question' => 'Walk me through the hardest system design problem you have solved.',
                                            'repo_reference' => 'candidate/portfolio',
                                            'file_reference' => 'src/Main.php',
                                            'why_ask' => 'Lowest scoring dimension.',
                                        ],
                                    ],
                                ])],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 8.0,
            'communication' => 7.5,
            'problem_complexity' => 7.0,
            'technical_breadth' => 8.5,
            'technical_judgment' => 8.0,
            'learning_trajectory' => 8.0,
            'colvalues_alignment' => 8.5,
        ]);

        $questions = app(InterviewQuestionService::class)->generate($candidate, $evaluation);

        $this->assertCount(1, $questions);
        $this->assertDatabaseHas('interview_questions', [
            'evaluation_id' => $evaluation->id,
            'dimension' => 'problem_complexity',
        ]);
    }

    public function test_returns_empty_array_when_ai_call_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'unavailable']], 503),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'code_quality' => 5.0,
            'technical_breadth' => 7.0,
        ]);

        $questions = app(InterviewQuestionService::class)->generate($candidate, $evaluation);

        $this->assertSame([], $questions);
        $this->assertDatabaseCount('interview_questions', 0);
    }

    public function test_stores_questions_from_raw_gemini_response_with_markdown_fences(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "```json\n".json_encode([
                                    'questions' => [
                                        [
                                            'dimension' => 'communication',
                                            'question' => 'Describe how you communicate technical decisions.',
                                            'repo_reference' => 'candidate/portfolio',
                                            'file_reference' => 'docs/decisions.md',
                                            'why_ask' => 'Communication signals are limited.',
                                        ],
                                    ],
                                ])."\n```"],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        [$candidate, $evaluation] = $this->candidateWithDimensions([
            'communication' => 5.0,
            'technical_breadth' => 7.0,
        ]);

        $questions = app(InterviewQuestionService::class)->generate($candidate, $evaluation);

        $this->assertCount(1, $questions);
        $this->assertDatabaseHas('interview_questions', [
            'evaluation_id' => $evaluation->id,
            'dimension' => 'communication',
        ]);
    }

    private function candidateWithDimensions(array $scores): array
    {
        $candidate = Candidate::factory()->evaluated()->create(['github_username' => 'candidate']);
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.0,
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
