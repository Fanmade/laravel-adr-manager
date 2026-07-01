<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Exceptions\AdrNotFound;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;
use Illuminate\Filesystem\Filesystem;

function adrTestDir(): string
{
    $dir = sys_get_temp_dir().'/adr-manager-tests/'.uniqid('repo-', true);
    (new Filesystem)->ensureDirectoryExists($dir);

    return $dir;
}

function makeRepo(string $dir): LocalMarkdownRepository
{
    return new LocalMarkdownRepository(
        new Filesystem,
        new MarkdownParser,
        new MarkdownGenerator,
        $dir,
    );
}

function record(string $id, string $title, string $status = 'proposed'): AdrDto
{
    return AdrDto::fromArray([
        'id' => $id,
        'title' => $title,
        'status' => $status,
        'date' => '2026-01-15',
        'decision' => 'We decided.',
    ]);
}

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

it('throws when superseding an unknown record', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(record('0005', 'New decision'));

    expect(fn () => $repo->supersede('0002', '0005'))
        ->toThrow(AdrNotFound::class, '0002');
});
