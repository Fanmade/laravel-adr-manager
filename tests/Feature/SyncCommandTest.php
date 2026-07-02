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

it('indexes backlink relations produced by superseding', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'accepted'));
    $repo->supersede('0001', '0002');

    $this->artisan('adr:sync');

    expect(AdrRelation::query()
        ->where('parent_id', '0001')
        ->where('child_id', '0002')
        ->where('relation_type', 'backlinks')
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

it('clears the index when every file is removed from disk', function () {
    app(AdrRepository::class)->save(record('0001', 'First'));
    $this->artisan('adr:sync');

    (new Filesystem)->deleteDirectory(base_path('docs/adrs'));
    $this->artisan('adr:sync')->assertSuccessful();

    expect(AdrRecord::query()->count())->toBe(0)
        ->and(AdrRelation::query()->count())->toBe(0);
});

it('exposes indexed relations through the AdrRecord model', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'proposed', ['0001']));
    $this->artisan('adr:sync');

    $record = AdrRecord::query()->find('0002');

    expect($record?->relations)->toHaveCount(1)
        ->and($record?->relations->first()?->child_id)->toBe('0001');
});

it('degrades gracefully when two files share an id', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    (new Filesystem)->put(
        base_path('docs/adrs/0001-duplicate.md'),
        "---\nid: \"0001\"\ntitle: Duplicate\nstatus: proposed\ndate: 2026-01-01\n---\n\n## Context\n\nx\n",
    );

    $this->artisan('adr:sync')->assertSuccessful();

    expect(AdrRecord::query()->count())->toBe(1);
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
