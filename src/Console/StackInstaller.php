<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Console;

use Illuminate\Filesystem\Filesystem;

/**
 * Copies a starter stack's stub files to their destinations in the host
 * application. The manifest (which stub goes where) is supplied by the
 * composition root, keeping this class free of path resolution logic.
 */
final class StackInstaller
{
    /**
     * @param  array<string, list<array{0: string, 1: string}>>  $manifest  stack => list of [stubPath, destinationPath]
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly array $manifest,
    ) {}

    /**
     * @return list<string>
     */
    public function stacks(): array
    {
        return array_keys($this->manifest);
    }

    public function supports(string $stack): bool
    {
        return array_key_exists($stack, $this->manifest);
    }

    /**
     * @return list<string> the destinations that were written.
     */
    public function install(string $stack): array
    {
        $written = [];

        foreach ($this->manifest[$stack] ?? [] as [$stub, $destination]) {
            $this->files->ensureDirectoryExists(dirname($destination));
            $this->files->copy($stub, $destination);

            $written[] = $destination;
        }

        return $written;
    }
}
