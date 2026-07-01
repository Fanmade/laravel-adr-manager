<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;
use Illuminate\Filesystem\Filesystem;

/**
 * A throwaway records directory under the system temp path.
 */
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

/**
 * @param  list<string>  $supersedes
 */
function record(string $id, string $title, string $status = 'proposed', array $supersedes = []): AdrDto
{
    return AdrDto::fromArray([
        'id' => $id,
        'title' => $title,
        'status' => $status,
        'date' => '2026-01-15',
        'decision' => 'We decided.',
        'supersedes' => $supersedes,
    ]);
}
