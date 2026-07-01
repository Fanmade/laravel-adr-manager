<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Services\ChangelogGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;

afterEach(function () {
    (new Filesystem)->deleteDirectory(sys_get_temp_dir().'/adr-manager-tests');
});

function dated(string $id, string $title, string $status, string $date): AdrDto
{
    return AdrDto::fromArray(compact('id', 'title', 'status', 'date'));
}

it('compiles records into a dated, chronologically ordered markdown list', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(dated('0002', 'Adopt Redis cache', 'accepted', '2026-05-10'));
    $repo->save(dated('0001', 'Choose PostgreSQL', 'accepted', '2026-01-05'));

    $markdown = (new ChangelogGenerator($repo))->generate();

    expect($markdown)->toContain('ADR-0001')
        ->and($markdown)->toContain('Adopt Redis cache')
        ->and($markdown)->toContain('accepted')
        ->and($markdown)->toContain('2026-05-10')
        ->and(strpos($markdown, 'ADR-0001'))->toBeLessThan(strpos($markdown, 'ADR-0002'));
});

it('limits the changelog to a date range', function () {
    $repo = makeRepo(adrTestDir());
    $repo->save(dated('0001', 'Old decision', 'accepted', '2026-01-05'));
    $repo->save(dated('0002', 'Recent decision', 'accepted', '2026-06-20'));

    $markdown = (new ChangelogGenerator($repo))->generate(
        from: Carbon::parse('2026-06-01'),
    );

    expect($markdown)->toContain('Recent decision')
        ->and($markdown)->not->toContain('Old decision');
});

it('renders a placeholder when no records fall in the range', function () {
    $markdown = (new ChangelogGenerator(makeRepo(adrTestDir())))->generate();

    expect($markdown)->toContain('No records');
});
