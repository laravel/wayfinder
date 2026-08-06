<?php

namespace Tests\Feature;

use App\Http\Middleware\UrlDefaultsMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class MiddlewareUrlDefaultsTest extends TestCase
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
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-middleware-'.uniqid());
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    private function generate(string $directory): string
    {
        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--skip-actions' => true,
        ])->assertSuccessful();

        return $this->files->get(join_paths($this->tempPath, 'routes', $directory, 'index.ts'));
    }

    public function test_url_defaults_are_resolved_from_an_aliased_middleware(): void
    {
        $this->app->afterResolving(Kernel::class, fn ($kernel) => $kernel->setMiddlewareAliases([
            'url-defaults' => UrlDefaultsMiddleware::class,
        ]));

        Route::middleware('url-defaults')->get('/alias-defaults/{locale}', fn () => '')->name('alias.defaults');

        $this->assertStringContainsString("url: '/alias-defaults/{locale?}'", $this->generate('alias'));
    }

    public function test_url_defaults_are_resolved_from_a_middleware_pushed_onto_a_group(): void
    {
        Route::pushMiddlewareToGroup('web', UrlDefaultsMiddleware::class);

        Route::middleware('web')->get('/group-defaults/{locale}', fn () => '')->name('group.defaults');

        $this->assertStringContainsString("url: '/group-defaults/{locale?}'", $this->generate('group'));
    }
}
