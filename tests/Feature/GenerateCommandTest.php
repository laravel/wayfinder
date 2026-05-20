<?php

use Illuminate\Filesystem\Filesystem;

use function Illuminate\Filesystem\join_paths;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->tempPath = join_paths(sys_get_temp_dir(), 'wayfinder-prune-'.uniqid());
});

afterEach(function () {
    $this->files->deleteDirectory($this->tempPath);
});

function generate(object $test): void
{
    $rootPath = realpath(join_paths(__DIR__, '..', '..'));

    $test->artisan('wayfinder:generate', [
        '--path' => $test->tempPath,
        '--app-path' => join_paths($rootPath, 'workbench', 'app'),
        '--base-path' => join_paths($rootPath, 'workbench'),
    ])->assertSuccessful();
}

test('generated files exist after generate', function () {
    generate($this);

    expect($this->tempPath)->toBeDirectory();
    expect(join_paths($this->tempPath, 'index.ts'))->toBeFile();
    expect($this->files->allFiles($this->tempPath))->not->toBeEmpty();
});

test('stale files are removed while current files are kept', function () {
    generate($this);

    $current = collect($this->files->allFiles($this->tempPath))
        ->map(fn ($file) => $file->getPathname());

    expect($current)->not->toBeEmpty();

    $stale = join_paths($this->tempPath, 'definitely-not-a-real-route.ts');
    $this->files->put($stale, '// stale');
    expect($stale)->toBeFile();

    generate($this);

    expect($stale)->not->toBeFile();
    $current->each(fn ($path) => expect($path)->toBeFile());
});

test('empty directories are pruned', function () {
    generate($this);

    $orphanDir = join_paths($this->tempPath, 'orphan-dir');
    $this->files->ensureDirectoryExists($orphanDir);
    $this->files->put(join_paths($orphanDir, 'thing.ts'), '// stale');

    generate($this);

    expect($orphanDir)->not->toBeDirectory();
});

test('helper index is skipped when contents match', function () {
    generate($this);

    $destination = join_paths($this->tempPath, 'index.ts');
    $beforeMtime = filemtime($destination);

    clearstatcache(true, $destination);
    sleep(1);

    generate($this);

    clearstatcache(true, $destination);
    expect(filemtime($destination))->toBe($beforeMtime);
});

test('unchanged generated files are not rewritten', function () {
    generate($this);

    $sample = collect($this->files->allFiles($this->tempPath))
        ->map(fn ($file) => $file->getPathname())
        ->first(fn ($path) => str_ends_with($path, '.ts') && ! str_ends_with($path, 'index.ts'));

    expect($sample)->not->toBeNull();

    $beforeMtime = filemtime($sample);

    clearstatcache(true, $sample);
    sleep(1);

    generate($this);

    clearstatcache(true, $sample);
    expect(filemtime($sample))->toBe($beforeMtime);
});

test('noop regenerate does not touch any file', function () {
    generate($this);

    $before = collect($this->files->allFiles($this->tempPath))
        ->mapWithKeys(fn ($file) => [$file->getPathname() => filemtime($file->getPathname())]);

    expect($before)->not->toBeEmpty();

    clearstatcache();
    sleep(1);

    generate($this);

    clearstatcache();
    $after = collect($this->files->allFiles($this->tempPath))
        ->mapWithKeys(fn ($file) => [$file->getPathname() => filemtime($file->getPathname())]);

    expect($before->keys()->sort()->values()->all())->toBe($after->keys()->sort()->values()->all());

    $changed = $before->filter(fn ($mtime, $path) => $after->get($path) !== $mtime);

    expect($changed->keys()->all())->toBeEmpty(
        'expected no files to be rewritten on a no-op regen, got: '.$changed->keys()->implode(', ')
    );
});
