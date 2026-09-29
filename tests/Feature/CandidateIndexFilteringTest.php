<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\Evaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateIndexFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_by_status(): void
    {
        $submitted = Candidate::factory()->submitted()->create(['name' => 'Pending Person']);
        $evaluated = Candidate::factory()->evaluated()->create(['name' => 'Complete Person']);

        $response = $this->get('/candidates?status='.CandidateStatus::Evaluated->value);

        $response->assertStatus(200);
        $response->assertSee('Complete Person');
        $response->assertDontSee('Pending Person');
    }

    public function test_index_filters_by_verdict(): void
    {
        $hire = Candidate::factory()->evaluated()->create(['name' => 'Hire Person']);
        $this->attachEvaluation($hire, 7.5, 'hire');

        $noHire = Candidate::factory()->evaluated()->create(['name' => 'No Hire Person']);
        $this->attachEvaluation($noHire, 3.0, 'no_hire');

        $response = $this->get('/candidates?verdict=hire');

        $response->assertStatus(200);
        $response->assertSee('Hire Person');
        $response->assertDontSee('No Hire Person');
    }

    public function test_index_filters_by_min_score(): void
    {
        $top = Candidate::factory()->evaluated()->create(['name' => 'Top Scorer']);
        $this->attachEvaluation($top, 9.0, 'strong_hire');

        $mid = Candidate::factory()->evaluated()->create(['name' => 'Mid Scorer']);
        $this->attachEvaluation($mid, 6.0, 'maybe');

        $response = $this->get('/candidates?min_score=8');

        $response->assertStatus(200);
        $response->assertSee('Top Scorer');
        $response->assertDontSee('Mid Scorer');
    }

    public function test_index_filters_by_max_score(): void
    {
        $top = Candidate::factory()->evaluated()->create(['name' => 'Top Scorer']);
        $this->attachEvaluation($top, 9.0, 'strong_hire');

        $mid = Candidate::factory()->evaluated()->create(['name' => 'Mid Scorer']);
        $this->attachEvaluation($mid, 6.0, 'maybe');

        $response = $this->get('/candidates?max_score=7');

        $response->assertStatus(200);
        $response->assertSee('Mid Scorer');
        $response->assertDontSee('Top Scorer');
    }

    public function test_index_filters_by_search_on_name_email_and_github_username(): void
    {
        Candidate::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com', 'github_username' => 'alicew']);
        Candidate::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com', 'github_username' => 'bobbuilder']);

        $response = $this->get('/candidates?search=alice');

        $response->assertStatus(200);
        $response->assertSee('Alice Wonder');
        $response->assertDontSee('Bob Builder');

        $response = $this->get('/candidates?search=bob@example.com');

        $response->assertStatus(200);
        $response->assertSee('Bob Builder');
        $response->assertDontSee('Alice Wonder');
    }

    public function test_index_shows_all_candidates_without_filters(): void
    {
        Candidate::factory()->submitted()->create(['name' => 'First Candidate']);
        Candidate::factory()->evaluated()->create(['name' => 'Second Candidate']);

        $response = $this->get('/candidates');

        $response->assertStatus(200);
        $response->assertSee('First Candidate');
        $response->assertSee('Second Candidate');
    }

    private function attachEvaluation(Candidate $candidate, float $score, string $verdict): void
    {
        Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => $score,
            'verdict' => $verdict,
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);
    }
}
