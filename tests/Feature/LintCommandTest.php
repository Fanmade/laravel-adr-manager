<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
    (new Filesystem)->ensureDirectoryExists(base_path('docs/adrs'));
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

function writeRawAdr(string $filename, string $contents): void
{
    (new Filesystem)->put(base_path('docs/adrs/'.$filename), $contents);
}

it('passes a well-formed, contiguous set of records', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'proposed'));

    $this->artisan('adr:lint')->assertSuccessful();
});

it('fails when a record uses a status outside the allowed set', function () {
    app(AdrRepository::class)->save(record('0001', 'First', 'yolo'));

    $this->artisan('adr:lint')
        ->expectsOutputToContain('yolo')
        ->assertFailed();
});

it('fails when a record links to a non-existent record', function () {
    app(AdrRepository::class)->save(record('0001', 'First', 'accepted', ['9999']));

    $this->artisan('adr:lint')
        ->expectsOutputToContain('9999')
        ->assertFailed();
});

it('fails when the sequence has a gap', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First'));
    $repo->save(record('0003', 'Third'));

    $this->artisan('adr:lint')
        ->expectsOutputToContain('0002')
        ->assertFailed();
});

it('fails when a file cannot be parsed into a valid record', function () {
    writeRawAdr('0001-broken.md', "---\ntitle: No identifier\nstatus: accepted\n---\n\n## Context\n\nBody.\n");

    $this->artisan('adr:lint')->assertFailed();
});

it('passes when the records directory does not exist', function () {
    config()->set('adr-manager.path', 'docs/nonexistent');

    $this->artisan('adr:lint')->assertSuccessful();
});

it('fails when two files declare the same record id', function () {
    app(AdrRepository::class)->save(record('0001', 'First'));
    writeRawAdr('0001-duplicate.md', "---\nid: \"0001\"\ntitle: Duplicate\nstatus: accepted\ndate: 2026-01-01\n---\n\n## Context\n\nx\n");

    $this->artisan('adr:lint')
        ->expectsOutputToContain('Duplicate')
        ->assertFailed();
});

it('treats a non-array status configuration as an empty allow-list', function () {
    config()->set('adr-manager.statuses', 'nonsense');
    app(AdrRepository::class)->save(record('0001', 'First', 'accepted'));

    $this->artisan('adr:lint')->assertFailed();
});

it('reports the offending file path', function () {
    app(AdrRepository::class)->save(record('0001', 'First', 'nope'));

    $this->artisan('adr:lint')
        ->expectsOutputToContain('0001-first.md')
        ->assertFailed();
});
