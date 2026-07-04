<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;

/**
 * Keeps supersede relations reciprocal when a record is created or edited:
 * newly listed targets are superseded through the engine, and targets removed
 * from the list are released — their backlink is dropped and, when no other
 * record supersedes them anymore, their status reverts to `accepted` (the
 * prior status is not recorded; see ADR 0006).
 *
 * Call after the record itself has been saved.
 */
final class SupersedeSynchronizer
{
    public function apply(AdrRepository $repository, ?AdrDto $before, AdrDto $after): void
    {
        $previous = $before->supersedes ?? [];

        foreach (array_diff($after->supersedes, $previous) as $target) {
            $repository->supersede($target, $after->id);
        }

        foreach (array_diff($previous, $after->supersedes) as $target) {
            $this->release($repository, $target, $after->id);
        }
    }

    private function release(AdrRepository $repository, string $targetId, string $formerId): void
    {
        $target = $repository->find($targetId);

        if ($target === null) {
            return;
        }

        $stillSuperseded = $repository->all()->contains(
            fn (AdrDto $adr): bool => $adr->id !== $formerId
                && in_array($targetId, $adr->supersedes, true),
        );

        $repository->save($target->with(
            status: ! $stillSuperseded && $target->status === 'superseded' ? 'accepted' : $target->status,
            backlinks: array_values(array_filter(
                $target->backlinks,
                fn (string $backlink): bool => $backlink !== $formerId,
            )),
        ));
    }
}
