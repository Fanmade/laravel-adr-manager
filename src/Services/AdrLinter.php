<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Data\LintIssue;
use Fanmade\AdrManager\Exceptions\InvalidAdrData;
use Illuminate\Filesystem\Filesystem;

/**
 * Validates the ADR files on disk without touching the database. It reports
 * format breakage, invalid statuses, broken links and skipped sequence
 * numbers so a CI job can gate on architectural integrity.
 */
final class AdrLinter
{
    /**
     * @param  list<string>  $allowedStatuses
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly MarkdownParser $parser,
        private readonly string $directory,
        private readonly array $allowedStatuses,
    ) {}

    /**
     * @return list<LintIssue>
     */
    public function lint(): array
    {
        $issues = [];

        /** @var array<string, AdrDto> $records */
        $records = [];

        /** @var array<string, string> $paths */
        $paths = [];

        foreach ($this->markdownFiles() as $path) {
            try {
                $dto = $this->parser->parse($this->files->get($path));
            } catch (InvalidAdrData $e) {
                $issues[] = new LintIssue($path, LintIssue::FORMAT, $e->getMessage());

                continue;
            }

            if (isset($records[$dto->id])) {
                $issues[] = new LintIssue($path, LintIssue::DUPLICATE, "Duplicate record id [{$dto->id}].");

                continue;
            }

            $records[$dto->id] = $dto;
            $paths[$dto->id] = $path;

            if (! in_array($dto->status, $this->allowedStatuses, true)) {
                $issues[] = new LintIssue(
                    $path,
                    LintIssue::STATUS,
                    "Invalid status [{$dto->status}]; allowed: ".implode(', ', $this->allowedStatuses).'.',
                );
            }
        }

        return [
            ...$issues,
            ...$this->brokenLinks($records, $paths),
            ...$this->oneSidedSupersedes($records, $paths),
            ...$this->sequenceGaps($records),
        ];
    }

    /**
     * @return list<string>
     */
    private function markdownFiles(): array
    {
        if (! $this->files->isDirectory($this->directory)) {
            return [];
        }

        $paths = [];

        foreach ($this->files->glob($this->directory.'/*.md') as $path) {
            if (is_string($path)) {
                $paths[] = $path;
            }
        }

        sort($paths);

        return $paths;
    }

    /**
     * @param  array<string, AdrDto>  $records
     * @param  array<string, string>  $paths
     * @return list<LintIssue>
     */
    private function brokenLinks(array $records, array $paths): array
    {
        $issues = [];

        foreach ($records as $id => $dto) {
            foreach ([...$dto->supersedes, ...$dto->backlinks] as $reference) {
                if (! array_key_exists($reference, $records)) {
                    $issues[] = new LintIssue(
                        $paths[$id],
                        LintIssue::LINK,
                        "ADR [{$id}] references unknown record [{$reference}].",
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * Every "A supersedes B" must be reciprocal: B carries a backlink to A
     * and the `superseded` status (see ADR 0006).
     *
     * @param  array<string, AdrDto>  $records
     * @param  array<string, string>  $paths
     * @return list<LintIssue>
     */
    private function oneSidedSupersedes(array $records, array $paths): array
    {
        $issues = [];

        foreach ($records as $id => $dto) {
            foreach ($dto->supersedes as $target) {
                if (! array_key_exists($target, $records)) {
                    continue; // Already reported as a broken link.
                }

                $targetDto = $records[$target];

                if ($targetDto->status !== 'superseded' || ! in_array($id, $targetDto->backlinks, true)) {
                    $issues[] = new LintIssue(
                        $paths[$target],
                        LintIssue::RELATION,
                        "ADR [{$target}] is superseded by [{$id}] but is not marked as superseded with a backlink.",
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, AdrDto>  $records
     * @return list<LintIssue>
     */
    private function sequenceGaps(array $records): array
    {
        $sequences = [];

        foreach ($records as $dto) {
            $sequences[] = (int) ltrim($dto->id, '0');
        }

        $sequences = array_values(array_unique($sequences));
        sort($sequences);

        $issues = [];

        for ($i = 1, $count = count($sequences); $i < $count; $i++) {
            for ($missing = $sequences[$i - 1] + 1; $missing < $sequences[$i]; $missing++) {
                $issues[] = new LintIssue(
                    $this->directory,
                    LintIssue::SEQUENCE,
                    'Sequence gap: missing ADR '.str_pad((string) $missing, 4, '0', STR_PAD_LEFT).'.',
                );
            }
        }

        return $issues;
    }
}
