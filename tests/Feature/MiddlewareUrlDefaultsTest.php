<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalUrlDefaultsMiddleware;
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
        // Registering via afterResolving mirrors withMiddleware(): the alias only reaches
        // the router when the HTTP kernel resolves, which is what console never does.
        $this->app->afterResolving(Kernel::class, fn ($kernel) => $kernel->setMiddlewareAliases([
            'url-defaults' => UrlDefaultsMiddleware::class,
        ]));

        Route::middleware('url-defaults')->get('/alias-defaults/{locale}', fn () => '')->name('alias.defaults');

        $this->assertStringContainsString("url: '/alias-defaults/{locale?}'", $this->generate('alias'));
    }

    public function test_url_defaults_are_resolved_from_a_kernel_middleware_group(): void
    {
        $this->app->afterResolving(Kernel::class, fn ($kernel) => $kernel->setMiddlewareGroups([
            'tenant' => [UrlDefaultsMiddleware::class],
        ]));

        Route::middleware('tenant')->get('/kernel-group-defaults/{locale}', fn () => '')->name('kernel.defaults');

        $this->assertStringContainsString("url: '/kernel-group-defaults/{locale?}'", $this->generate('kernel'));
    }

    public function test_url_defaults_are_resolved_from_global_middleware(): void
    {
        // Global middleware never reaches the router, so this only works if the defaults
        // are read off the kernel itself.
        $this->app->afterResolving(Kernel::class, fn ($kernel) => $kernel->setGlobalMiddleware([
            UrlDefaultsMiddleware::class,
        ]));

        Route::get('/global-defaults/{locale}', fn () => '')->name('global.defaults');

        $this->assertStringContainsString("url: '/global-defaults/{locale?}'", $this->generate('global'));
    }

    public function test_url_defaults_are_resolved_from_a_middleware_pushed_onto_a_group(): void
    {
        Route::pushMiddlewareToGroup('web', UrlDefaultsMiddleware::class);

        Route::middleware('web')->get('/group-defaults/{locale}', fn () => '')->name('group.defaults');

        $this->assertStringContainsString("url: '/group-defaults/{locale?}'", $this->generate('group'));
    }

    public function test_route_middleware_defaults_win_over_global_middleware_defaults(): void
    {
        $this->app->afterResolving(Kernel::class, fn ($kernel) => $kernel->setGlobalMiddleware([
            GlobalUrlDefaultsMiddleware::class,
        ]));

        Route::middleware(UrlDefaultsMiddleware::class)
            ->get('/precedence/{locale}', fn () => '')->name('precedence.show');

        $this->assertStringContainsString("@param locale - Default: 'en'", $this->generate('precedence'));
    }
}
