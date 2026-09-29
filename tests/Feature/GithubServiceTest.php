<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Services\GithubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_info_is_null_before_any_request(): void
    {
        $service = app(GithubService::class);

        $this->assertNull($service->getRateLimitInfo());
    }

    public function test_rate_limit_info_is_tracked_after_request(): void
    {
        Http::fake([
            'api.github.com/users/octo' => Http::response(['login' => 'octo'], 200, [
                'X-RateLimit-Remaining' => '4999',
                'X-RateLimit-Reset' => '1760000000',
            ]),
        ]);

        $service = app(GithubService::class);
        $service->getUser('octo');

        $info = $service->getRateLimitInfo();
        $this->assertSame(4999, $info['remaining']);
        $this->assertNotNull($info['reset_at']);
    }

    public function test_sync_candidate_repo_urls_skips_forked_repos(): void
    {
        Http::fake([
            'api.github.com/repos/octo/owned' => Http::response($this->repoPayload([
                'id' => 1,
                'name' => 'owned',
                'full_name' => 'octo/owned',
                'fork' => false,
            ])),
            'api.github.com/repos/octo/forked' => Http::response($this->repoPayload([
                'id' => 2,
                'name' => 'forked',
                'full_name' => 'octo/forked',
                'fork' => true,
                'parent' => ['full_name' => 'someone/forked'],
            ])),
        ]);

        $candidate = Candidate::factory()->create();

        $synced = app(GithubService::class)->syncCandidateRepoUrls(
            ['https://github.com/octo/owned', 'https://github.com/octo/forked'],
            $candidate->id
        );

        $this->assertCount(1, $synced);
        $this->assertDatabaseHas('repositories', [
            'candidate_id' => $candidate->id,
            'github_repo_id' => 1,
        ]);
        $this->assertDatabaseMissing('repositories', [
            'candidate_id' => $candidate->id,
            'github_repo_id' => 2,
        ]);
    }

    public function test_sync_candidate_repos_filters_forks_from_full_profile(): void
    {
        Http::fake([
            'api.github.com/users/octo/repos*' => Http::response([
                $this->repoPayload(['id' => 10, 'name' => 'mine-a', 'full_name' => 'octo/mine-a', 'fork' => false]),
                $this->repoPayload(['id' => 11, 'name' => 'cloned-b', 'full_name' => 'octo/cloned-b', 'fork' => true]),
                $this->repoPayload(['id' => 12, 'name' => 'mine-c', 'full_name' => 'octo/mine-c', 'fork' => false]),
            ]),
        ]);

        $candidate = Candidate::factory()->create(['github_username' => 'octo']);

        $synced = app(GithubService::class)->syncCandidateRepos('octo', $candidate->id);

        $this->assertCount(2, $synced);
        $this->assertDatabaseHas('repositories', ['candidate_id' => $candidate->id, 'github_repo_id' => 10]);
        $this->assertDatabaseHas('repositories', ['candidate_id' => $candidate->id, 'github_repo_id' => 12]);
        $this->assertDatabaseMissing('repositories', ['candidate_id' => $candidate->id, 'github_repo_id' => 11]);
    }

    public function test_get_user_returns_null_on_api_failure(): void
    {
        Http::fake([
            'api.github.com/users/ghost' => Http::response([], 404),
        ]);

        $service = app(GithubService::class);

        $this->assertNull($service->getUser('ghost'));
    }

    private function repoPayload(array $overrides): array
    {
        return array_merge([
            'name' => 'repo',
            'description' => 'A repository',
            'html_url' => 'https://github.com/owner/repo',
            'default_branch' => 'main',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-01-02T00:00:00Z',
            'topics' => [],
            'fork' => false,
            'parent' => null,
        ], $overrides);
    }
}
