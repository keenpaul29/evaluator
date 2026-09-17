<?php

namespace App\Services;

use App\Models\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubService
{
    private string $baseUrl = 'https://api.github.com';

    private string $token;

    private ?int $rateLimitRemaining = null;

    private ?int $rateLimitReset = null;

    public function __construct()
    {
        $this->token = config('services.github.token') ?? '';
    }

    private function headers(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'ColoredCow-Technical-Candidate-Evaluator/1.0',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        return $headers;
    }

    private function trackRateLimit($response): void
    {
        $remaining = $response->header('X-RateLimit-Remaining');
        $reset = $response->header('X-RateLimit-Reset');

        $this->rateLimitRemaining = $remaining !== null ? (int) $remaining : null;
        $this->rateLimitReset = $reset !== null ? (int) $reset : null;

        if ($this->rateLimitRemaining !== null && $this->rateLimitRemaining < 100) {
            Log::warning('GitHub API rate limit running low', [
                'remaining' => $this->rateLimitRemaining,
                'reset_at' => $this->rateLimitReset ? date('Y-m-d H:i:s', $this->rateLimitReset) : 'unknown',
            ]);
        }
    }

    private function handleRateLimit($response): void
    {
        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');
            $waitSeconds = $retryAfter ? (int) $retryAfter : 60;

            Log::warning('GitHub API rate limited, waiting', ['wait_seconds' => $waitSeconds]);

            sleep($waitSeconds);
        }
    }

    public function getRateLimitInfo(): ?array
    {
        if ($this->rateLimitRemaining === null) {
            return null;
        }

        return [
            'remaining' => $this->rateLimitRemaining,
            'reset_at' => $this->rateLimitReset ? date('Y-m-d H:i:s', $this->rateLimitReset) : null,
        ];
    }

    public function getUser(string $username): ?array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/users/{$username}");

        $this->trackRateLimit($response);

        if ($response->status() === 429) {
            $this->handleRateLimit($response);

            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/users/{$username}");
        }

        if ($response->failed()) {
            Log::warning("GitHub API: Failed to fetch user {$username}", [
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function getUserRepos(string $username, int $perPage = 30, int $page = 1): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/users/{$username}/repos", [
                'per_page' => $perPage,
                'page' => $page,
                'sort' => 'updated',
                'direction' => 'desc',
            ]);

        $this->trackRateLimit($response);

        if ($response->status() === 429) {
            $this->handleRateLimit($response);

            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/users/{$username}/repos", [
                    'per_page' => $perPage,
                    'page' => $page,
                    'sort' => 'updated',
                    'direction' => 'desc',
                ]);
        }

        if ($response->failed()) {
            Log::warning("GitHub API: Failed to fetch repos for {$username}", [
                'status' => $response->status(),
            ]);

            return [];
        }

        return $response->json();
    }

    public function getRepo(string $owner, string $repo): ?array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}");

        $this->trackRateLimit($response);

        if ($response->status() === 429) {
            $this->handleRateLimit($response);

            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/repos/{$owner}/{$repo}");
        }

        if ($response->failed()) {
            Log::warning("GitHub API: Failed to fetch repo {$owner}/{$repo}", [
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function getRepoContents(string $owner, string $repo, string $path = ''): ?array
    {
        $url = $path
            ? "{$this->baseUrl}/repos/{$owner}/{$repo}/contents/{$path}"
            : "{$this->baseUrl}/repos/{$owner}/{$repo}/contents";

        $response = Http::withHeaders($this->headers())->get($url);

        $this->trackRateLimit($response);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function getFileContent(string $owner, string $repo, string $path): ?string
    {
        $contents = $this->getRepoContents($owner, $repo, $path);

        if (! $contents || ! isset($contents['download_url'])) {
            return null;
        }

        $response = Http::withHeaders($this->headers())
            ->get($contents['download_url']);

        if ($response->failed()) {
            return null;
        }

        return $response->body();
    }

    public function getRepoTree(string $owner, string $repo, string $sha = 'HEAD'): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/git/trees/{$sha}", [
                'recursive' => 1,
            ]);

        $this->trackRateLimit($response);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['tree'] ?? [];
    }

    public function getRepoCommits(string $owner, string $repo, int $perPage = 30): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/commits", [
                'per_page' => $perPage,
            ]);

        $this->trackRateLimit($response);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    public function getRepoLanguages(string $owner, string $repo): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/languages");

        $this->trackRateLimit($response);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    public function syncCandidateRepoUrls(array $repoUrls, int $candidateId): array
    {
        $synced = [];

        foreach ($repoUrls as $repoUrl) {
            $parts = $this->parseGithubRepoUrl($repoUrl);

            if (! $parts) {
                Log::warning('GitHub API: Invalid repository URL submitted', [
                    'url' => $repoUrl,
                    'candidate_id' => $candidateId,
                ]);

                continue;
            }

            $repoData = $this->getRepo($parts['owner'], $parts['repo']);

            if (! $repoData) {
                Log::warning('GitHub API: Submitted repository unavailable', [
                    'url' => $repoUrl,
                    'candidate_id' => $candidateId,
                ]);

                continue;
            }

            if ($repoData['fork'] ?? false) {
                Log::info('GitHub API: Skipping forked repository', [
                    'url' => $repoUrl,
                    'candidate_id' => $candidateId,
                ]);

                continue;
            }

            $synced[] = $this->upsertCandidateRepository($repoData, $candidateId);
        }

        return $synced;
    }

    public function syncCandidateRepos(string $username, int $candidateId): array
    {
        $repos = $this->getUserRepos($username, 100);
        $synced = [];

        foreach ($repos as $repoData) {
            if ($repoData['fork'] ?? false) {
                continue;
            }

            $synced[] = $this->upsertCandidateRepository($repoData, $candidateId);
        }

        return $synced;
    }

    private function parseGithubRepoUrl(string $repoUrl): ?array
    {
        $path = parse_url(trim($repoUrl), PHP_URL_PATH);

        if (! $path) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (count($segments) < 2) {
            return null;
        }

        return [
            'owner' => $segments[0],
            'repo' => preg_replace('/\.git$/', '', $segments[1]),
        ];
    }

    private function upsertCandidateRepository(array $repoData, int $candidateId): Repository
    {
        return Repository::updateOrCreate(
            [
                'candidate_id' => $candidateId,
                'github_repo_id' => $repoData['id'],
            ],
            [
                'name' => $repoData['name'],
                'full_name' => $repoData['full_name'],
                'description' => $repoData['description'] ?? null,
                'html_url' => $repoData['html_url'],
                'default_branch' => $repoData['default_branch'] ?? 'main',
                'primary_language' => $repoData['language'] ?? null,
                'stars_count' => $repoData['stargazers_count'] ?? 0,
                'forks_count' => $repoData['forks_count'] ?? 0,
                'open_issues_count' => $repoData['open_issues_count'] ?? 0,
                'created_at_github' => $repoData['created_at'] ?? null,
                'updated_at_github' => $repoData['updated_at'] ?? null,
                'topics' => $repoData['topics'] ?? [],
                'is_fork' => $repoData['fork'] ?? false,
                'fork_parent_name' => ($repoData['fork'] ?? false) ? ($repoData['parent']['full_name'] ?? null) : null,
            ]
        );
    }
}
