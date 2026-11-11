<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class WriteFailureHandledTest extends TestCase
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
        $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-write-failure-'.uniqid());
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    public function test_command_fails_when_file_write_fails(): void
    {
        $failingFilesystem = new class extends Filesystem
        {
            public function put($path, $contents, $lock = false)
            {
                return false;
            }
        };

        $this->instance(Filesystem::class, $failingFilesystem);

        $this->artisan('wayfinder:generate', ['--path' => $this->tempPath])
            ->assertFailed()
            ->expectsOutputToContain('[Wayfinder] Unable to write file:');
    }

    public function test_command_fails_when_directory_creation_fails(): void
    {
        $failingFilesystem = new class extends Filesystem
        {
            public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
            {
                return false;
            }

            public function isDirectory($path)
            {
                return false;
            }

            public function put($path, $contents, $lock = false)
            {
                return true;
            }
        };

        $this->instance(Filesystem::class, $failingFilesystem);

        $this->artisan('wayfinder:generate', ['--path' => $this->tempPath])
            ->assertFailed()
            ->expectsOutputToContain('[Wayfinder] Unable to create directory:');
    }
}
