<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\HrUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'github_username' => fake()->userName(),
            'linkedin_url' => null,
            'portfolio_url' => null,
            'status' => 'submitted',
            'submitted_by' => HrUser::factory(),
            'submission_type' => 'hr_initiated',
            'notes' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
        ]);
    }

    public function analyzing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'analyzing',
        ]);
    }

    public function evaluated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'evaluated',
        ]);
    }

    public function shortlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shortlisted',
        ]);
    }

    public function selfService(): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_type' => 'candidate_self_service',
            'submitted_by' => null,
        ]);
    }
}
