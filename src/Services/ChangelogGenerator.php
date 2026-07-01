<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Illuminate\Support\Carbon;

/**
 * Compiles the records into a human-readable Markdown changelog, optionally
 * restricted to a date range. Records are listed oldest-first so the output
 * reads as a timeline of architectural decisions.
 */
final class ChangelogGenerator
{
    public function __construct(private readonly AdrRepository $repository) {}

    public function generate(?Carbon $from = null, ?Carbon $to = null): string
    {
        $records = $this->repository->all()
            ->filter(fn (AdrDto $adr): bool => $this->withinRange($adr, $from, $to))
            ->sortBy(fn (AdrDto $adr): int => $adr->date->getTimestamp())
            ->values();

        if ($records->isEmpty()) {
            return "# Architectural Changelog\n\n_No records in the selected range._\n";
        }

        $lines = ['# Architectural Changelog', ''];

        foreach ($records as $adr) {
            $lines[] = sprintf(
                '- **ADR-%s** — %s _(%s, %s)_',
                $adr->id,
                $adr->title,
                $adr->status,
                $adr->date->toDateString(),
            );
        }

        return implode("\n", $lines)."\n";
    }

    private function withinRange(AdrDto $adr, ?Carbon $from, ?Carbon $to): bool
    {
        if ($from !== null && $adr->date->lessThan($from)) {
            return false;
        }

        return $to === null || ! $adr->date->greaterThan($to);
    }
}
