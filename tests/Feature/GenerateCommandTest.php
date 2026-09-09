<?php

namespace Tests\Feature;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Laravel\Wayfinder\Console\GenerateCommand;
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

    public function test_generate_completes_under_default_memory_limit(): void
    {
        // The static analysis pass can exceed PHP's default 128M limit on a
        // cold cache (laravel/wayfinder#167). Generation must complete rather
        // than fatal with an out-of-memory error.
        $process = new Process([
            PHP_BINARY,
            '-d',
            'memory_limit=128M',
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
            'wayfinder:generate failed under the default 128M memory limit: '
                .$process->getErrorOutput().$process->getOutput()
        );
        $this->assertFileExists(join_paths($this->tempPath, 'index.ts'));
    }

    public function test_a_capped_memory_limit_is_raised_to_a_bound(): void
    {
        // Not removed: a runaway analysis should still stop with a PHP error
        // rather than being killed by the OS without one.
        $this->assertSame('1536M', $this->limitAfterRaising('256M'));
    }

    public function test_a_memory_limit_above_the_bound_is_left_alone(): void
    {
        $this->assertSame('2048M', $this->limitAfterRaising('2048M'));
    }

    public function test_an_uncapped_memory_limit_is_left_alone(): void
    {
        $this->assertSame('-1', $this->limitAfterRaising('-1'));
    }

    public function test_a_configured_limit_is_used_above_the_bound(): void
    {
        $this->assertSame('4096M', $this->limitAfterRaising('256M', '4096M'));
    }

    public function test_a_configured_limit_is_used_below_the_bound(): void
    {
        // Someone who has named a limit has overridden the bound, not asked to
        // be raised to it.
        $this->assertSame('256M', $this->limitAfterRaising('512M', '256M'));
    }

    public function test_a_configured_limit_can_remove_the_cap(): void
    {
        $this->assertSame('-1', $this->limitAfterRaising('256M', '-1'));
    }

    public function test_a_configured_limit_php_rejects_falls_back_to_the_bound(): void
    {
        $this->assertSame('1536M', $this->limitAfterRaising('256M', 'not-a-size'));
    }

    public function test_a_rejected_configured_limit_is_reported_and_generation_continues(): void
    {
        $process = new Process([
            PHP_BINARY,
            '-d',
            'memory_limit=128M',
            join_paths($this->rootPath, 'vendor', 'bin', 'testbench'),
            'wayfinder:generate',
            '--path='.$this->tempPath,
            '--app-path='.join_paths($this->rootPath, 'workbench', 'app'),
            '--base-path='.join_paths($this->rootPath, 'workbench'),
            '--fresh',
        ], $this->rootPath, ['WAYFINDER_MEMORY_LIMIT' => 'not-a-size']);

        $process->setTimeout(60);
        $process->run();

        $output = $process->getOutput().$process->getErrorOutput();

        $this->assertTrue($process->isSuccessful(), 'wayfinder:generate failed: '.$output);
        $this->assertStringContainsString('not-a-size', $output);
        $this->assertFileExists(join_paths($this->tempPath, 'index.ts'));
    }

    /**
     * Run raiseMemoryLimit() against a starting limit, optionally with one
     * configured, and report where it left it. Every value has to be above
     * what the suite is already using, or PHP refuses it.
     */
    private function limitAfterRaising(string $start, ?string $configured = null): string
    {
        $original = ini_get('memory_limit');

        try {
            ini_set('memory_limit', $start);

            $command = (new \ReflectionClass(GenerateCommand::class))->newInstanceWithoutConstructor();

            (new \ReflectionProperty($command, 'config'))->setValue(
                $command,
                new Repository(['wayfinder' => ['memory_limit' => $configured]]),
            );

            (new \ReflectionMethod($command, 'raiseMemoryLimit'))->invoke($command);

            return (string) ini_get('memory_limit');
        } finally {
            ini_set('memory_limit', $original);
        }
    }

    public function test_noop_regenerate_does_not_touch_any_file(): void
    {
        $this->generate();

        $before = collect($this->files->allFiles($this->tempPath))
            ->mapWithKeys(fn ($file) => [$file->getPathname() => filemtime($file->getPathname())]);

        $this->assertNotEmpty($before);

        clearstatcache();
        sleep(1);

        $this->generate();

        clearstatcache();
        $after = collect($this->files->allFiles($this->tempPath))
            ->mapWithKeys(fn ($file) => [$file->getPathname() => filemtime($file->getPathname())]);

        $this->assertSame($before->keys()->sort()->values()->all(), $after->keys()->sort()->values()->all());

        $changed = $before->filter(fn ($mtime, $path) => $after->get($path) !== $mtime);

        $this->assertEmpty(
            $changed,
            'expected no files to be rewritten on a no-op regen, got: '.$changed->keys()->implode(', ')
        );
    }
}
