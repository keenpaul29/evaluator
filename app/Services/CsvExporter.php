<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    private const HEADERS = ['name', 'email', 'github_username', 'repos', 'status', 'overall_score', 'verdict'];

    public function exportCandidates(Collection $candidates, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($candidates) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, self::HEADERS, ',', '"', '\\');

            foreach ($candidates as $candidate) {
                fputcsv($handle, $this->row($candidate), ',', '"', '\\');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function row(Candidate $candidate): array
    {
        $repos = $candidate->repositories
            ->sortBy('full_name')
            ->map(fn ($repo) => 'https://github.com/'.$repo->full_name)
            ->implode(',');

        return [
            $candidate->name,
            $candidate->email ?? '',
            $candidate->github_username ?? '',
            $repos,
            $candidate->status->value,
            $candidate->evaluation?->overall_score ?? '',
            $candidate->evaluation?->verdict ?? '',
        ];
    }
}
