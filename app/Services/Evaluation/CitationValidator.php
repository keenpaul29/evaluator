<?php

namespace App\Services\Evaluation;

use App\Exceptions\AiEvaluationException;
use Illuminate\Support\Facades\Log;

class CitationValidator
{
    private AiProviderClient $providerClient;

    public function __construct(?AiProviderClient $providerClient = null)
    {
        $this->providerClient = $providerClient ?? new AiProviderClient;
    }

    /**
     * Validate each dimension's evidence against the artifact registry.
     * Citations must exist in the registry (normalized matching) and,
     * for file citations, survive a separate generation-free support check.
     * Dimensions with no citable artifact are flagged insufficient — never
     * rejected. Returns dimensions with evidence normalized.
     *
     * @param  array  $dimensions  parsed AI dimensions
     * @param  array  $repositoryAnalyses  RepositoryAnalysis models (registry)
     */
    public function validate(array $dimensions, array $repositoryAnalyses): array
    {
        $registry = $this->buildRegistry($repositoryAnalyses);

        foreach ($dimensions as &$dim) {
            $dim['evidence'] = $this->classifyEvidence($dim['evidence'] ?? []);
        }
        unset($dim);

        if ($registry['empty']) {
            return $dimensions;
        }

        $candidates = [];
        foreach ($dimensions as $index => $dim) {
            $citations = array_filter(
                $dim['evidence'] ?? [],
                fn ($e) => is_array($e) && isset($e['file_path']) && ! isset($e['insufficient'])
            );

            if (empty($citations)) {
                continue;
            }

            $verified = array_values(array_filter(
                array_map(fn ($e) => $this->verifyExistence($e, $registry), $citations)
            ));

            if (empty($verified)) {
                $dimensions[$index]['evidence'] = [['insufficient' => true]];

                continue;
            }

            $dimensions[$index]['evidence'] = $verified;
            $candidates[$index] = $verified;
        }

        if (empty($candidates)) {
            return $dimensions;
        }

        $supportResults = $this->supportCheck($dimensions, $candidates, $registry);

        if ($supportResults === null) {
            foreach (array_keys($candidates) as $index) {
                $dimensions[$index]['evidence'] = [['insufficient' => true]];
            }

            return $dimensions;
        }

        foreach ($candidates as $index => $citations) {
            $kept = array_values(array_filter($citations, function ($citation) use ($supportResults) {
                $needle = $this->normalizePath($citation['file_path'] ?? '');
                $citationSha = strtolower((string) ($citation['commit_sha'] ?? ''));
                $citationShort = $citationSha === '' ? '' : substr($citationSha, 0, 7);

                foreach ($supportResults as $supported) {
                    $supportedSha = strtolower((string) $supported);
                    $supportedShort = $supportedSha === '' ? '' : substr($supportedSha, 0, 7);

                    if ($needle !== '') {
                        $haystack = $this->normalizePath($supported);

                        if ($haystack === $needle || str_starts_with($haystack, $needle.'/')) {
                            return true;
                        }
                    }

                    if ($citationShort !== '' && $supportedShort !== '' && $supportedShort === $citationShort) {
                        return true;
                    }
                }

                return false;
            }));

            $dimensions[$index]['evidence'] = empty($kept)
                ? [['insufficient' => true]]
                : $kept;
        }

        return $dimensions;
    }

    /**
     * Support check: a separate generation-free AI pass whose only job is
     * claim-vs-artifact support-checking over the registry excerpt. The
     * prompt is narrowed to the cited artifacts ("re-prompt once with a
     * narrowed artifact list"). Returns the list of supported file paths,
     * or null when the AI pass failed through the whole fallback chain.
     *
     * @param  array  $dimensions  dimension index => dimension
     * @param  array  $candidates  dimension index => verified citations
     */
    private function supportCheck(array $dimensions, array $candidates, array $registry): ?array
    {
        $lines = [];

        foreach ($candidates as $index => $citations) {
            $dim = $dimensions[$index];
            $refs = array_map(function ($e) {
                $path = isset($e['file_path']) ? $this->normalizePath($e['file_path']) : '';

                if ($path !== '') {
                    return $path;
                }

                return isset($e['commit_sha']) ? substr(strtolower($e['commit_sha']), 0, 7) : '';
            }, $citations);

            $lines[] = "- claim: {$dim['dimension']} — {$dim['justification']}\n  cited artifacts: ".implode(', ', array_values(array_filter($refs)));
        }

        $artifacts = '';
        foreach ($registry['commits'] as $line) {
            $artifacts .= "\n- COMMIT {$line}";
        }

        foreach ($registry['excerpts'] as $path => $content) {
            $artifacts .= "\n## FILE {$path}\n".substr($content, 0, 400);
        }

        $claimsBlock = implode("\n", $lines);

        $prompt = <<<EOT
        You verify whether cited repository artifacts actually SUPPORT an evaluator's claims.

        ARTIFACTS (the only artifacts in scope):
        {$artifacts}

        CLAIMS TO CHECK:
        {$claimsBlock}

        For each claim, decide for every cited file or commit whether its content genuinely supports the claim. An artifact that merely exists but does not support the claim is a FAILED citation.
        Respond with JSON only: {"supported": ["path/of/supported/file"]} where each entry is a file path or a commit SHA (full or 7-char). List only artifacts whose content supports the claim. If no artifact supports a claim, omit it.
        EOT;

        try {
            $response = $this->providerClient->callWithFallback($prompt);
            $decoded = $this->decodeJson($response);

            if (! is_array($decoded)) {
                Log::info('supportCheck raw', ['response' => $response]);
                throw new AiEvaluationException('Invalid support-check response');
            }

            if (isset($decoded['supported']) && is_array($decoded['supported'])) {
                return array_values($decoded['supported']);
            }

            $flattened = [];
            foreach ($decoded as $dimension => $entries) {
                if (is_array($entries)) {
                    foreach ($entries as $entry) {
                        if (is_string($entry)) {
                            $flattened[] = $entry;
                        }
                    }
                }
            }

            if (empty($flattened)) {
                Log::info('supportCheck raw', ['response' => $response]);
                throw new AiEvaluationException('Invalid support-check response');
            }

            return array_values(array_unique($flattened));
        } catch (AiEvaluationException $e) {
            Log::warning('Citation support check failed for all AI providers', [
                'message' => $e->getMessage(),
                'response' => substr($response ?? '', 0, 500),
            ]);

            return null;
        }
    }

    private function decodeJson(string $response): ?array
    {
        $decoded = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $cleaned = preg_replace('/```(?:json|JSON)?\s*/', '', trim($response));
        $cleaned = preg_replace('/```\s*/', '', $cleaned);
        $decoded = json_decode(trim($cleaned), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $this->decodeConcatenated($cleaned);
    }

    /**
     * Models sometimes emit a stream of sibling JSON objects instead of one
     * object. json_decode rejects that as trailing data, which would discard a
     * perfectly usable support-check answer and collapse the verdict to
     * insufficient_data. Recover by decoding each top-level object and merging
     * list values under the same key.
     */
    private function decodeConcatenated(string $text): ?array
    {
        $merged = [];
        $found = false;
        $length = strlen($text);
        $depth = 0;
        $start = null;
        $inString = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{') {
                if ($depth === 0) {
                    $start = $i;
                }
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0 && $start !== null) {
                    $piece = json_decode(substr($text, $start, $i - $start + 1), true);

                    if (is_array($piece)) {
                        $found = true;

                        foreach ($piece as $key => $value) {
                            $merged[$key] = isset($merged[$key]) && is_array($merged[$key]) && is_array($value)
                                ? array_merge($merged[$key], $value)
                                : $value;
                        }
                    }

                    $start = null;
                } elseif ($depth < 0) {
                    $depth = 0;
                }
            }
        }

        return $found ? $merged : null;
    }

    /**
     * Existence check: normalized path matching plus commit SHA resolution.
     * A "directory citation" (no exact file match) resolves to any registry
     * file in that subtree. Path and (when provided) commit SHA must BOTH
     * trace to artifacts the prompt was actually fed. Returns the citation
     * unchanged when it exists, null otherwise.
     */
    private function verifyExistence(array $citation, array $registry): ?array
    {
        $path = $this->normalizePath($citation['file_path'] ?? '');
        $sha = strtolower((string) ($citation['commit_sha'] ?? ''));

        $pathExists = $path === '' ? null : $this->pathExists($path, $registry['paths']);
        $shaExists = $sha === '' ? null : $this->shaExists($sha, $registry['shas']);

        if ($pathExists === false || $shaExists === false) {
            return null;
        }

        if ($pathExists === null && $shaExists === null) {
            return null;
        }

        if (is_string($pathExists)) {
            $citation['file_path'] = $pathExists;
        }

        return $citation;
    }

    private function pathExists(string $normalizedPath, array $registryPaths): bool|string
    {
        foreach ($registryPaths as $registryPath) {
            $normalizedRegistryPath = $this->normalizePath($registryPath);

            if ($normalizedRegistryPath === $normalizedPath) {
                return $registryPath;
            }

            if (str_starts_with($normalizedRegistryPath, $normalizedPath.'/')) {
                return true;
            }
        }

        return false;
    }

    private function shaExists(string $sha, array $registryShas): bool
    {
        $sha = strtolower($sha);
        $short = substr($sha, 0, 7);

        foreach ($registryShas as $registrySha) {
            if ($sha === $registrySha || ($short !== '' && str_starts_with($registrySha, $short))) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, './');

        return strtolower(rtrim($path, '/'));
    }

    private function classifyEvidence($evidence): array
    {
        if ($evidence === [['insufficient' => true]] || ($evidence === ['insufficient' => true])) {
            return [['insufficient' => true]];
        }

        if (! is_array($evidence)) {
            return [['insufficient' => true]];
        }

        if (isset($evidence['file_path'], $evidence['commit_sha'])) {
            return [$evidence];
        }

        $normalized = [];
        foreach ($evidence as $item) {
            if (is_array($item) && (isset($item['file_path']) || isset($item['commit_sha']))) {
                $normalized[] = $item;

                continue;
            }

            return [['insufficient' => true]];
        }

        return $normalized;
    }

    /**
     * Build the in-memory artifact registry from RepositoryAnalysis models.
     * Registry inputs are exactly what the prompt builder was fed.
     */
    private function buildRegistry(array $repositoryAnalyses): array
    {
        $paths = [];
        $shas = [];
        $excerpts = [];
        $commits = [];
        $empty = true;

        foreach ($repositoryAnalyses as $analysis) {
            foreach ($analysis->artifact_file_paths ?? [] as $path) {
                $paths[] = $path;
            }

            foreach ($analysis->commit_samples ?? [] as $commit) {
                $sha = strtolower((string) ($commit['sha'] ?? ''));

                if ($sha !== '') {
                    $shas[] = $sha;
                    $commits[] = substr($sha, 0, 7).' | '.($commit['author_date'] ?? '').' | '.($commit['subject'] ?? '');
                }
            }

            foreach ($analysis->artifact_excerpts ?? [] as $path => $content) {
                $excerpts[$this->normalizePath($path)] = $content;
                $empty = false;
            }

            if (! empty($analysis->artifact_file_paths) || ! empty($analysis->commit_samples)) {
                $empty = false;
            }
        }

        return [
            'paths' => array_values(array_unique($paths)),
            'shas' => array_values(array_unique($shas)),
            'excerpts' => $excerpts,
            'commits' => $commits,
            'empty' => $empty,
        ];
    }
}
