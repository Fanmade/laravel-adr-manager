<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Models\AdrRecord;
use Fanmade\AdrManager\Models\AdrRelation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

it('indexes every record found on disk', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'proposed', ['0001']));

    $this->artisan('adr:sync')->assertSuccessful();

    expect(AdrRecord::query()->count())->toBe(2)
        ->and(AdrRecord::query()->find('0001')?->status)->toBe('accepted')
        ->and(AdrRecord::query()->find('0002')?->sequence_number)->toBe(2);
});

it('mirrors superseding links into the relations pivot', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'proposed', ['0001']));

    $this->artisan('adr:sync');

    expect(AdrRelation::query()
        ->where('parent_id', '0002')
        ->where('child_id', '0001')
        ->where('relation_type', 'supersedes')
        ->exists())->toBeTrue();
});

it('prunes records whose files were removed from disk', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First'));
    $repo->save(record('0002', 'Second'));
    $this->artisan('adr:sync');

    (new Filesystem)->delete(base_path('docs/adrs/0002-second.md'));
    $this->artisan('adr:sync');

    expect(AdrRecord::query()->count())->toBe(1)
        ->and(AdrRecord::query()->find('0002'))->toBeNull();
});

it('is idempotent across repeated runs', function () {
    app(AdrRepository::class)->save(record('0001', 'First'));

    $this->artisan('adr:sync');
    $this->artisan('adr:sync');

    expect(AdrRecord::query()->count())->toBe(1);
});

it('reports the number of synchronized records', function () {
    app(AdrRepository::class)->save(record('0001', 'First'));

    $this->artisan('adr:sync')
        ->expectsOutputToContain('1')
        ->assertSuccessful();
});
