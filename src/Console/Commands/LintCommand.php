<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Console\Commands;

use Fanmade\AdrManager\Services\AdrLinter;
use Illuminate\Console\Command;

final class LintCommand extends Command
{
    protected $signature = 'adr:lint';

    protected $description = 'Validate the ADR files for format, status, link and sequence integrity.';

    public function handle(AdrLinter $linter): int
    {
        $issues = $linter->lint();

        if ($issues === []) {
            $this->info('All ADRs passed linting.');

            return self::SUCCESS;
        }

        foreach ($issues as $issue) {
            $this->line("<fg=red>✗</> [{$issue->category}] {$issue->file}: {$issue->message}");
        }

        $this->newLine();
        $this->error(count($issues).' issue(s) found.');

        return self::FAILURE;
    }
}
