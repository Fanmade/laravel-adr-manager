<?php

declare(strict_types=1);

use Fanmade\AdrManager\Exceptions\AdrNotFound;
use Illuminate\Filesystem\Filesystem;

afterEach(function () {
    (new Filesystem)->deleteDirectory(sys_get_temp_dir().'/adr-manager-tests');
});

it('persists a record to disk and reads it back unchanged', function () {
    $repo = makeRepo($dir = adrTestDir());

    $repo->save(record('0001', 'Choose a framework'));

    expect($repo->find('0001')?->toArray())->toBe(record('0001', 'Choose a framework')->toArray())
        ->and(glob($dir.'/*.md'))->toHaveCount(1);
});

it('writes a git-friendly filename from the id and slugified title', function () {
    $repo = makeRepo($dir = adrTestDir());

    $repo->save(record('0007', 'Use PostgreSQL for persistence'));

    expect(file_exists($dir.'/0007-use-postgresql-for-persistence.md'))->toBeTrue();
});

it('returns null when a record does not exist', function () {
    expect(makeRepo(adrTestDir())->find('9999'))->toBeNull();
});

it('refuses to look up ids containing path or glob metacharacters', function (string $maliciousId) {
    $repo = makeRepo($dir = adrTestDir());
    $repo->save(record('0001', 'First'));

    expect($repo->find($maliciousId))->toBeNull()
        // The legitimate record is still reachable.
        ->and($repo->find('0001'))->not->toBeNull()
        ->and(glob($dir.'/*.md'))->toHaveCount(1);
})->with([
    'parent traversal' => '../0001',
    'glob wildcard' => '*',
    'glob range' => '000[0-9]',
    'nested path' => 'foo/0001',
]);

it('reads an empty set when the directory does not yet exist', function () {
    $repo = makeRepo(sys_get_temp_dir().'/adr-manager-tests/missing-'.uniqid());

    expect($repo->all())->toBeEmpty()
        ->and($repo->getLatestSequence())->toBe(0);
});

it('lists every record ordered by ascending sequence', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(record('0003', 'Third'));
    $repo->save(record('0001', 'First'));
    $repo->save(record('0002', 'Second'));

    expect($repo->all()->pluck('id')->all())->toBe(['0001', '0002', '0003']);
});

it('reports the highest sequence number in use', function () {
    $repo = makeRepo(adrTestDir());
    expect($repo->getLatestSequence())->toBe(0);

    $repo->save(record('0001', 'First'));
    $repo->save(record('0012', 'Twelfth'));

    expect($repo->getLatestSequence())->toBe(12);
});

it('does not leave an orphan file when a saved title changes its slug', function () {
    $repo = makeRepo($dir = adrTestDir());
    $repo->save(record('0001', 'Original title'));

    $repo->save($repo->find('0001')?->with(title: 'Renamed title'));

    expect(glob($dir.'/*.md'))->toHaveCount(1)
        ->and(file_exists($dir.'/0001-renamed-title.md'))->toBeTrue();
});

it('supersedes a record by linking both files reciprocally', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(record('0002', 'Old decision', 'accepted'));
    $repo->save(record('0005', 'New decision', 'accepted'));

    $repo->supersede('0002', '0005');

    $old = $repo->find('0002');
    $new = $repo->find('0005');

    expect($old?->status)->toBe('superseded')
        ->and($old?->backlinks)->toContain('0005')
        ->and($new?->supersedes)->toContain('0002')
        ->and($new?->status)->toBe('accepted');
});

it('refuses to let a record supersede itself', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(record('0001', 'Only decision', 'accepted'));

    expect(fn () => $repo->supersede('0001', '0001'))
        ->toThrow(InvalidArgumentException::class);

    // The record is left untouched.
    expect($repo->find('0001')?->status)->toBe('accepted');
});

it('throws when superseding an unknown record', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(record('0005', 'New decision'));

    expect(fn () => $repo->supersede('0002', '0005'))
        ->toThrow(AdrNotFound::class, '0002');
});
