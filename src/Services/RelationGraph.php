<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Models\AdrRecord;
use Fanmade\AdrManager\Models\AdrRelation;

/**
 * Query layer over the cache index for the dashboard's relation graph
 * (see ADR 0003: graph traversal reads the database, not the files). Only
 * `supersedes` rows become edges — the `backlinks` rows mirror them.
 */
final class RelationGraph
{
    /**
     * @return array{
     *     nodes: list<array{id: string, title: string, status: string}>,
     *     edges: list<array{from: string, to: string}>,
     * }
     */
    public function build(): array
    {
        $nodes = array_values(AdrRecord::query()
            ->orderBy('sequence_number')
            ->get(['id', 'title', 'status'])
            ->map(fn (AdrRecord $record): array => [
                'id' => $record->id,
                'title' => $record->title,
                'status' => $record->status,
            ])
            ->all());

        $edges = array_values(AdrRelation::query()
            ->where('relation_type', AdrRelation::SUPERSEDES)
            ->orderBy('id')
            ->get(['parent_id', 'child_id'])
            ->map(fn (AdrRelation $relation): array => [
                'from' => $relation->parent_id,
                'to' => $relation->child_id,
            ])
            ->all());

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
