<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire\Concerns;

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Support\Environment;
use Illuminate\Support\Str;

/**
 * Shared authoring behaviour for the editing components: whether writes are
 * allowed in the current environment, and — when they are not — the Markdown
 * and git commands a developer can copy to commit the record by hand.
 */
trait AuthorsAdrs
{
    public function canPersist(): bool
    {
        return Environment::authoringAllowed();
    }

    /**
     * The rendered Markdown a developer would commit, plus a ready-to-paste
     * `git add` / `git commit` block for the record.
     *
     * @return array{markdown: string, path: string, commands: string}
     */
    protected function commitInstructions(AdrDto $adr): array
    {
        $directory = config('adr-manager.path', 'docs/adrs');
        $directory = is_string($directory) ? $directory : 'docs/adrs';
        $path = rtrim($directory, '/').'/'.$adr->id.'-'.Str::slug($adr->title).'.md';

        $commands = sprintf(
            "git add %s\ngit commit -m %s",
            $path,
            escapeshellarg("docs(adr): {$adr->id} {$adr->title}"),
        );

        return [
            'markdown' => app(MarkdownGenerator::class)->render($adr),
            'path' => $path,
            'commands' => $commands,
        ];
    }
}
