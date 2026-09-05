<?php

namespace App\Services;

use App\Models\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubService
{
    private string $baseUrl = 'https://api.github.com';

    private string $token;

    public function __construct()
    {
        $this->token = config('services.github.token', '');
    }

    private function headers(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'ColoredCow-Technical-Candidate-Evaluator/1.0',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    public function getUser(string $username): ?array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/users/{$username}");

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

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    public function getRepoLanguages(string $owner, string $repo): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/languages");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    public function syncCandidateRepos(string $username, int $candidateId): array
    {
        $repos = $this->getUserRepos($username, 100);
        $synced = [];

        foreach ($repos as $repoData) {
            $repo = Repository::updateOrCreate(
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
                    'fork_parent_name' => $repoData['fork'] ? ($repoData['parent']['full_name'] ?? null) : null,
                ]
            );

            $synced[] = $repo;
        }

        return $synced;
    }
}
