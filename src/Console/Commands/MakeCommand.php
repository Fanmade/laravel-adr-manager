<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Console\Commands;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Support\CommitInstructions;
use Fanmade\AdrManager\Support\Environment;
use Illuminate\Console\Command;

/**
 * Terminal-first authoring: creates the next ADR Markdown file, wires up
 * reciprocal supersede links, and prints the git commands to commit it.
 */
final class MakeCommand extends Command
{
    protected $signature = 'adr:make
        {title : The decision title}
        {--status=proposed : Initial status}
        {--author= : Author recorded in the front-matter}
        {--supersedes=* : Id(s) of records this decision supersedes}';

    protected $description = 'Create a new ADR Markdown file.';

    public function handle(AdrRepository $repository, CommitInstructions $instructions): int
    {
        if (! Environment::authoringAllowed()) {
            $this->error('ADR authoring is not enabled in this environment (see adr-manager.authoring.environments).');

            return self::FAILURE;
        }

        $title = $this->argument('title');
        $title = is_string($title) ? trim($title) : '';

        if ($title === '') {
            $this->error('The title must not be empty.');

            return self::FAILURE;
        }

        $status = $this->option('status');
        $status = is_string($status) ? $status : '';

        if (! in_array($status, $this->allowedStatuses(), true)) {
            $this->error("Invalid status [{$status}]. Valid: ".implode(', ', $this->allowedStatuses()).'.');

            return self::FAILURE;
        }

        $raw = $this->option('supersedes');
        $supersedes = is_array($raw) ? array_values(array_filter($raw, 'is_string')) : [];

        foreach ($supersedes as $target) {
            if ($repository->find($target) === null) {
                $this->error("Cannot supersede unknown ADR [{$target}].");

                return self::FAILURE;
            }
        }

        $author = $this->option('author');
        $id = str_pad((string) ($repository->getLatestSequence() + 1), 4, '0', STR_PAD_LEFT);

        $repository->save(AdrDto::fromArray([
            'id' => $id,
            'title' => $title,
            'status' => $status,
            'author' => is_string($author) ? $author : null,
        ]));

        foreach ($supersedes as $target) {
            $repository->supersede($target, $id);
        }

        $adr = $repository->find($id);
        $details = $instructions->for($adr ?? AdrDto::fromArray(['id' => $id, 'title' => $title, 'status' => $status]));

        $this->info("Created ADR-{$id}: {$details['path']}");

        foreach ($supersedes as $target) {
            $this->line("Superseded ADR-{$target} (its file changed too — commit it along).");
        }

        $this->newLine();

        foreach (explode("\n", $details['commands']) as $command) {
            $this->line($command);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function allowedStatuses(): array
    {
        $statuses = config('adr-manager.statuses', []);

        return is_array($statuses) ? array_values(array_filter($statuses, 'is_string')) : [];
    }
}
