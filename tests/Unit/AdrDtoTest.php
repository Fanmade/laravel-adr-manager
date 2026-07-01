<?php

declare(strict_types=1);

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Exceptions\InvalidAdrData;
use Illuminate\Support\Carbon;

it('builds a fully populated record from an array', function () {
    $dto = AdrDto::fromArray([
        'id' => '0007',
        'title' => 'Use PostgreSQL for persistence',
        'status' => 'accepted',
        'context' => 'We need a relational store.',
        'decision' => 'We will use PostgreSQL.',
        'consequences' => 'Operations must run PostgreSQL.',
        'backlinks' => ['0003'],
        'supersedes' => ['0002'],
        'date' => '2026-01-15',
        'author' => 'Ben',
    ]);

    expect($dto->id)->toBe('0007')
        ->and($dto->title)->toBe('Use PostgreSQL for persistence')
        ->and($dto->status)->toBe('accepted')
        ->and($dto->context)->toBe('We need a relational store.')
        ->and($dto->decision)->toBe('We will use PostgreSQL.')
        ->and($dto->consequences)->toBe('Operations must run PostgreSQL.')
        ->and($dto->backlinks)->toBe(['0003'])
        ->and($dto->supersedes)->toBe(['0002'])
        ->and($dto->date)->toBeInstanceOf(Carbon::class)
        ->and($dto->date->toDateString())->toBe('2026-01-15')
        ->and($dto->author)->toBe('Ben');
});

it('applies defaults for optional attributes', function () {
    $dto = AdrDto::fromArray([
        'id' => '0001',
        'title' => 'First decision',
        'status' => 'proposed',
    ]);

    expect($dto->context)->toBe('')
        ->and($dto->decision)->toBe('')
        ->and($dto->consequences)->toBe('')
        ->and($dto->backlinks)->toBe([])
        ->and($dto->supersedes)->toBe([])
        ->and($dto->author)->toBeNull()
        ->and($dto->date)->toBeInstanceOf(Carbon::class);
});

it('throws a clear exception naming the missing required attribute', function (array $payload, string $missing) {
    expect(fn () => AdrDto::fromArray($payload))
        ->toThrow(InvalidAdrData::class, $missing);
})->with([
    'missing id' => [['title' => 'x', 'status' => 'proposed'], 'id'],
    'missing title' => [['id' => '1', 'status' => 'proposed'], 'title'],
    'missing status' => [['id' => '1', 'title' => 'x'], 'status'],
    'blank id' => [['id' => '   ', 'title' => 'x', 'status' => 'proposed'], 'id'],
]);

it('round-trips through toArray() with the date serialized to a string', function () {
    $payload = [
        'id' => '0007',
        'title' => 'Use PostgreSQL',
        'status' => 'accepted',
        'context' => 'ctx',
        'decision' => 'dec',
        'consequences' => 'con',
        'backlinks' => ['0003'],
        'supersedes' => ['0002'],
        'date' => '2026-01-15',
        'author' => 'Ben',
    ];

    expect(AdrDto::fromArray($payload)->toArray())->toBe($payload);
});

it('exposes an immutable copy helper that leaves the original untouched', function () {
    $original = AdrDto::fromArray(['id' => '1', 'title' => 'x', 'status' => 'proposed']);

    $changed = $original->with(status: 'accepted');

    expect($changed->status)->toBe('accepted')
        ->and($original->status)->toBe('proposed')
        ->and($changed->id)->toBe($original->id);
});
