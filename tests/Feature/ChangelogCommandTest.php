<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

it('prints the changelog to stdout', function () {
    app(AdrRepository::class)->save(record('0001', 'First', 'accepted'));

    $this->artisan('adr:changelog')
        ->expectsOutputToContain('ADR-0001')
        ->assertSuccessful();
});

it('writes the changelog to a file with --output', function () {
    app(AdrRepository::class)->save(record('0001', 'First', 'accepted'));
    $target = base_path('CHANGELOG-ADR.md');

    $this->artisan('adr:changelog', ['--output' => $target])->assertSuccessful();

    expect(file_exists($target))->toBeTrue()
        ->and(file_get_contents($target))->toContain('ADR-0001');
});

it('applies the --from date filter', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));

    $this->artisan('adr:changelog', ['--from' => '2030-01-01'])
        ->expectsOutputToContain('No records')
        ->assertSuccessful();
});
