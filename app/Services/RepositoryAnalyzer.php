<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\RepositoryAnalysis;
use Illuminate\Support\Facades\Log;

class RepositoryAnalyzer
{
    private GithubService $github;

    public function __construct(GithubService $github)
    {
        $this->github = $github;
    }

    public function analyze(Repository $repository): RepositoryAnalysis
    {
        $owner = explode('/', $repository->full_name)[0];
        $repoName = $repository->name;

        $tree = $this->github->getRepoTree($owner, $repoName);
        $languages = $this->github->getRepoLanguages($owner, $repoName);
        $commits = $this->github->getRepoCommits($owner, $repoName, 100);

        $files = collect($tree)->filter(fn ($item) => $item['type'] === 'blob');
        $codeFiles = $files->filter(fn ($item) => $this->isCodeFile($item['path']));
        $totalLines = $this->estimateLines($codeFiles);

        $hasReadme = $files->contains(fn ($item) => str($item['path'])->lower()->startsWith('readme'));
        $hasTests = $this->detectTests($files);
        $hasCi = $this->detectCiConfig($files);
        $hasDocs = $this->detectDocumentation($files);

        $commitFrequencyScore = $this->scoreCommitFrequency($commits);
        $commitQualityScore = $this->scoreCommitQuality($commits);
        $complexity = $this->estimateComplexity($codeFiles, $languages);
        $patterns = $this->detectArchitecturalPatterns($files, $languages);

        return RepositoryAnalysis::updateOrCreate(
            ['repository_id' => $repository->id],
            [
                'total_files_analyzed' => $codeFiles->count(),
                'total_lines_analyzed' => $totalLines,
                'primary_languages' => $languages,
                'has_readme' => $hasReadme,
                'has_tests' => $hasTests,
                'has_ci_config' => $hasCi,
                'has_documentation' => $hasDocs,
                'commit_frequency_score' => $commitFrequencyScore,
                'avg_commit_quality_score' => $commitQualityScore,
                'code_complexity_estimate' => $complexity,
                'architectural_patterns' => $patterns,
                'dependencies_analysis' => $this->analyzeDependencies($files),
                'analyzed_at' => now(),
            ]
        );
    }

    private function isCodeFile(string $path): bool
    {
        $codeExtensions = [
            'php', 'py', 'js', 'ts', 'jsx', 'tsx', 'vue', 'rb', 'go', 'rs', 'java',
            'c', 'cpp', 'h', 'cs', 'swift', 'kt', 'scala', 'clj', 'ex', 'exs',
            'html', 'css', 'scss', 'sass', 'less', 'sql', 'sh', 'bash', 'yaml', 'yml',
            'json', 'xml', 'toml', 'ini', 'env', 'blade.php', 'twig',
        ];

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($ext), $codeExtensions);
    }

    private function estimateLines($codeFiles): int
    {
        $totalLines = 0;
        $sampledFiles = $codeFiles->take(50);

        foreach ($sampledFiles as $file) {
            $owner = explode('/', $file['path'])[0] ?? '';
            $content = $this->github->getFileContent(
                $owner,
                pathinfo($file['path'], PATHINFO_FILENAME),
                $file['path']
            );

            if ($content) {
                $totalLines += substr_count($content, "\n") + 1;
            }
        }

        $scaleFactor = $codeFiles->count() > 50 ? $codeFiles->count() / 50 : 1;

        return (int) ($totalLines * $scaleFactor);
    }

    private function detectTests($files): bool
    {
        $testPatterns = ['test', 'spec', 'tests', '__tests__', 'phpunit', 'pytest', 'jest'];

        return $files->contains(function ($file) use ($testPatterns) {
            $path = strtolower($file['path']);

            return collect($testPatterns)->contains(fn ($pattern) => str($path)->contains($pattern))
                || str($path)->endsWith('_test.php')
                || str($path)->endsWith('.test.js')
                || str($path)->endsWith('.test.ts')
                || str($path)->endsWith('.spec.js')
                || str($path)->endsWith('.spec.ts');
        });
    }

    private function detectCiConfig($files): bool
    {
        $ciPaths = [
            '.github/workflows/',
            '.gitlab-ci.yml',
            '.circleci/',
            '.travis.yml',
            'Jenkinsfile',
            'bitbucket-pipelines.yml',
            '.drone.yml',
        ];

        return $files->contains(function ($file) use ($ciPaths) {
            return collect($ciPaths)->contains(fn ($path) => str($file['path'])->startsWith($path))
                || $file['path'] === '.gitlab-ci.yml'
                || $file['path'] === 'Jenkinsfile'
                || $file['path'] === '.travis.yml';
        });
    }

    private function detectDocumentation($files): bool
    {
        $docPatterns = ['docs/', 'documentation/', 'wiki/', 'guide/'];

        return $files->contains(function ($file) use ($docPatterns) {
            return collect($docPatterns)->contains(fn ($p) => str($file['path'])->startsWith($p))
                || strtolower(pathinfo($file['path'], PATHINFO_FILENAME)) === 'contributing'
                || strtolower(pathinfo($file['path'], PATHINFO_FILENAME)) === 'changelog';
        });
    }

    private function scoreCommitFrequency(array $commits): float
    {
        if (empty($commits)) {
            return 0.0;
        }

        $dates = array_map(fn ($c) => $c['commit']['author']['date'] ?? '', $commits);
        $dates = array_filter($dates);
        $uniqueDates = array_unique(array_map(fn ($d) => substr($d, 0, 10), $dates));

        $ratio = count($uniqueDates) / max(count($commits), 1);

        return min(10.0, round($ratio * 10, 1));
    }

    private function scoreCommitQuality(array $commits): float
    {
        if (empty($commits)) {
            return 0.0;
        }

        $goodMessages = 0;

        foreach ($commits as $commit) {
            $message = $commit['commit']['message'] ?? '';
            $firstLine = explode("\n", $message)[0];

            if (strlen($firstLine) > 10 && strlen($firstLine) < 72) {
                $goodMessages++;
            }
        }

        return min(10.0, round(($goodMessages / count($commits)) * 10, 1));
    }

    private function estimateComplexity($codeFiles, array $languages): string
    {
        $fileCount = $codeFiles->count();
        $languageCount = count($languages);

        if ($fileCount > 100 || $languageCount > 5) {
            return 'high';
        }

        if ($fileCount > 30 || $languageCount > 3) {
            return 'medium';
        }

        return 'low';
    }

    private function detectArchitecturalPatterns($files, array $languages): array
    {
        $patterns = [];
        $paths = $files->pluck('path')->toArray();

        if (collect($paths)->contains(fn ($p) => str($p)->startsWith('app/Http/Controllers/'))) {
            $patterns[] = 'MVC';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->contains('Service'))) {
            $patterns[] = 'Service Layer';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->contains('Repository'))) {
            $patterns[] = 'Repository Pattern';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->contains('Middleware'))) {
            $patterns[] = 'Middleware';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->startsWith('resources/js/components/'))) {
            $patterns[] = 'Component-based UI';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->startsWith('database/migrations/'))) {
            $patterns[] = 'Migration-based schema';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->contains('docker') || str($p)->contains('Docker'))) {
            $patterns[] = 'Containerized';
        }

        if (collect($paths)->contains(fn ($p) => str($p)->contains('.github/workflows'))) {
            $patterns[] = 'CI/CD';
        }

        return $patterns;
    }

    private function analyzeDependencies($files): array
    {
        $deps = [];

        $composer = $files->first(fn ($f) => $f['path'] === 'composer.json');
        if ($composer) {
            $deps['php'] = 'composer.json detected';
        }

        $packageJson = $files->first(fn ($f) => $f['path'] === 'package.json');
        if ($packageJson) {
            $deps['javascript'] = 'package.json detected';
        }

        $requirements = $files->first(fn ($f) => $f['path'] === 'requirements.txt');
        if ($requirements) {
            $deps['python'] = 'requirements.txt detected';
        }

        $mixManifest = $files->first(fn ($f) => $f['path'] === 'mix-manifest.json');
        $viteManifest = $files->first(fn ($f) => str($f['path'])->startsWith('public/build/'));
        if ($mixManifest || $viteManifest) {
            $deps['build_tool'] = $viteManifest ? 'Vite' : 'Laravel Mix';
        }

        return $deps;
    }
}
