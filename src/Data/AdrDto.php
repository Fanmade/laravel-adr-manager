<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Data;

use DateTimeInterface;
use Fanmade\AdrManager\Exceptions\InvalidAdrData;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * Immutable representation of a single Architectural Decision Record.
 *
 * All engine layers exchange records as {@see AdrDto} instances so raw,
 * unvalidated arrays never leak past the parsing boundary.
 *
 * @implements Arrayable<string, mixed>
 */
final class AdrDto implements Arrayable
{
    /**
     * @param  list<string>  $backlinks  Records this record links back to.
     * @param  list<string>  $supersedes  Records this record supersedes.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $status,
        public readonly Carbon $date,
        public readonly string $context = '',
        public readonly string $decision = '',
        public readonly string $consequences = '',
        public readonly array $backlinks = [],
        public readonly array $supersedes = [],
        public readonly ?string $author = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidAdrData when a required attribute is missing or blank.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: self::requiredString($data, 'id'),
            title: self::requiredString($data, 'title'),
            status: self::requiredString($data, 'status'),
            date: self::toDate($data['date'] ?? null),
            context: self::optionalString($data, 'context'),
            decision: self::optionalString($data, 'decision'),
            consequences: self::optionalString($data, 'consequences'),
            backlinks: self::stringList($data['backlinks'] ?? []),
            supersedes: self::stringList($data['supersedes'] ?? []),
            author: self::nullableString($data, 'author'),
        );
    }

    /**
     * Return a copy with the given attributes replaced. Omitted attributes
     * keep their current value.
     *
     * @param  list<string>|null  $backlinks
     * @param  list<string>|null  $supersedes
     */
    public function with(
        ?string $id = null,
        ?string $title = null,
        ?string $status = null,
        ?Carbon $date = null,
        ?string $context = null,
        ?string $decision = null,
        ?string $consequences = null,
        ?array $backlinks = null,
        ?array $supersedes = null,
        ?string $author = null,
    ): self {
        return new self(
            id: $id ?? $this->id,
            title: $title ?? $this->title,
            status: $status ?? $this->status,
            date: $date ?? $this->date,
            context: $context ?? $this->context,
            decision: $decision ?? $this->decision,
            consequences: $consequences ?? $this->consequences,
            backlinks: $backlinks ?? $this->backlinks,
            supersedes: $supersedes ?? $this->supersedes,
            author: $author ?? $this->author,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'context' => $this->context,
            'decision' => $this->decision,
            'consequences' => $this->consequences,
            'backlinks' => $this->backlinks,
            'supersedes' => $this->supersedes,
            'date' => $this->date->toDateString(),
            'author' => $this->author,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidAdrData
     */
    private static function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw InvalidAdrData::missingAttribute($key);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function optionalString(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    private static function toDate(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return Carbon::now();
    }
}
