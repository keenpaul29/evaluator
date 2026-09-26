<?php

namespace App\Services\Evaluation;

class ArtifactSelector
{
    private const MAX_FILE_BYTES = 200 * 1024;

    private const PRIORITY_DOCS = 3;

    private const PRIORITY_MANIFEST = 2;

    private const PRIORITY_TESTS = 1;

    private const PRIORITY_CORE = 0;

    public function select(array $tree, int $topN = 10): array
    {
        $blobs = collect($tree)->filter(fn ($item) => ($item['type'] ?? null) === 'blob');

        return $blobs
            ->filter(fn ($item) => ! $this->isOversized($item))
            ->map(fn ($item) => [
                'path' => $item['path'],
                'depth' => substr_count($item['path'], '/'),
                'priority' => $this->priority($item['path']),
            ])
            ->sortBy([
                ['priority', 'desc'],
                ['depth', 'asc'],
                ['path', 'asc'],
            ])
            ->pluck('path')
            ->take($topN)
            ->values()
            ->all();
    }

    private function isOversized(array $item): bool
    {
        return isset($item['size']) && (int) $item['size'] > self::MAX_FILE_BYTES;
    }

    private function priority(string $path): int
    {
        $lower = strtolower($path);

        if ($this->isDocs($lower)) {
            return self::PRIORITY_DOCS;
        }

        if ($this->isManifest($path)) {
            return self::PRIORITY_MANIFEST;
        }

        if ($this->isTest($lower)) {
            return self::PRIORITY_TESTS;
        }

        return self::PRIORITY_CORE;
    }

    private function isDocs(string $path): bool
    {
        if (str($path)->startsWith('readme')) {
            return true;
        }

        if (str($path)->startsWith(['docs/', 'documentation/', 'guide/', 'wiki/'])) {
            return true;
        }

        if (str($path)->contains('contributing')) {
            return true;
        }

        if (str($path)->contains('changelog')) {
            return true;
        }

        return in_array($path, [
            'license',
            'license.md',
            'licence.md',
            'copying',
            'code_of_conduct.md',
        ], true);
    }

    private function isManifest(string $path): bool
    {
        return in_array(strtolower($path), [
            'composer.json',
            'package.json',
            'pyproject.toml',
            'pom.xml',
            'build.gradle',
            'cargo.toml',
            'go.mod',
            'gemfile',
            'requirements.txt',
            'dockerfile',
            'docker-compose.yml',
            '.github/workflows/ci.yml',
            '.gitlab-ci.yml',
            'jenkinsfile',
        ], true);
    }

    private function isTest(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (str($path)->contains(['tests/', 'test/', '__tests__/'])) {
            return true;
        }

        if (str($path)->contains('spec/')) {
            return true;
        }

        if (str($path)->endsWith(['_test.php', '.test.php', '.test.js', '.test.ts', '.spec.js', '.spec.ts'])) {
            return true;
        }

        return false;
    }
}
