<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class RelativeUrlsTest extends TestCase
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
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-relative-'.uniqid());
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    public function test_relative_flag_strips_host_from_domain_routes(): void
    {
        Route::domain('example.test')->get('/admin/users', function () {
            return '';
        })->name('admin.users');

        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--skip-actions' => true,
            '--relative' => true,
        ])->assertSuccessful();

        $content = $this->files->get(join_paths($this->tempPath, 'routes', 'admin', 'index.ts'));

        $this->assertStringContainsString("'/admin/users'", $content);
        $this->assertStringNotContainsString('example.test', $content);
    }

    public function test_without_relative_flag_host_is_included(): void
    {
        Route::domain('example.test')->get('/admin/check', function () {
            return '';
        })->name('admin.check');

        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--skip-actions' => true,
        ])->assertSuccessful();

        $content = $this->files->get(join_paths($this->tempPath, 'routes', 'admin', 'index.ts'));

        $this->assertStringContainsString('example.test', $content);
    }

    public function test_relative_flag_deduplicates_routes_with_same_path_across_domains(): void
    {
        // Simulate multi-tenant central domains: same controller method registered
        // under two different domains but identical path. Without deduplication,
        // the generated TypeScript file would contain two `export const fixedDomain`
        // declarations, which is a compile error.
        Route::domain('app.test')->get('/fixed-domain/{param}', [\App\Http\Controllers\DomainController::class, 'fixedDomain']);
        Route::domain('localhost')->get('/fixed-domain/{param}', [\App\Http\Controllers\DomainController::class, 'fixedDomain']);

        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--skip-routes' => true,
            '--relative' => true,
        ])->assertSuccessful();

        $content = $this->files->get(
            join_paths($this->tempPath, 'actions', 'App', 'Http', 'Controllers', 'DomainController.ts')
        );

        // Should have exactly one `export const fixedDomain` — not two
        $this->assertSame(1, substr_count($content, 'export const fixedDomain'));
        // And the path should be relative (no hard-coded host)
        $this->assertStringNotContainsString('app.test', $content);
        $this->assertStringNotContainsString('//localhost', $content);
    }
}
