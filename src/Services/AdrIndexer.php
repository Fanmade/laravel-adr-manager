<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Models\AdrRecord;
use Fanmade\AdrManager\Models\AdrRelation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Reconciles the relational cache index with the files on disk. A single
 * {@see sync()} call makes the database an exact projection of the current
 * file state: new records are inserted, changed records updated, and records
 * whose files disappeared are removed — all in one transaction.
 */
final class AdrIndexer
{
    public function __construct(private readonly AdrRepository $repository) {}

    /**
     * @return int the number of records now in the index.
     */
    public function sync(): int
    {
        // Collapse any duplicate ids (a lint failure, but the index must not
        // crash on it) so bulk upserts never target the same key twice.
        $adrs = $this->repository->all()
            ->keyBy(fn (AdrDto $adr): string => $adr->id)
            ->values();

        AdrRecord::query()->getConnection()->transaction(function () use ($adrs): void {
            $this->upsertRecords($adrs);
            $this->pruneRemoved($adrs);
            $this->rebuildRelations($adrs);
        });

        return $adrs->count();
    }

    /**
     * @param  Collection<int, AdrDto>  $adrs
     */
    private function upsertRecords(Collection $adrs): void
    {
        $now = Carbon::now();

        $rows = $adrs->map(fn (AdrDto $adr): array => [
            'id' => $adr->id,
            'sequence_number' => (int) ltrim($adr->id, '0'),
            'title' => $adr->title,
            'status' => $adr->status,
            'author' => $adr->author,
            'metadata' => json_encode([
                'date' => $adr->date->toDateString(),
                'backlinks' => $adr->backlinks,
                'supersedes' => $adr->supersedes,
            ], JSON_THROW_ON_ERROR),
            'content_summary' => Str::limit($adr->context !== '' ? $adr->context : $adr->decision, 500, ''),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows === []) {
            return;
        }

        AdrRecord::query()->upsert(
            $rows,
            ['id'],
            ['sequence_number', 'title', 'status', 'author', 'metadata', 'content_summary', 'updated_at'],
        );
    }

    /**
     * @param  Collection<int, AdrDto>  $adrs
     */
    private function pruneRemoved(Collection $adrs): void
    {
        AdrRecord::query()
            ->whereNotIn('id', $adrs->map(fn (AdrDto $adr): string => $adr->id)->all())
            ->delete();
    }

    /**
     * @param  Collection<int, AdrDto>  $adrs
     */
    private function rebuildRelations(Collection $adrs): void
    {
        AdrRelation::query()->delete();

        $now = Carbon::now();
        $rows = [];

        foreach ($adrs as $adr) {
            foreach ($adr->supersedes as $target) {
                $rows[] = $this->relationRow($adr->id, $target, AdrRelation::SUPERSEDES, $now);
            }

            foreach ($adr->backlinks as $target) {
                $rows[] = $this->relationRow($adr->id, $target, AdrRelation::BACKLINKS, $now);
            }
        }

        if ($rows !== []) {
            AdrRelation::query()->insert($rows);
        }
    }

    /**
     * @return array<string, string|Carbon>
     */
    private function relationRow(string $parent, string $child, string $type, Carbon $now): array
    {
        return [
            'parent_id' => $parent,
            'child_id' => $child,
            'relation_type' => $type,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
