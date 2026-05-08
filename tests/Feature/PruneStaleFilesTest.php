<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class PruneStaleFilesTest extends TestCase
{
    private string $tempPath;

    private Filesystem $files;

    protected function getPackageProviders($app): array
    {
        return [WayfinderServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-prune-'.uniqid());

        Route::get('/prune-test/alpha', fn () => '')->name('prune.test.alpha');
        Route::get('/prune-test/beta', fn () => '')->name('prune.test.beta');
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    private function generate(): void
    {
        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--skip-actions' => true,
        ])->assertSuccessful();
    }

    public function test_generated_files_exist_after_generate(): void
    {
        $this->generate();

        $routes = join_paths($this->tempPath, 'routes');

        $this->assertDirectoryExists($routes);
        $this->assertNotEmpty($this->files->allFiles($routes));
        $this->assertFileExists(join_paths($routes, 'prune', 'test', 'index.ts'));
    }

    public function test_stale_files_are_removed_while_current_files_are_kept(): void
    {
        $this->generate();

        $current = collect($this->files->allFiles(join_paths($this->tempPath, 'routes')))
            ->map(fn ($file) => $file->getPathname());

        $this->assertNotEmpty($current);

        $stale = join_paths($this->tempPath, 'routes', 'definitely-not-a-real-route.ts');
        $this->files->put($stale, '// stale');
        $this->assertFileExists($stale);

        $this->generate();

        $this->assertFileDoesNotExist($stale);
        $current->each(fn ($path) => $this->assertFileExists($path));
    }

    public function test_empty_directories_are_pruned(): void
    {
        $this->generate();

        $sibling = join_paths($this->tempPath, 'routes', 'prune', 'test', 'index.ts');
        $this->assertFileExists($sibling);

        $orphanDir = join_paths($this->tempPath, 'routes', 'orphan-dir');
        $this->files->ensureDirectoryExists($orphanDir);
        $this->files->put(join_paths($orphanDir, 'thing.ts'), '// stale');

        $this->generate();

        $this->assertDirectoryDoesNotExist($orphanDir);
        $this->assertFileExists($sibling);
    }
}
