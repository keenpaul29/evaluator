<?php

namespace App\Console\Commands;

use App\Services\GithubService;
use Illuminate\Console\Command;

class PilotCandidateCheck extends Command
{
    protected $signature = 'pilot:candidate-check {username}';

    protected $description = 'Pre-flight check that a pilot candidate clears the artifact-rich threshold (>=2 repos with authored commits, one with tests + CI + README)';

    public function handle(GithubService $github): int
    {
        $username = $this->argument('username');

        $user = $github->getUser($username);
        if (! $user) {
            $this->error("Could not fetch GitHub profile for {$username}");

            return self::FAILURE;
        }

        $repos = array_values(array_filter(
            $github->getUserRepos($username, 100),
            fn (array $repo) => ! ($repo['fork'] ?? false)
        ));

        if (empty($repos)) {
            $this->error("No non-fork repositories found for {$username}");

            return self::FAILURE;
        }

        $this->info('Found '.count($repos).' candidate repositories for '.$user['login'].' ('.$user['name'].')');

        $authoredRepos = 0;
        $evidenceRepo = null;
        $report = [];

        foreach ($repos as $repo) {
            $owner = $repo['owner']['login'];
            $name = $repo['name'];

            $commits = $github->getRepoCommits($owner, $name, 100);
            $authored = count(array_filter($commits, fn ($c) => $this->isAuthoredBy($c, $user)));

            $tree = $github->getRepoTree($owner, $name);
            $paths = array_column($tree, 'path');
            $hasTests = $this->hasTests($paths);
            $hasCi = $this->hasCi($paths);
            $hasReadme = $this->hasReadme($paths);

            $report[] = [
                'repo' => $repo['full_name'],
                'authored_commits' => $authored,
                'tests' => $hasTests,
                'ci' => $hasCi,
                'readme' => $hasReadme,
            ];

            if ($authored > 0) {
                $authoredRepos++;
            }

            if ($evidenceRepo === null && $authored > 0 && $hasTests && $hasCi && $hasReadme) {
                $evidenceRepo = $repo['full_name'];
            }
        }

        $this->table(['Repository', 'Authored commits', 'Tests', 'CI', 'README'], $report);

        $pass = $authoredRepos >= 2 && $evidenceRepo !== null;

        $this->newLine();
        $this->info("Repos with authored commits: {$authoredRepos}");
        $this->info('Evidence repo (tests + CI + README): '.($evidenceRepo ?? 'none'));

        if ($pass) {
            $this->info('PASS: candidate clears the artifact-rich threshold.');

            return self::SUCCESS;
        }

        $this->error('FAIL: candidate does not clear the threshold. Pick the contingency candidate.');

        return self::FAILURE;
    }

    private function isAuthoredBy(array $commit, array $user): bool
    {
        $authorLogin = strtolower((string) ($commit['author']['login'] ?? ''));
        $authorName = strtolower((string) ($commit['commit']['author']['name'] ?? ''));
        $authorEmail = strtolower((string) ($commit['commit']['author']['email'] ?? ''));

        $login = strtolower((string) ($user['login'] ?? ''));
        $name = strtolower((string) ($user['name'] ?? ''));

        if ($authorLogin !== '' && $authorLogin === $login) {
            return true;
        }

        if ($authorName !== '' && $name !== '' && $authorName === $name) {
            return true;
        }

        if ($authorEmail !== '') {
            $emails = array_map('strtolower', array_values(array_filter([$user['email'] ?? null])));

            return in_array($authorEmail, $emails, true);
        }

        return false;
    }

    private function hasTests(array $paths): bool
    {
        foreach ($paths as $path) {
            $lower = strtolower($path);

            if (str_contains($lower, '/test/')
                || str_contains($lower, '/tests/')
                || str_starts_with($lower, 'test/')
                || str_starts_with($lower, 'tests/')
                || str_contains($lower, '__tests__')
                || str_ends_with($lower, '_test.php')
                || str_ends_with($lower, '.test.js')
                || str_ends_with($lower, '.test.ts')
                || str_ends_with($lower, '.spec.js')
                || str_ends_with($lower, '.spec.ts')) {
                return true;
            }
        }

        return false;
    }

    private function hasCi(array $paths): bool
    {
        foreach ($paths as $path) {
            $lower = strtolower($path);

            if (str_starts_with($lower, '.github/workflows/')
                || $lower === '.gitlab-ci.yml'
                || str_starts_with($lower, '.circleci/')
                || $lower === '.travis.yml'
                || $lower === 'jenkinsfile'
                || str_starts_with($lower, 'bitbucket-pipelines.yml')) {
                return true;
            }
        }

        return false;
    }

    private function hasReadme(array $paths): bool
    {
        foreach ($paths as $path) {
            if (str_starts_with(strtolower($path), 'readme')) {
                return true;
            }
        }

        return false;
    }
}
