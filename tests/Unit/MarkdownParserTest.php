<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Exceptions\InvalidAdrData;
use Fanmade\AdrManager\Services\MarkdownParser;

function nygardSample(): string
{
    return <<<'MD'
        ---
        id: "0007"
        title: Use PostgreSQL for persistence
        status: accepted
        date: 2026-01-15
        author: Ben
        supersedes:
          - "0002"
        backlinks:
          - "0003"
        ---

        # 7. Use PostgreSQL for persistence

        ## Context

        We need a relational store with strong consistency.

        ## Decision

        We will use PostgreSQL.

        ## Consequences

        Operations must run and back up PostgreSQL.
        MD;
}

it('maps a Nygard record into a populated DTO with separated sections', function () {
    $dto = (new MarkdownParser)->parse(nygardSample());

    expect($dto)->toBeInstanceOf(AdrDto::class)
        ->and($dto->id)->toBe('0007')
        ->and($dto->title)->toBe('Use PostgreSQL for persistence')
        ->and($dto->status)->toBe('accepted')
        ->and($dto->date->toDateString())->toBe('2026-01-15')
        ->and($dto->author)->toBe('Ben')
        ->and($dto->supersedes)->toBe(['0002'])
        ->and($dto->backlinks)->toBe(['0003'])
        ->and($dto->context)->toBe('We need a relational store with strong consistency.')
        ->and($dto->decision)->toBe('We will use PostgreSQL.')
        ->and($dto->consequences)->toBe('Operations must run and back up PostgreSQL.');
});

it('treats section headings case-insensitively and defaults absent sections to empty', function () {
    $raw = <<<'MD'
        ---
        id: "0001"
        title: Minimal
        status: proposed
        date: 2026-02-01
        ---

        ## context

        Only a context here.
        MD;

    $dto = (new MarkdownParser)->parse($raw);

    expect($dto->context)->toBe('Only a context here.')
        ->and($dto->decision)->toBe('')
        ->and($dto->consequences)->toBe('');
});

it('preserves multi-paragraph section bodies', function () {
    $raw = <<<'MD'
        ---
        id: "0002"
        title: Multi
        status: proposed
        date: 2026-02-01
        ---

        ## Decision

        First paragraph.

        Second paragraph.
        MD;

    expect((new MarkdownParser)->parse($raw)->decision)
        ->toBe("First paragraph.\n\nSecond paragraph.");
});

it('throws when the document has no front-matter block at all', function () {
    expect(fn () => (new MarkdownParser)->parse("# A title\n\n## Context\n\nBody without front-matter."))
        ->toThrow(InvalidAdrData::class);
});

it('throws when the front-matter lacks a required attribute', function () {
    $raw = <<<'MD'
        ---
        title: No identifier
        status: proposed
        ---

        ## Context

        Body.
        MD;

    expect(fn () => (new MarkdownParser)->parse($raw))
        ->toThrow(InvalidAdrData::class, 'id');
});
