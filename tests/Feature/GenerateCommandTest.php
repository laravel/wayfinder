<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function Illuminate\Filesystem\join_paths;

class GenerateCommandTest extends TestCase
{
    private string $tempPath;

    private Filesystem $files;

    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->rootPath = realpath(join_paths(__DIR__, '..', '..'));
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-prune-'.uniqid());

        $envExample = join_paths($this->rootPath, 'workbench', '.env.example');
        $envFile = join_paths($this->rootPath, 'workbench', '.env');

        if ($this->files->exists($envExample) && ! $this->files->exists($envFile)) {
            $this->files->copy($envExample, $envFile);
        }
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    private function generate(): void
    {
        $process = new Process([
            join_paths($this->rootPath, 'vendor', 'bin', 'testbench'),
            'wayfinder:generate',
            '--path='.$this->tempPath,
            '--app-path='.join_paths($this->rootPath, 'workbench', 'app'),
            '--base-path='.join_paths($this->rootPath, 'workbench'),
            '--fresh',
        ], $this->rootPath);

        $process->setTimeout(60);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            'wayfinder:generate failed: '.$process->getErrorOutput().$process->getOutput()
        );
    }

    public function test_generated_files_exist_after_generate(): void
    {
        $this->generate();

        $this->assertDirectoryExists($this->tempPath);
        $this->assertFileExists(join_paths($this->tempPath, 'index.ts'));
        $this->assertNotEmpty($this->files->allFiles($this->tempPath));
    }

    public function test_stale_files_are_removed_while_current_files_are_kept(): void
    {
        $this->generate();

        $current = collect($this->files->allFiles($this->tempPath))
            ->map(fn ($file) => $file->getPathname());

        $this->assertNotEmpty($current);

        $stale = join_paths($this->tempPath, 'definitely-not-a-real-route.ts');
        $this->files->put($stale, '// stale');
        $this->assertFileExists($stale);

        $this->generate();

        $this->assertFileDoesNotExist($stale);
        $current->each(fn ($path) => $this->assertFileExists($path));
    }

    public function test_empty_directories_are_pruned(): void
    {
        $this->generate();

        $orphanDir = join_paths($this->tempPath, 'orphan-dir');
        $this->files->ensureDirectoryExists($orphanDir);
        $this->files->put(join_paths($orphanDir, 'thing.ts'), '// stale');

        $this->generate();

        $this->assertDirectoryDoesNotExist($orphanDir);
    }

    public function test_helper_index_is_skipped_when_contents_match(): void
    {
        $this->generate();

        $destination = join_paths($this->tempPath, 'index.ts');
        $beforeMtime = filemtime($destination);

        clearstatcache(true, $destination);
        sleep(1);

        $this->generate();

        clearstatcache(true, $destination);
        $this->assertSame($beforeMtime, filemtime($destination));
    }

    public function test_unchanged_generated_files_are_not_rewritten(): void
    {
        $this->generate();

        $sample = collect($this->files->allFiles($this->tempPath))
            ->map(fn ($file) => $file->getPathname())
            ->first(fn ($path) => str_ends_with($path, '.ts') && ! str_ends_with($path, 'index.ts'));

        $this->assertNotNull($sample, 'expected at least one non-index generated file');

        $beforeMtime = filemtime($sample);

        clearstatcache(true, $sample);
        sleep(1);

        $this->generate();

        clearstatcache(true, $sample);
        $this->assertSame($beforeMtime, filemtime($sample));
    }
}
