<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class SkipDatabaseTest extends TestCase
{
    private string $tempPath;

    private Filesystem $files;

    protected function getPackageProviders($app): array
    {
        return [WayfinderServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-skip-database-'.uniqid());

        Schema::create('docblock_posts', function (Blueprint $table) {
            $table->id();
        });

        Route::get('/docblock-posts/{post}', function (DocblockPost $post) {
            return $post;
        })->name('docblock-posts.show');
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    public function test_skipping_the_database_reads_model_docblocks(): void
    {
        $this->assertStringContainsString('post: number', $this->generate());

        $this->assertStringContainsString('post: string', $this->generate(skipDatabase: true));
    }

    private function generate(bool $skipDatabase = false): string
    {
        $this->artisan('wayfinder:generate', [
            '--path' => $this->tempPath,
            '--skip-actions' => true,
            '--skip-database' => $skipDatabase,
        ])->assertSuccessful();

        return $this->files->get(join_paths($this->tempPath, 'routes', 'docblock-posts', 'index.ts'));
    }
}

/**
 * @property string $id
 */
class DocblockPost extends Model
{
    protected $table = 'docblock_posts';
}
