<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Support;

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Illuminate\Support\Str;

/**
 * Builds what a developer needs to commit a record by hand when the current
 * environment may not write to disk: the rendered Markdown, its target path
 * and a ready-to-paste `git add` / `git commit` block. Shared by the Livewire
 * components and the published Inertia starter controllers.
 */
final class CommitInstructions
{
    public function __construct(private readonly MarkdownGenerator $generator) {}

    /**
     * @return array{markdown: string, path: string, commands: string}
     */
    public function for(AdrDto $adr): array
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
            'markdown' => $this->generator->render($adr),
            'path' => $path,
            'commands' => $commands,
        ];
    }
}
