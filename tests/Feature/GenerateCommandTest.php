<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Laravel\Ranger\RangerServiceProvider;
use Laravel\Surveyor\SurveyorServiceProvider;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class GenerateCommandTest extends TestCase
{
    use WithWorkbench;

    private string $tempPath;

    private Filesystem $files;

    protected function getPackageProviders($app): array
    {
        return [
            RangerServiceProvider::class,
            SurveyorServiceProvider::class,
            WayfinderServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-prune-'.uniqid());
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    private function generate(): void
    {
        $rootPath = realpath(join_paths(__DIR__, '..', '..'));

        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--app-path' => join_paths($rootPath, 'workbench', 'app'),
            '--base-path' => join_paths($rootPath, 'workbench'),
        ])->assertSuccessful();
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
