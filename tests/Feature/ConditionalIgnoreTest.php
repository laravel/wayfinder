<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function Illuminate\Filesystem\join_paths;

class ConditionalIgnoreTest extends TestCase
{
    private string $rootPath;

    private string $cachePath;

    private array $generatedPaths = [];

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->rootPath = realpath(join_paths(__DIR__, '..', '..'));
        $this->cachePath = join_paths(sys_get_temp_dir(), 'wayfinder-condition-cache-'.uniqid());

        $envExample = join_paths($this->rootPath, 'workbench', '.env.example');
        $envFile = join_paths($this->rootPath, 'workbench', '.env');

        if ($this->files->exists($envExample) && ! $this->files->exists($envFile)) {
            $this->files->copy($envExample, $envFile);
        }
    }

    protected function tearDown(): void
    {
        foreach ([...$this->generatedPaths, $this->cachePath] as $path) {
            $this->files->deleteDirectory($path);
        }

        parent::tearDown();
    }

    public function test_a_condition_decides_per_build_even_when_the_analysis_is_cached(): void
    {
        $off = $this->generate(fake: false);

        $this->assertStringContainsString('export const Gitlab = "gitlab"', $off);
        $this->assertStringNotContainsString('GitFake', $off);

        // Same cache directory, so the second run reads the analysis written by
        // the first: the condition has to be answered after the cache, not
        // baked into it.
        $on = $this->generate(fake: true);

        $this->assertStringContainsString('export const GitFake = "gitfake"', $on);

        $this->assertStringNotContainsString('GitFake', $this->generate(fake: false));
    }

    public function test_a_when_condition_leaves_a_case_out_only_while_it_passes(): void
    {
        $hidden = $this->generate(hideRetired: true);

        $this->assertStringNotContainsString('GitRetired', $hidden);

        $shown = $this->generate(hideRetired: false);

        $this->assertStringContainsString('export const GitRetired = "gitretired"', $shown);
    }

    private function generate(bool $fake = false, bool $hideRetired = true): string
    {
        $path = join_paths(sys_get_temp_dir(), 'wayfinder-condition-'.uniqid());
        $this->generatedPaths[] = $path;

        $process = new Process([
            join_paths($this->rootPath, 'vendor', 'bin', 'testbench'),
            'wayfinder:generate',
            '--path='.$path,
            '--app-path='.join_paths($this->rootPath, 'workbench', 'app'),
            '--base-path='.join_paths($this->rootPath, 'workbench'),
        ], $this->rootPath, [
            'WAYFINDER_CACHE_ENABLED' => 'true',
            'WAYFINDER_CACHE_DIRECTORY' => $this->cachePath,
            'WORKBENCH_FAKE_SOURCE_PROVIDER' => $fake ? '1' : '',
            'WORKBENCH_HIDE_RETIRED_SOURCE_PROVIDER' => $hideRetired ? '1' : '0',
        ]);

        $process->setTimeout(120);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            'wayfinder:generate failed: '.$process->getErrorOutput().$process->getOutput()
        );

        return $this->files->get(join_paths($path, 'App', 'Enums', 'SourceProvider.ts'));
    }
}
