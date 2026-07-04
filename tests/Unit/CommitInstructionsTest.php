<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Support\CommitInstructions;

it('builds the record path, markdown and git commands', function () {
    config()->set('adr-manager.path', 'docs/decisions/');

    $adr = AdrDto::fromArray([
        'id' => '0007',
        'title' => 'Use PostgreSQL',
        'status' => 'accepted',
    ]);

    $instructions = app(CommitInstructions::class)->for($adr);

    expect($instructions['path'])->toBe('docs/decisions/0007-use-postgresql.md')
        ->and($instructions['markdown'])->toContain('# 0007. Use PostgreSQL')
        ->and($instructions['commands'])->toContain('git add docs/decisions/0007-use-postgresql.md')
        ->and($instructions['commands'])->toContain("git commit -m 'docs(adr): 0007 Use PostgreSQL'");
});

it('falls back to the default records path when misconfigured', function () {
    config()->set('adr-manager.path', ['not-a-string']);

    $adr = AdrDto::fromArray([
        'id' => '0001',
        'title' => 'First decision',
        'status' => 'proposed',
    ]);

    expect(app(CommitInstructions::class)->for($adr)['path'])
        ->toBe('docs/adrs/0001-first-decision.md');
});
