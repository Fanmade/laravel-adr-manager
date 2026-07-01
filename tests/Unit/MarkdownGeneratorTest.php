<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;

function sampleDto(): AdrDto
{
    return AdrDto::fromArray([
        'id' => '0007',
        'title' => 'Use PostgreSQL for persistence',
        'status' => 'accepted',
        'date' => '2026-01-15',
        'author' => 'Ben',
        'context' => "We need a relational store.\n\nStrong consistency matters.",
        'decision' => 'We will use PostgreSQL.',
        'consequences' => 'Operations must run PostgreSQL.',
        'backlinks' => ['0003'],
        'supersedes' => ['0002'],
    ]);
}

it('renders the standardized Nygard headings', function () {
    $output = (new MarkdownGenerator)->render(sampleDto());

    expect($output)->toContain('## Context')
        ->and($output)->toContain('## Decision')
        ->and($output)->toContain('## Consequences')
        ->and($output)->toStartWith('---')
        ->and($output)->toEndWith("\n");
});

it('produces output that parses back into an equal record (round-trip)', function () {
    $dto = sampleDto();

    $reparsed = (new MarkdownParser)->parse((new MarkdownGenerator)->render($dto));

    expect($reparsed->toArray())->toBe($dto->toArray());
});

it('round-trips records with special characters in the title', function () {
    $dto = AdrDto::fromArray([
        'id' => '0011',
        'title' => 'Adopt "strict" mode: no exceptions',
        'status' => 'proposed',
        'date' => '2026-03-01',
        'decision' => 'Enable it.',
    ]);

    $reparsed = (new MarkdownParser)->parse((new MarkdownGenerator)->render($dto));

    expect($reparsed->title)->toBe('Adopt "strict" mode: no exceptions');
});

it('is idempotent: rendering a re-parsed document yields identical output (no drift)', function () {
    $generator = new MarkdownGenerator;

    $once = $generator->render(sampleDto());
    $twice = $generator->render((new MarkdownParser)->parse($once));

    expect($twice)->toBe($once);
});

it('omits optional front-matter that is empty', function () {
    $output = (new MarkdownGenerator)->render(AdrDto::fromArray([
        'id' => '0001',
        'title' => 'Minimal',
        'status' => 'proposed',
        'date' => '2026-01-01',
    ]));

    expect($output)->not->toContain('author')
        ->and($output)->not->toContain('supersedes')
        ->and($output)->not->toContain('backlinks');
});
