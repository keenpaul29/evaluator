<?php

namespace App\Console\Commands;

use App\Enums\AssignmentStatus;
use App\Enums\CandidateStatus;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\HrUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedE2eFixtures extends Command
{
    protected $signature = 'e2e:seed {--reset : Wipe existing e2e fixtures first}';

    protected $description = 'Seed deterministic fixtures for the Playwright end-to-end suite';

    public function handle(): int
    {
        if ($this->option('reset')) {
            Assignment::query()->delete();
            Candidate::query()->where('email', 'like', '%@e2e.test')->delete();
        }

        $hr = HrUser::first() ?? HrUser::factory()->create([
            'name' => 'E2E Reviewer',
            'email' => 'reviewer@e2e.test',
        ]);

        $candidate = Candidate::factory()->create([
            'name' => 'Priya Sharma',
            'email' => 'priya@e2e.test',
            'github_username' => 'priya-e2e',
            'status' => CandidateStatus::Evaluated,
            'submitted_by' => $hr->id,
        ]);

        $candidate->evaluation()->create([
            'overall_score' => 7.8,
            'verdict' => 'hire',
            'narrative_summary' => 'Solid backend engineer with clear ownership instincts.',
            'strengths' => ['Consistent test coverage'],
            'concerns' => ['Documentation trails the code in places'],
            'interview_focus_areas' => ['Walk through a documentation decision'],
            'ai_model_used' => 'gemini-3.1-flash-lite',
            'evaluated_at' => now(),
        ]);

        $dispatched = Assignment::create([
            'candidate_id' => $candidate->id,
            'evaluation_id' => $candidate->evaluation->id,
            'token' => Str::random(48),
            'status' => AssignmentStatus::Dispatched,
            'brief' => [
                'title' => 'Evolve a multi-tenant learning platform',
                'objective' => 'Build a small course-authoring API with strict per-tenant data isolation and a weekly engagement report.',
                'context' => 'ColoredCow sustains interactive-video learning platforms for education NGOs, where reporting has to stay dependable for teachers who read it first thing every week.',
                'deliverables' => [
                    'Laravel REST API for courses, lessons and enrolment',
                    'Per-tenant isolation enforced in every query path',
                    'A weekly engagement report endpoint',
                    'Test suite plus a README that runs from scratch',
                ],
                'review_criteria' => [
                    'Tests are meaningful and pass in CI',
                    'Code is review-ready: clear naming, small surface, documented decisions',
                    'Runs without us: a new developer can boot it unaided',
                ],
                'timebox' => '7 days, ~12-15 focused hours.',
                'ai_use_note' => 'Use AI as a thinking partner. AI must not replace understanding, and honest attribution of what you used it for is part of the work.',
            ],
            'generated_at' => now(),
            'dispatched_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $pending = Candidate::factory()->create([
            'name' => 'Arjun Verma',
            'email' => 'arjun@e2e.test',
            'github_username' => 'arjun-e2e',
            'status' => CandidateStatus::Evaluated,
            'submitted_by' => $hr->id,
        ]);

        $pending->evaluation()->create([
            'overall_score' => 8.4,
            'verdict' => 'strong_hire',
            'narrative_summary' => 'Excellent technical judgment.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-3.1-flash-lite',
            'evaluated_at' => now(),
        ]);

        $generated = Assignment::create([
            'candidate_id' => $pending->id,
            'evaluation_id' => $pending->evaluation->id,
            'token' => Str::random(48),
            'status' => AssignmentStatus::Generated,
            'brief' => [
                'title' => 'Harden a public-sector reporting pipeline',
                'objective' => 'Add caching and a nightly export job to a reporting endpoint.',
                'deliverables' => ['Cached endpoint', 'Nightly export job'],
            ],
            'generated_at' => now(),
        ]);

        file_put_contents(base_path('e2e/fixtures.json'), json_encode([
            'dispatched' => [
                'token' => $dispatched->token,
                'candidate' => $candidate->name,
                'candidate_id' => $candidate->id,
                'title' => $dispatched->brief['title'],
                'objective' => $dispatched->brief['objective'],
            ],
            'generated' => [
                'token' => $generated->token,
                'candidate' => $pending->name,
                'candidate_id' => $pending->id,
            ],
        ], JSON_PRETTY_PRINT));

        $this->info('Seeded e2e fixtures. Dispatched token: '.$dispatched->token);

        return self::SUCCESS;
    }
}
