<?php

namespace App\Services\Evaluation;

use App\Models\Repository;
use App\Services\GithubService;
use Illuminate\Support\Facades\Log;

class ArtifactContentFetcher
{
    private const MAX_EXCERPT_LINES = 120;

    private const MAX_README_LINES = 40;

    public function __construct(private GithubService $github) {}

    public function fetch(Repository $repository, array $artifactPaths): array
    {
        [$owner, $repo] = explode('/', $repository->full_name, 2);

        $excerpts = [];

        foreach ($artifactPaths as $path) {
            $content = $this->github->getFileContent($owner, $repo, $path);

            if ($content === null) {
                continue;
            }

            $limit = $this->isReadme($path) ? self::MAX_README_LINES : self::MAX_EXCERPT_LINES;

            $excerpts[$path] = $this->truncateToLines($content, $limit);
        }

        if (empty($excerpts)) {
            Log::warning("Artifact content fetch returned nothing for repo {$repository->full_name}");
        }

        return $excerpts;
    }

    private function isReadme(string $path): bool
    {
        return str(strtolower($path))->startsWith('readme');
    }

    private function truncateToLines(string $content, int $maxLines): string
    {
        $lines = preg_split('/\R/', trim($content));

        if (count($lines) <= $maxLines) {
            return implode("\n", $lines);
        }

        return implode("\n", array_slice($lines, 0, $maxLines));
    }
}
