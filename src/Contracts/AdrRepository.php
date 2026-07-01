<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Contracts;

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Illuminate\Support\Collection;

/**
 * Abstract boundary for reading and mutating Architectural Decision Records.
 *
 * The default binding is the file-based {@see LocalMarkdownRepository},
 * but a host application may bind any implementation (database, remote API,
 * …) without touching the rest of the package.
 */
interface AdrRepository
{
    /**
     * All records, ordered by ascending sequence number.
     *
     * @return Collection<int, AdrDto>
     */
    public function all(): Collection;

    /**
     * Find a single record by its identifier, or null when it does not exist.
     */
    public function find(string $id): ?AdrDto;

    /**
     * Persist a record, creating or replacing it.
     */
    public function save(AdrDto $adr): void;

    /**
     * The highest sequence number currently in use (0 when empty).
     */
    public function getLatestSequence(): int;
}
