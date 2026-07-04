<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    config()->set('adr-manager.authoring.environments', ['testing']);
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

it('creates a record with the next id and prints commit instructions', function () {
    $this->artisan('adr:make', ['title' => 'Use PostgreSQL'])
        ->expectsOutputToContain('git add docs/adrs/0001-use-postgresql.md')
        ->assertSuccessful();

    $adr = app(AdrRepository::class)->find('0001');

    expect($adr)->not->toBeNull()
        ->and($adr->title)->toBe('Use PostgreSQL')
        ->and($adr->status)->toBe('proposed');
});

it('continues the existing id sequence', function () {
    app(AdrRepository::class)->save(record('0007', 'Earlier decision', 'accepted'));

    $this->artisan('adr:make', ['title' => 'Next decision'])->assertSuccessful();

    expect(app(AdrRepository::class)->find('0008'))->not->toBeNull();
});

it('accepts status and author options', function () {
    $this->artisan('adr:make', [
        'title' => 'Adopt Redis',
        '--status' => 'accepted',
        '--author' => 'Ben',
    ])->assertSuccessful();

    $adr = app(AdrRepository::class)->find('0001');

    expect($adr->status)->toBe('accepted')
        ->and($adr->author)->toBe('Ben');
});

it('rejects an empty title', function () {
    $this->artisan('adr:make', ['title' => '  '])
        ->expectsOutputToContain('title must not be empty')
        ->assertExitCode(Command::FAILURE);

    expect(app(AdrRepository::class)->all())->toHaveCount(0);
});

it('rejects a status outside the configured list', function () {
    $this->artisan('adr:make', ['title' => 'Bad status', '--status' => 'bogus'])
        ->expectsOutputToContain('Invalid status')
        ->assertExitCode(Command::FAILURE);

    expect(app(AdrRepository::class)->all())->toHaveCount(0);
});

it('supersedes existing records reciprocally and passes linting', function () {
    app(AdrRepository::class)->save(record('0001', 'Old decision', 'accepted'));

    $this->artisan('adr:make', ['title' => 'New direction', '--supersedes' => ['0001']])
        ->assertSuccessful();

    $old = app(AdrRepository::class)->find('0001');
    $new = app(AdrRepository::class)->find('0002');

    expect($old->status)->toBe('superseded')
        ->and($old->backlinks)->toContain('0002')
        ->and($new->supersedes)->toContain('0001');

    $this->artisan('adr:lint')->assertSuccessful();
});

it('rejects an unknown supersede target before writing anything', function () {
    $this->artisan('adr:make', ['title' => 'Orphan link', '--supersedes' => ['9999']])
        ->expectsOutputToContain('9999')
        ->assertExitCode(Command::FAILURE);

    expect(app(AdrRepository::class)->all())->toHaveCount(0);
});

it('refuses to write outside the configured authoring environments', function () {
    config()->set('adr-manager.authoring.environments', ['local']);

    $this->artisan('adr:make', ['title' => 'Not here'])
        ->expectsOutputToContain('not enabled in this environment')
        ->assertExitCode(Command::FAILURE);

    expect(app(AdrRepository::class)->all())->toHaveCount(0);
});
