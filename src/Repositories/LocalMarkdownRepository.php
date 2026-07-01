<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Repositories;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Exceptions\AdrNotFound;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Default file-based repository: ADRs live as Markdown files in a Git-tracked
 * directory, and this class is the single point where they are read from and
 * written to disk.
 */
final class LocalMarkdownRepository implements AdrRepository
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly MarkdownParser $parser,
        private readonly MarkdownGenerator $generator,
        private readonly string $directory,
        private readonly string $filenamePattern = '{id}-{slug}.md',
    ) {}

    public function all(): Collection
    {
        if (! $this->files->isDirectory($this->directory)) {
            /** @var Collection<int, AdrDto> */
            return collect();
        }

        $records = [];

        foreach ($this->files->glob($this->directory.'/*.md') as $path) {
            if (is_string($path)) {
                $records[] = $this->parser->parse($this->files->get($path));
            }
        }

        return collect($records)
            ->sortBy(fn (AdrDto $adr): int => $this->sequenceOf($adr->id))
            ->values();
    }

    public function find(string $id): ?AdrDto
    {
        $path = $this->pathForId($id);

        return $path === null ? null : $this->parser->parse($this->files->get($path));
    }

    public function save(AdrDto $adr): void
    {
        $this->files->ensureDirectoryExists($this->directory);

        $target = $this->pathFor($adr);
        $existing = $this->pathForId($adr->id);

        $this->files->put($target, $this->generator->render($adr), lock: true);

        if ($existing !== null && $existing !== $target) {
            $this->files->delete($existing);
        }
    }

    public function getLatestSequence(): int
    {
        $max = $this->all()
            ->map(fn (AdrDto $adr): int => $this->sequenceOf($adr->id))
            ->max();

        return is_int($max) ? $max : 0;
    }

    /**
     * Mark $targetId as superseded by $newId, updating both files reciprocally.
     *
     * @throws AdrNotFound when either record is missing.
     */
    public function supersede(string $targetId, string $newId): void
    {
        $target = $this->find($targetId) ?? throw AdrNotFound::withId($targetId);
        $new = $this->find($newId) ?? throw AdrNotFound::withId($newId);

        $this->save($target->with(
            status: 'superseded',
            backlinks: $this->withValue($target->backlinks, $newId),
        ));

        $this->save($new->with(
            supersedes: $this->withValue($new->supersedes, $targetId),
        ));
    }

    private function pathFor(AdrDto $adr): string
    {
        $filename = str_replace(
            ['{id}', '{slug}'],
            [$adr->id, Str::slug($adr->title)],
            $this->filenamePattern,
        );

        return $this->directory.'/'.$filename;
    }

    private function pathForId(string $id): ?string
    {
        $first = $this->files->glob($this->directory.'/'.$id.'-*.md')[0] ?? null;

        return is_string($first) ? $first : null;
    }

    private function sequenceOf(string $id): int
    {
        return (int) ltrim($id, '0');
    }

    /**
     * @param  list<string>  $list
     * @return list<string>
     */
    private function withValue(array $list, string $value): array
    {
        return in_array($value, $list, true) ? $list : [...$list, $value];
    }
}
