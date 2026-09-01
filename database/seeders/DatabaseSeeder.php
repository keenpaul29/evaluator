<?php

namespace Database\Seeders;

use App\Models\HrUser;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationDimension;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $hrUser = HrUser::create([
            'name' => 'HR Admin',
            'email' => 'hr@coloredcow.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $reviewer = HrUser::create([
            'name' => 'Technical Reviewer',
            'email' => 'reviewer@coloredcow.com',
            'password' => Hash::make('password'),
            'role' => 'reviewer',
        ]);

        $candidates = [
            [
                'name' => 'Priya Sharma',
                'email' => 'priya@example.com',
                'github_username' => 'torvalds',
                'status' => 'evaluated',
                'submitted_by' => $hrUser->id,
                'submission_type' => 'hr_initiated',
            ],
            [
                'name' => 'Rahul Verma',
                'email' => 'rahul@example.com',
                'github_username' => 'gaearon',
                'status' => 'evaluated',
                'submitted_by' => $hrUser->id,
                'submission_type' => 'hr_initiated',
            ],
            [
                'name' => 'Anita Desai',
                'email' => 'anita@example.com',
                'github_username' => 'sindresorhus',
                'status' => 'submitted',
                'submitted_by' => null,
                'submission_type' => 'candidate_self_service',
            ],
        ];

        foreach ($candidates as $data) {
            Candidate::create($data);
        }

        $evaluated = Candidate::where('status', 'evaluated')->first();
        if ($evaluated) {
            $evaluation = Evaluation::create([
                'candidate_id' => $evaluated->id,
                'overall_score' => 7.5,
                'verdict' => 'hire',
                'narrative_summary' => 'This candidate demonstrates strong technical fundamentals and a clear understanding of software craftsmanship. Their GitHub profile shows consistent contribution patterns and thoughtful code organization. The repositories analyzed reveal experience with modern web technologies and a commitment to documentation. Areas for growth include deeper engagement with testing practices and more explicit demonstration of collaborative development patterns.',
                'strengths' => [
                    'Strong code organization and naming conventions',
                    'Consistent commit history showing sustained engagement',
                    'Good README documentation in primary projects',
                    'Demonstrates learning through progressive project complexity',
                ],
                'concerns' => [
                    'Limited evidence of team collaboration in public repos',
                    'Testing coverage could be more visible',
                    'Some repositories lack documentation',
                ],
                'interview_focus_areas' => [
                    'Team collaboration and code review experience',
                    'Testing philosophy and approach',
                    'Architecture decision-making process',
                ],
                'ai_model_used' => 'gemini-1.5-flash',
                'evaluated_at' => now(),
            ]);

            $dimensions = [
                ['dimension' => 'code_quality', 'score' => 8.0, 'justification' => 'Code is well-structured with consistent naming conventions. Files are organized logically and follow framework conventions.', 'evidence' => ['Clean module organization', 'Consistent code style across repositories']],
                ['dimension' => 'technical_judgment', 'score' => 7.5, 'justification' => 'Demonstrates sound architectural decisions for project scale. Trade-offs are reasonable and documented.', 'evidence' => ['Appropriate technology choices', 'Clear project structure']],
                ['dimension' => 'colvalues_alignment', 'score' => 7.0, 'justification' => 'Shows ownership through complete projects and documentation. Evidence of learning through diverse project types.', 'evidence' => ['Personal projects show initiative', 'Documentation explains reasoning']],
                ['dimension' => 'communication', 'score' => 7.5, 'justification' => 'READMEs are clear and provide good getting-started guides. Commit messages are generally descriptive.', 'evidence' => ['Detailed README files', 'Descriptive commit messages']],
                ['dimension' => 'problem_complexity', 'score' => 7.0, 'justification' => 'Projects show progression from simple to complex. Some evidence of system design thinking.', 'evidence' => ['Progressive project complexity', 'Multi-component architectures']],
                ['dimension' => 'learning_trajectory', 'score' => 8.0, 'justification' => 'Clear progression in technology choices and project sophistication over time.', 'evidence' => ['Adoption of newer technologies', 'Increasing project complexity']],
                ['dimension' => 'technical_breadth', 'score' => 7.5, 'justification' => 'Experience spans frontend and backend technologies. Some exposure to DevOps practices.', 'evidence' => ['Multiple language proficiency', 'CI/CD configuration present']],
            ];

            foreach ($dimensions as $dim) {
                EvaluationDimension::create(array_merge($dim, [
                    'evaluation_id' => $evaluation->id,
                    'weight' => 1.0,
                ]));
            }
        }
    }
}
