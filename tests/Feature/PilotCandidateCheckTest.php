<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PilotCandidateCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_passes_artifact_rich_candidate(): void
    {
        $this->fakeGitHub([
            'user' => [
                'login' => 'candidate',
                'name' => 'Candidate Name',
                'email' => 'candidate@example.com',
            ],
            'repos' => [
                $this->repo('candidate/backend', false),
                $this->repo('candidate/game', false),
                $this->repo('candidate/fork-of-something', true),
            ],
            'trees' => [
                'candidate/backend' => [
                    ['type' => 'blob', 'path' => 'README.md'],
                    ['type' => 'blob', 'path' => 'app/GameService.php'],
                    ['type' => 'blob', 'path' => 'tests/GameServiceTest.php'],
                    ['type' => 'blob', 'path' => '.github/workflows/ci.yml'],
                ],
                'candidate/game' => [
                    ['type' => 'blob', 'path' => 'README.md'],
                    ['type' => 'blob', 'path' => 'src/main.py'],
                ],
            ],
        ]);

        $this->artisan('pilot:candidate-check candidate')
            ->expectsOutputToContain('PASS: candidate clears the artifact-rich threshold.')
            ->assertExitCode(0);
    }

    public function test_command_fails_thin_candidate(): void
    {
        $this->fakeGitHub([
            'user' => [
                'login' => 'thin',
                'name' => 'Thin Candidate',
                'email' => null,
            ],
            'repos' => [
                $this->repo('thin/single', false),
            ],
            'trees' => [
                'thin/single' => [
                    ['type' => 'blob', 'path' => 'main.py'],
                ],
            ],
        ]);

        $this->artisan('pilot:candidate-check thin')
            ->expectsOutputToContain('FAIL: candidate does not clear the threshold. Pick the contingency candidate.')
            ->assertExitCode(1);
    }

    private function fakeGitHub(array $spec): void
    {
        $repoData = collect($spec['repos'])->keyBy('full_name');
        $treeData = collect($spec['trees']);

        Http::fake([
            'api.github.com/users/*/repos*' => Http::response(array_values($spec['repos'])),
            'api.github.com/users/*' => Http::response($spec['user']),
            'api.github.com/repos/*/commits*' => Http::response($this->authoredCommits()),
            'api.github.com/repos/*/git/trees/HEAD*' => function ($request) use ($treeData) {
                $pattern = '#api.github.com/repos/([^/]+)/([^/]+)/git/trees/HEAD#';
                preg_match($pattern, (string) $request->url(), $m);

                return Http::response([
                    'tree' => $treeData->get("{$m[1]}/{$m[2]}") ?? [],
                    'truncated' => false,
                ]);
            },
        ]);
    }

    private function repo(string $fullName, bool $fork): array
    {
        [$owner, $name] = explode('/', $fullName);

        return [
            'id' => crc32($fullName),
            'name' => $name,
            'full_name' => $fullName,
            'owner' => ['login' => $owner],
            'fork' => $fork,
        ];
    }

    private function authoredCommits(): array
    {
        return [
            [
                'sha' => 'abc123',
                'author' => ['login' => 'candidate'],
                'commit' => ['author' => ['name' => 'Candidate Name', 'email' => 'candidate@example.com']],
            ],
            [
                'sha' => 'def456',
                'author' => ['login' => 'candidate'],
                'commit' => ['author' => ['name' => 'Candidate Name', 'email' => 'candidate@example.com']],
            ],
        ];
    }
}
