<?php

namespace Tests\Unit;

use App\Services\Evaluation\ArtifactSelector;
use Tests\TestCase;

class ArtifactSelectorTest extends TestCase
{
    public function test_select_ranks_docs_before_core_code(): void
    {
        $selector = new ArtifactSelector;

        $tree = [
            ['path' => 'app/Models/User.php', 'type' => 'blob'],
            ['path' => 'README.md', 'type' => 'blob', 'size' => 2048],
            ['path' => 'app/Http/Controllers/Controller.php', 'type' => 'blob'],
            ['path' => 'docs/architecture.md', 'type' => 'blob', 'size' => 4096],
        ];

        $paths = $selector->select($tree);

        $this->assertSame(['README.md', 'docs/architecture.md', 'app/Models/User.php', 'app/Http/Controllers/Controller.php'], $paths);
    }

    public function test_select_ties_broken_by_path_depth_asc_then_name(): void
    {
        $selector = new ArtifactSelector;

        $tree = [
            ['path' => 'b.php', 'type' => 'blob', 'size' => 100],
            ['path' => 'deep/nested/dir/z.php', 'type' => 'blob', 'size' => 100],
            ['path' => 'deep/a.php', 'type' => 'blob', 'size' => 100],
            ['path' => 'a.php', 'type' => 'blob', 'size' => 100],
        ];

        $paths = $selector->select($tree);

        $this->assertSame(['a.php', 'b.php', 'deep/a.php', 'deep/nested/dir/z.php'], $paths);
    }

    public function test_select_clamps_oversized_files_out(): void
    {
        $selector = new ArtifactSelector;

        $tree = [
            ['path' => 'README.md', 'type' => 'blob', 'size' => 100],
            ['path' => 'vendor/bundle.min.js', 'type' => 'blob', 'size' => 300 * 1024],
        ];

        $paths = $selector->select($tree);

        $this->assertSame(['README.md'], $paths);
    }

    public function test_select_respects_top_n_limit(): void
    {
        $selector = new ArtifactSelector;

        $tree = [];
        for ($i = 0; $i < 15; $i++) {
            $tree[] = ['path' => "file{$i}.php", 'type' => 'blob', 'size' => 100];
        }

        $this->assertCount(10, $selector->select($tree));
    }

    public function test_select_ignores_non_blob_entries(): void
    {
        $selector = new ArtifactSelector;

        $tree = [
            ['path' => 'src', 'type' => 'tree'],
            ['path' => 'README.md', 'type' => 'blob', 'size' => 100],
            ['path' => 'tests/', 'type' => 'tree'],
        ];

        $paths = $selector->select($tree);

        $this->assertSame(['README.md'], $paths);
    }

    public function test_select_handles_missing_size(): void
    {
        $selector = new ArtifactSelector;

        $tree = [
            ['path' => 'README.md', 'type' => 'blob'],
            ['path' => 'src/App.php', 'type' => 'blob'],
        ];

        $paths = $selector->select($tree);

        $this->assertSame(['README.md', 'src/App.php'], $paths);
    }
}
